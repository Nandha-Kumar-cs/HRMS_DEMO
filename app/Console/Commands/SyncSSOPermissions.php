<?php

namespace App\Console\Commands;

use App\Services\SSOService;
use Illuminate\Console\Command;

class SyncSSOPermissions extends Command
{
    protected $signature = 'sso:sync-permissions
                            {--dry-run : Print the manifest without sending it}';

    protected $description = 'Publish the HRMS permissions manifest to the MagDyn SSO server';

    public function handle(SSOService $sso): int
    {
        // ── Pre-flight checks ─────────────────────────────────────────────────
        if (!config('magdyn.sso.enabled')) {
            $this->error('SSO is not enabled. Set MAGDYN_SSO_ENABLED=true in .env first.');
            return self::FAILURE;
        }

        if (!config('magdyn.sso.client_id') || !config('magdyn.sso.client_secret')) {
            $this->error('SSO client credentials are missing. Set MAGDYN_SSO_CLIENT_ID and MAGDYN_SSO_CLIENT_SECRET in .env.');
            return self::FAILURE;
        }

        // ── Load manifest ─────────────────────────────────────────────────────
        $manifest = require base_path('app/Sso/manifest.php');

        $permCount  = count($manifest['permissions'] ?? []);
        $roleCount  = count($manifest['roles']       ?? []);

        $this->info("Manifest loaded: {$permCount} permissions, {$roleCount} roles");
        $this->line('SSO server: ' . config('magdyn.sso.provider_url'));
        $this->line('Client ID:  ' . config('magdyn.sso.client_id'));
        $this->newLine();

        // ── Dry-run mode ──────────────────────────────────────────────────────
        if ($this->option('dry-run')) {
            $this->warn('[dry-run] Manifest would be sent (not sending):');
            $this->line(json_encode($manifest, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        // ── Send to SSO ───────────────────────────────────────────────────────
        $this->info('Syncing permissions with the SSO server…');

        try {
            $result = $sso->registerPermissions($manifest);
        } catch (\Throwable $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        // ── Show results ──────────────────────────────────────────────────────
        $this->info('✓ Sync complete!');

        $counts = $result['counts'] ?? $result;
        if (is_array($counts)) {
            $rows = [];
            foreach ($counts as $key => $value) {
                $rows[] = [$key, $value];
            }
            $this->table(['Metric', 'Count'], $rows);
        }

        return self::SUCCESS;
    }
}
