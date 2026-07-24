<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupPayrollData extends Command
{
    protected $signature = 'payroll:cleanup
                            {--force : Skip confirmation prompts (use with extreme caution)}
                            {--no-backup : Skip database backup before deletion}
                            {--dry-run : Show what would be deleted without actually deleting anything}';

    protected $description = 'Safely purge all transactional HR/payroll data while preserving employee master records and April attendance data';

    /**
     * Tables deleted in FK-safe order (children before parents).
     * Format: [table, description, whereClause|null]
     * A null whereClause means delete ALL rows; a callable receives the query builder.
     */
    private array $deletionPlan = [
        // ── Attendance: delete everything EXCEPT April records ──────────────
        // Handled separately in performCleanup() to keep April rows.

        // ── Loan chain (repayments first, then loan headers) ────────────────
        ['loan_repayments',       'Loan repayments'],
        ['employee_loans',        'Employee loans'],

        // ── Payroll / payslips ───────────────────────────────────────────────
        ['salary_slips',          'Payslips / payroll records'],

        // ── Salary revisions & letters ───────────────────────────────────────
        ['employee_increments',   'Salary revisions (increments)'],
        ['increment_letters',     'Increment letters'],

        // ── Offer / confirmation letters (transactional documents) ───────────
        ['offer_letters',         'Offer letters'],
        ['confirmation_letters',  'Confirmation letters'],

        // ── Promotions ───────────────────────────────────────────────────────
        ['employee_promotions',   'Employee promotions'],

        // ── Incentives / bonuses ─────────────────────────────────────────────
        ['employee_bonuses',      'Incentives / bonuses'],

        // ── Benefits ─────────────────────────────────────────────────────────
        ['employee_benefits',     'Employee benefits'],

        // ── Leave ────────────────────────────────────────────────────────────
        ['leave_requests',        'Leave requests'],
        ['leave_balances',        'Leave balances'],

        // ── Comp-off ─────────────────────────────────────────────────────────
        ['comp_off_credits',      'Comp-off credits / requests'],
        ['comp_off_working_days', 'Comp-off working days'],

        // ── On-duty requests ─────────────────────────────────────────────────
        ['on_duties',             'On-duty requests'],

        // ── Training progress (transactional, not config) ────────────────────
        ['training_progress',     'Training progress records'],

        // ── Asset assignments (transactional) ────────────────────────────────
        ['asset_assignments',     'Asset assignments'],

        // ── No-due certificates ──────────────────────────────────────────────
        ['no_due_certificates',   'No-due certificates'],

        // ── Logs & sessions ──────────────────────────────────────────────────
        ['activity_logs',         'Activity logs'],
        ['sessions',              'User sessions'],
    ];

    // ─────────────────────────────────────────────────────────────────────────

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->printBanner($isDryRun);
        $this->showCleanupPlan();

        if (!$isDryRun && !$this->confirmAction()) {
            $this->line('  Cleanup cancelled. No data was modified.');
            return Command::SUCCESS;
        }

        if (!$isDryRun && !$this->option('no-backup')) {
            $this->newLine();
            $this->info('Step 1/3 ─ Creating database backup...');
            if (!$this->createBackup()) {
                $this->error('  Backup failed. Aborting cleanup for safety. Pass --no-backup to skip (not recommended).');
                return Command::FAILURE;
            }
        }

        $this->newLine();
        $label = $isDryRun ? 'Step 1/1 ─ Dry-run analysis (no data will be deleted)...' : 'Step 2/3 ─ Purging transactional data...';
        $this->info($label);

        try {
            $deletedCounts = $this->performCleanup($isDryRun);
        } catch (\Throwable $e) {
            $this->error('  Cleanup failed and was rolled back: ' . $e->getMessage());
            Log::error('payroll:cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }

        $this->newLine();
        $this->showSummary($deletedCounts, $isDryRun);

        return Command::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  UI helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function printBanner(bool $isDryRun): void
    {
        $this->newLine();
        $this->line('  ┌─────────────────────────────────────────────────────┐');
        $this->line('  │       HRMS DATA CLEANUP UTILITY  v1.0               │');
        $this->line('  │  Preserves : Employee master data                   │');
        $this->line('  │             April attendance records (all years)     │');
        $this->line('  │  Purges    : All other transactional HR/payroll data │');
        if ($isDryRun) {
            $this->line('  │  ⚡ DRY-RUN MODE — nothing will be deleted           │');
        }
        $this->line('  └─────────────────────────────────────────────────────┘');
        $this->newLine();
    }

    private function showCleanupPlan(): void
    {
        $this->line('  <fg=yellow>Tables scheduled for full purge:</>');
        foreach ($this->deletionPlan as [$table, $description]) {
            $this->line("    • {$description} (<fg=gray>{$table}</>)");
        }
        $this->line('  <fg=yellow>Attendances: all rows where MONTH(date) ≠ 4 will be deleted.</>');
        $this->newLine();
        $this->line('  <fg=green>Tables preserved:</>');
        $this->line('    • employees (master data)');
        $this->line('    • employee_bank_details, employee_documents, employee_family_members');
        $this->line('    • departments, designations, entities');
        $this->line('    • users, roles, permissions (auth)');
        $this->line('    • leave_types, salary_components, benefit_fund_types, holidays (config)');
        $this->line('    • company_assets, training_modules, training_lessons, settings');
        $this->line('    • attendances where MONTH(date) = 4  (April — all years)');
        $this->newLine();
    }

    private function confirmAction(): bool
    {
        $this->warn('  ⚠️  WARNING: This operation is IRREVERSIBLE.');
        $this->warn('  A backup will be taken unless --no-backup is specified.');
        $this->newLine();

        if (!$this->confirm('  Are you sure you want to permanently delete transactional data?', false)) {
            return false;
        }

        if (!$this->confirm('  Please confirm ONCE MORE to proceed:', false)) {
            return false;
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Backup
    // ─────────────────────────────────────────────────────────────────────────

    private function createBackup(): bool
    {
        $config   = config('database.connections.' . config('database.default'));
        $host     = escapeshellarg($config['host'] ?? '127.0.0.1');
        $port     = (int) ($config['port'] ?? 3306);
        $database = escapeshellarg($config['database']);
        $username = escapeshellarg($config['username']);
        $password = $config['password'] ?? '';

        $backupDir      = storage_path('backups/payroll-cleanup');
        $timestamp      = Carbon::now()->format('Y-m-d_His');
        $backupFilename = "{$backupDir}/hrms_backup_{$timestamp}.sql";

        if (!is_dir($backupDir) && !mkdir($backupDir, 0750, true)) {
            $this->error("  Cannot create backup directory: {$backupDir}");
            return false;
        }

        // Build the mysqldump command; password is passed via env var to avoid shell history exposure
        $envPassword  = !empty($password) ? "MYSQL_PWD=" . escapeshellarg($password) . " " : '';
        $dumpCommand  = "{$envPassword}mysqldump --host={$host} --port={$port} --user={$username} "
                      . "--single-transaction --quick --skip-lock-tables "
                      . "{$database} > " . escapeshellarg($backupFilename) . ' 2>&1';

        exec($dumpCommand, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->error('  mysqldump failed (exit code ' . $exitCode . '): ' . implode(' ', $output));
            $this->error('  Ensure mysqldump is in PATH or pass --no-backup to skip.');
            return false;
        }

        $sizeMb = round(filesize($backupFilename) / 1048576, 2);
        $this->line("  <fg=green>✔ Backup saved:</> {$backupFilename} ({$sizeMb} MB)");
        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Core deletion logic — wrapped in a transaction
    // ─────────────────────────────────────────────────────────────────────────

    private function performCleanup(bool $isDryRun): array
    {
        $counts = [];

        DB::transaction(function () use ($isDryRun, &$counts) {
            // Temporarily disable FK checks so we can delete in any order without
            // worrying about every cascade edge. Re-enabled at end of transaction.
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            try {
                // ── Standard full-table purges ─────────────────────────────
                foreach ($this->deletionPlan as [$table, $description]) {
                    $count = DB::table($table)->count();
                    $this->line("  Purging {$description} (<fg=gray>{$table}</>): <fg=yellow>{$count} rows</>");

                    if (!$isDryRun) {
                        DB::table($table)->delete();
                    }

                    $counts[$table] = $count;
                }

                // ── Attendances: keep only April rows ──────────────────────
                $aprilCount     = DB::table('attendances')->whereRaw('MONTH(date) = 4')->count();
                $nonAprilCount  = DB::table('attendances')->whereRaw('MONTH(date) != 4')->count();

                $this->line("  Purging non-April attendance records (<fg=gray>attendances</>): <fg=yellow>{$nonAprilCount} rows</> (keeping {$aprilCount} April rows)");

                if (!$isDryRun) {
                    DB::table('attendances')->whereRaw('MONTH(date) != 4')->delete();
                }

                $counts['attendances_deleted'] = $nonAprilCount;
                $counts['attendances_kept']    = $aprilCount;

            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        });

        return $counts;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Summary report
    // ─────────────────────────────────────────────────────────────────────────

    private function showSummary(array $counts, bool $isDryRun): void
    {
        $prefix = $isDryRun ? '[DRY-RUN] Would delete' : 'Deleted';

        $totalDeleted = array_sum(array_filter($counts, fn ($k) => $k !== 'attendances_kept', ARRAY_FILTER_USE_KEY));

        $remainingEmployees = DB::table('employees')->count();
        $remainingApril     = DB::table('attendances')->whereRaw('MONTH(date) = 4')->count();

        $this->line('  ┌─────────────────────────────────────────────────────┐');
        $this->line('  │               CLEANUP SUMMARY                       │');
        $this->line('  ├─────────────────────────────────────────────────────┤');

        $this->line("  │  {$prefix}:");
        foreach ($this->deletionPlan as [$table, $description]) {
            $n     = $counts[$table] ?? 0;
            $label = str_pad("    • {$description}", 48);
            $this->line("  │  {$label} {$n}");
        }
        $nonApril = $counts['attendances_deleted'] ?? 0;
        $aprilKept = $counts['attendances_kept'] ?? $remainingApril;
        $this->line('  │  ' . str_pad('    • Non-April attendance rows', 48) . " {$nonApril}");

        $this->line('  ├─────────────────────────────────────────────────────┤');
        $this->line('  │  ' . str_pad('  Total records purged', 48) . " {$totalDeleted}");
        $this->line('  ├─────────────────────────────────────────────────────┤');
        $this->line('  │  Remaining data:');
        $this->line('  │  ' . str_pad('    • Employee master records', 48) . " {$remainingEmployees}");
        $this->line('  │  ' . str_pad('    • April attendance records (preserved)', 48) . " {$remainingApril}");
        $this->line('  └─────────────────────────────────────────────────────┘');
        $this->newLine();

        if ($isDryRun) {
            $this->warn('  Dry-run complete. Re-run without --dry-run to apply changes.');
        } else {
            $this->info('  Cleanup completed successfully.');
            Log::info('payroll:cleanup completed', [
                'total_deleted'       => $totalDeleted,
                'employees_remaining' => $remainingEmployees,
                'april_attendance'    => $remainingApril,
                'run_by'              => get_current_user(),
                'run_at'              => Carbon::now()->toIso8601String(),
            ]);
        }
    }
}
