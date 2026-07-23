<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Parses the "Monthly IN/OUT Report" XLS from biometric machines.
 *
 * File layout — each employee occupies one block:
 *   Row 1: Company name  (Magneto Dynamics Pvt Ltd)
 *   Row 2: Dept. Name  |  |  ELECTRONICS  |  …  |  Report Month:-  |  …  |  March-2026
 *   Row 3: Empcode  |  0021  |  …  |  Name  |  …  |  Raj mohan
 *   Row 4: Date | Shift | IN | Out1 | In2 … | Out | Work+OT | OT | Break
 *   Row 5…N: daily rows  (dd/MM/yyyy in col 0, Shift in col 1, IN in col 2,
 *                          final OUT in col 17, Work+OT in col 18, OT in col 19)
 *   Row N+1: Total Work+OT Hrs:- …
 *   Row N+2: Total Pr. | … | Total WO | … | Tot. Ab. | …
 *   Row N+3: Total HL | … | Total Leave | …
 *   (next block starts)
 *
 * Shift codes:
 *   G  = General working shift
 *   X  = Weekly off / holiday  → skip row (no attendance record needed)
 */
class MonthlyAttendanceImport implements ToCollection
{
    public int   $saved    = 0;
    public int   $updated  = 0;
    public int   $skipped  = 0;
    public int   $empCount = 0;
    public array $warnings = [];
    public array $errors   = [];
    public string $reportMonth = '';

    private array $empMap = [];

    // ── Entry point ───────────────────────────────────────────────────────
    public function collection(Collection $rows): void
    {
        // Build fuzzy employee lookup map (same as daily import)
        Employee::all()->each(function (Employee $emp) {
            foreach ($this->codeKeys($emp->employee_code) as $key) {
                $this->empMap[$key] = $emp;
            }
        });

        $currentEmployee = null;
        $inDataBlock     = false;   // true once we pass the "Date" header row

        foreach ($rows as $idx => $row) {
            $col0 = trim((string) ($row[0] ?? ''));

            // ── Dept header row: extract report month ─────────────────────
            if ($col0 === 'Dept. Name') {
                $this->extractMonth($row);
                $inDataBlock = false;
                continue;
            }

            // ── Empcode row: new employee block ───────────────────────────
            if ($col0 === 'Empcode') {
                $rawCode = trim((string) ($row[1] ?? ''));
                $currentEmployee = $this->findEmployee($rawCode);

                if (!$currentEmployee) {
                    $this->skipped++;
                    $this->warnings[] = "Employee code \"{$rawCode}\" not found — rows skipped.";
                }
                $inDataBlock = false;
                continue;
            }

            // ── Column header row: next rows are daily data ───────────────
            if ($col0 === 'Date') {
                if ($currentEmployee) {
                    $inDataBlock = true;
                    $this->empCount++;
                }
                continue;
            }

            // ── Summary / footer rows: end of employee block ──────────────
            if (str_starts_with($col0, 'Total')) {
                $inDataBlock = false;
                continue;
            }

            // ── Daily data rows ───────────────────────────────────────────
            if ($inDataBlock && $currentEmployee && $this->isDateString($col0)) {
                $this->processDay($row, $col0, $currentEmployee, $idx + 1);
            }
        }
    }

    // ── Process one daily row ─────────────────────────────────────────────
    private function processDay(mixed $row, string $dateStr, Employee $emp, int $rowNum): void
    {
        $shift    = strtoupper(trim((string) ($row[1] ?? '')));
        $inRaw    = trim((string) ($row[2]  ?? ''));  // col C: first punch IN
        $outRaw   = trim((string) ($row[17] ?? ''));  // col R: final punch OUT
        $workRaw  = trim((string) ($row[18] ?? ''));  // col S: Work+OT (HH:MM)
        $otRaw    = trim((string) ($row[19] ?? ''));  // col T: OT (HH:MM)

        // X shift = weekly off / holiday — no attendance record needed
        if ($shift === 'X') {
            return;
        }

        $date = $this->parseDate($dateStr);
        if (!$date) {
            $this->warnings[] = "Row {$rowNum}: Cannot parse date \"{$dateStr}\" — skipped.";
            return;
        }

        $checkIn    = $this->parseTime($inRaw);
        $checkOut   = $this->parseTime($outRaw);
        $workHours  = $this->parseWorkHours($workRaw);
        $otHours    = $this->parseWorkHours($otRaw);
        $status     = $this->resolveStatus($checkIn, $workHours);

        $payload = [
            'status'        => $status,
            'check_in'      => $checkIn,
            'check_out'     => $checkOut,
            'working_hours' => $workHours,
            'ot_hours'      => ($otHours > 0) ? $otHours : null,
            'remarks'       => 'Monthly import',
        ];

        $existing = Attendance::where('employee_id', $emp->id)
            ->where('date', $date)
            ->first();

        if ($existing) {
            $existing->update($payload);
            $this->updated++;
        } else {
            Attendance::create(array_merge($payload, [
                'employee_id' => $emp->id,
                'date'        => $date,
            ]));
            $this->saved++;
        }
    }

