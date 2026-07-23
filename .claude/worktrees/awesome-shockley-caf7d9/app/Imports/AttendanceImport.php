<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Parses the "Daily IN/OUT Report" XLS from biometric machines.
 *
 * File layout (0-indexed columns):
 *   Row 0  : "Daily IN/OUT Report … Date :- … 06/05/2026"  ← date at col 7
 *   Row 1  : Company name — skip
 *   Row 2  : "Dept. Name | ELECTRONICS | …" — dept section header
 *   Row 3  : "Empcode | Name | Shift | INTime | Out1…OUTTime | Work+OT | OT | Break"
 *   Row 4+ : employee data rows (dept-header + col-header pair repeats each dept)
 *
 *   Col  0 : Empcode
 *   Col  3 : INTime    → check_in   ("--:--" = no punch)
 *   Col 18 : OUTTime   → check_out  ("--:--" = no punch)
 *   Col 19 : Work+OT   → working_hours (HH:MM)
 */
class AttendanceImport implements ToCollection
{
    public string $date    = '';
    public int    $saved   = 0;
    public int    $updated = 0;
    public int    $skipped = 0;
    public array  $errors  = [];
    public array  $warnings = [];

    /**
     * Employee lookup map — indexed by multiple key variants per employee
     * so both "MAGDYN-023." and "MAGDYN023" resolve to the same record.
     */
    private array $empMap = [];

    // ── Entry point ───────────────────────────────────────────────────────

    public function collection(Collection $rows): void
    {
        // Row 0: extract date from column 7
        $row0    = $rows->first();
        $rawDate = trim((string)($row0[7] ?? ''));
        $this->date = $this->parseReportDate($rawDate);

        if (! $this->date) {
            $this->errors[] = "Cannot read report date (expected dd/mm/yyyy at row 1, col 8). Import aborted.";
            return;
        }

        // Build fuzzy employee lookup map
        Employee::all()->each(function (Employee $emp) {
            foreach ($this->codeKeys($emp->employee_code) as $key) {
                $this->empMap[$key] = $emp;
            }
        });

        // Process each row
        foreach ($rows as $idx => $row) {
            // Skip the first two meta rows (title + company)
            if ($idx <= 1) continue;

            $col0 = trim((string)($row[0] ?? ''));

            // Skip blank, dept-header, and column-header rows
            if ($col0 === '' || $col0 === 'Dept. Name' || $col0 === 'Empcode') {
                continue;
            }

            $this->processRow($row, $idx + 1);
        }
    }

    // ── Row processing ────────────────────────────────────────────────────

    private function processRow(mixed $row, int $rowNum): void
    {
        $empCodeRaw = trim((string)($row[0]  ?? ''));
        $inTimeRaw  = trim((string)($row[3]  ?? ''));
        $outTimeRaw = trim((string)($row[18] ?? ''));
        $workRaw    = trim((string)($row[19] ?? ''));

        // Locate employee using fuzzy key lookup
        $employee = $this->findEmployee($empCodeRaw);

        if (! $employee) {
            $this->skipped++;
            $this->warnings[] = "Row {$rowNum}: Employee code \"{$empCodeRaw}\" not found in system — skipped.";
            return;
        }

        $checkIn   = $this->parseTime($inTimeRaw);
        $checkOut  = $this->parseTime($outTimeRaw);
        $workHours = $this->parseWorkHours($workRaw);
        $status    = $this->resolveStatus($checkIn, $workHours);

        $payload = [
            'status'        => $status,
            'check_in'      => $checkIn,
            'check_out'     => $checkOut,
            'working_hours' => $workHours,
            'remarks'       => 'Imported from attendance report',
        ];

        $existing = Attendance::where('employee_id', $employee->id)
            ->where('date', $this->date)
            ->first();

        if ($existing) {
            $existing->update($payload);
            $this->updated++;
        } else {
            Attendance::create(array_merge($payload, [
                'employee_id' => $employee->id,
                'date'        => $this->date,
            ]));
            $this->saved++;
        }
    }

    // ── Employee lookup ───────────────────────────────────────────────────

    /**
     * Generate multiple normalised key variants for an employee code so that
     * codes like "MAGDYN-023.", "MAGDYN023", "magdyn023" all resolve correctly.
     */
    private function codeKeys(string $code): array
    {
        $lower    = strtolower(trim($code));
        $stripped = strtolower(preg_replace('/[^a-z0-9]/i', '', $code));
        $trimDot  = rtrim($lower, '.');
        return array_unique(array_filter([$lower, $stripped, $trimDot]));
    }

    private function findEmployee(string $rawCode): ?Employee
    {
        foreach ($this->codeKeys($rawCode) as $key) {
            if (isset($this->empMap[$key])) {
                return $this->empMap[$key];
            }
        }
        return null;
    }

    // ── Parsers ───────────────────────────────────────────────────────────

    private function parseReportDate(string $raw): string
    {
        if ($raw === '') return '';

        // Excel serial date
        if (is_numeric($raw) && (float)$raw > 1000) {
            try {
                return ExcelDate::excelToDateTimeObject((float)$raw)->format('Y-m-d');
            } catch (\Throwable) {}
        }

        // String formats
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y', 'd.m.Y'] as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $raw);
                if ($d && $d->year > 1900 && $d->year < 2100) {
                    return $d->format('Y-m-d');
                }
            } catch (\Throwable) {}
        }

        return '';
    }

    /**
     * "09:01" → "09:01:00" | "--:--" or "00:00" → null
     */
    private function parseTime(string $raw): ?string
    {
        if ($raw === '' || $raw === '--:--' || $raw === '00:00') return null;

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $raw, $m)) {
            return sprintf('%02d:%02d:00', (int)$m[1], (int)$m[2]);
        }

        return null;
    }

    /**
     * "08:49" → 8.82  |  "00:00" → 0.0  |  "--:--" → null
     */
    private function parseWorkHours(string $raw): ?float
    {
        if ($raw === '' || $raw === '--:--') return null;

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $raw, $m)) {
            return round((int)$m[1] + ((int)$m[2] / 60), 2);
        }

        return null;
    }

    /**
     * Status rules:
     *   absent   → no check-in at all
     *   half_day → check-in present but worked < 4 hours
     *   late     → check-in after 09:30 AND worked ≥ 4 hours
     *   present  → everything else with a valid check-in
     */
    private function resolveStatus(?string $checkIn, ?float $workHours): string
    {
        if (! $checkIn) {
            return 'absent';
        }

        $hours = $workHours ?? 0.0;

        // Has check-in but worked a very short time → half day
        if ($hours > 0.0 && $hours < 4.0) {
            return 'half_day';
        }

        // Check for late arrival (after 09:30)
        try {
            $inCarbon = Carbon::createFromFormat('H:i:s', $checkIn);
            $cutoff   = Carbon::createFromFormat('H:i:s', '09:30:00');
            if ($inCarbon->gt($cutoff) && $hours >= 4.0) {
                return 'late';
            }
        } catch (\Throwable) {}

        // has check-in (whether or not checkout was recorded) → present
        return 'present';
    }
}