    // ── Extract "March-2026" from the Dept header row ─────────────────────
    private function extractMonth(mixed $row): void
    {
        foreach ($row as $cell) {
            $val = trim((string) $cell);
            // Matches "March-2026", "Jan-2026", "February-2026" etc.
            if (preg_match('/^([A-Za-z]+)-(\d{4})$/', $val, $m)) {
                try {
                    $dt = Carbon::createFromFormat('F-Y', $m[1] . '-' . $m[2]);
                    $this->reportMonth = $dt->format('F Y');
                } catch (\Throwable) {
                    // Try abbreviated month name
                    try {
                        $dt = Carbon::createFromFormat('M-Y', $m[1] . '-' . $m[2]);
                        $this->reportMonth = $dt->format('F Y');
                    } catch (\Throwable) {}
                }
                return;
            }
        }
    }

    // ── Employee lookup ───────────────────────────────────────────────────
    private function codeKeys(string $code): array
    {
        $lower    = strtolower(trim($code));
        $stripped = strtolower(preg_replace('/[^a-z0-9]/i', '', $code));
        $trimDot  = rtrim($lower, '.');
        // Also try leading-zero variants: "21" and "0021" both map to same key
        $noLeadingZero = ltrim($stripped, '0') ?: $stripped;
        return array_unique(array_filter([$lower, $stripped, $trimDot, $noLeadingZero]));
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

    private function isDateString(string $val): bool
    {
        return (bool) preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $val);
    }

    private function parseDate(string $raw): ?string
    {
        // Format from file: dd/MM/yyyy
        try {
            $d = Carbon::createFromFormat('d/m/Y', $raw);
            if ($d && $d->year > 1900 && $d->year < 2100) {
                return $d->format('Y-m-d');
            }
        } catch (\Throwable) {}
        return null;
    }

    /** "09:01" → "09:01:00"  |  "--:--" or "00:00" → null */
    private function parseTime(string $raw): ?string
    {
        if ($raw === '' || $raw === '--:--' || $raw === '00:00') return null;
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $raw, $m)) {
            return sprintf('%02d:%02d:00', (int) $m[1], (int) $m[2]);
        }
        return null;
    }

    /** "08:49" → 8.82  |  "00:00" or "--:--" → null */
    private function parseWorkHours(string $raw): ?float
    {
        if ($raw === '' || $raw === '--:--' || $raw === '00:00') return null;
        if (preg_match('/^(\d+):(\d{2})$/', $raw, $m)) {
            $val = round((int) $m[1] + ((int) $m[2] / 60), 2);
            return $val > 0 ? $val : null;
        }
        return null;
    }

    /**
     * Status rules (same as daily import):
     *   absent   → no check-in
     *   half_day → check-in but worked < 4 hours
     *   late     → check-in after 09:30 AND worked ≥ 4 hours
     *   present  → everything else with a valid check-in
     */
    private function resolveStatus(?string $checkIn, ?float $workHours): string
    {
        if (!$checkIn) return 'absent';

        $hours = $workHours ?? 0.0;

        if ($hours > 0.0 && $hours < 4.0) return 'half_day';

        try {
            $inCarbon = Carbon::createFromFormat('H:i:s', $checkIn);
            $cutoff   = Carbon::createFromFormat('H:i:s', '09:30:00');
            if ($inCarbon->gt($cutoff) && $hours >= 4.0) return 'late';
        } catch (\Throwable) {}

        return 'present';
    }
}
