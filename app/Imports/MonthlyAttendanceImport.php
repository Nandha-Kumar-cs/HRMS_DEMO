<?php

namespace App\Imports;

use App\Helpers\AppSettings;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Parses the "Monthly IN/OUT Report" XLS from biometric machines.
 *
 * File layout — each employee occupies one block:
 *   Row 1 : Company name
 *   Row 2 : Dept. Name | … | ELECTRONICS | … | Report Month:- | … | May-2026
 *   Row 3 : Empcode | 0021 | … | Name | … | Raj mohan
 *   Row 4 : Date | Shift | IN | Out1 | In2 … | Out | Work+OT | OT | Break
 *   Row 5…N: daily rows
 *   Row N+1: Total …  (footer — marks end of employee block)
 *
 * Columns (0-based index):
 *   0  = Date
 *   1  = Shift  (G = working, X = weekly off / holiday)
 *   2  = IN     (first check-in)
 *   3  = Out1
 *   4  = In2
 *   …intermediate pairs…
 *   17 = Out    (final check-out — summary column)
 *   18 = Work+OT
 *   19 = OT
 *   20 = Break
 *
 * We extract ONLY: employee name + code (for lookup), date, check-in, check-out.
 * Working hours and status are calculated from check-in/check-out, not from
 * the Work+OT column, to avoid discrepancies when the machine shows 00:00.
 */
class MonthlyAttendanceImport implements ToCollection
{
    public int    $saved       = 0;
    public int    $updated     = 0;
    public int    $skipped     = 0;
    public int    $empCount    = 0;
    public array  $warnings    = [];
    public array  $errors      = [];
    public string $reportMonth = '';

    /** [code-variant => Employee] for fast lookup */
    private array $empCodeMap = [];
    /** [lower-full-name => Employee] for name-based fallback */
    private array $empNameMap = [];

    // ── Entry point ───────────────────────────────────────────────────────────
    public function collection(Collection $rows): void
    {
        Employee::all()->each(function (Employee $emp) {
            foreach ($this->codeKeys($emp->employee_code) as $key) {
                $this->empCodeMap[$key] = $emp;
            }
            $this->empNameMap[strtolower(trim($emp->full_name))] = $emp;
        });

        $currentEmployee = null;
        $inDataBlock     = false;

        foreach ($rows as $idx => $row) {
            $col0 = trim((string) ($row[0] ?? ''));

            // ── Dept header row → extract report month ────────────────────
            if ($col0 === 'Dept. Name') {
                $this->extractMonth($row);
                $inDataBlock = false;
                continue;
            }

            // ── Empcode row → start new employee block ────────────────────
            if ($col0 === 'Empcode') {
                $rawCode = trim((string) ($row[1] ?? ''));
                $rawName = $this->extractNameFromRow($row);

                // Try employee code first, then name as fallback
                $currentEmployee = $this->findByCode($rawCode)
                    ?? ($rawName ? $this->findByName($rawName) : null);

                if (!$currentEmployee) {
                    $this->skipped++;
                    $label = $rawCode ?: $rawName ?: '(unknown)';
                    $this->warnings[] = "Employee \"{$label}\" not found in system — rows skipped.";
                }
                $inDataBlock = false;
                continue;
            }

            // ── Column header row → next rows are daily data ──────────────
            if ($col0 === 'Date') {
                if ($currentEmployee) {
                    $inDataBlock = true;
                    $this->empCount++;
                }
                continue;
            }

            // ── Footer / summary rows → end of employee block ─────────────
            if (str_starts_with($col0, 'Total')) {
                $inDataBlock = false;
                continue;
            }

            // ── Daily data rows ───────────────────────────────────────────
            if ($inDataBlock && $currentEmployee && $this->looksLikeDate($row[0] ?? '')) {
                $this->processDay($row, $row[0], $currentEmployee, $idx + 1);
            }
        }
    }

    // ── Process one daily row ─────────────────────────────────────────────────
    private function processDay(mixed $row, mixed $rawDate, Employee $emp, int $rowNum): void
    {
        $shift = strtoupper(trim((string) ($row[1] ?? '')));

        // X = weekly off / public holiday — skip entirely
        if ($shift === 'X') {
            return;
        }

        $date = $this->parseDate($rawDate);
        if (!$date) {
            $this->warnings[] = "Row {$rowNum}: Cannot parse date — skipped.";
            return;
        }

        // ── Extract ONLY check-in and check-out ───────────────────────────
        $checkIn  = $this->parseTime($row[2]  ?? '');   // col C: first IN
        $checkOut = $this->resolveCheckOut($row);        // col R: final Out (with fallback)

        // No punch at all (both --:-- / empty) → leave this day with no record.
        // The report shows red A for days with no record (past working days).
        if (!$checkIn && !$checkOut) {
            $this->skipped++;
            return;
        }

        // Check-in missing but checkout exists (unusual) → absent.
        // Also: check-in missing alone → absent (machine recorded nothing for check-in).
        if (!$checkIn) {
            $payload = [
                'status'        => 'absent',
                'check_in'      => null,
                'check_out'     => $checkOut,
                'working_hours' => null,
                'ot_hours'      => null,
                'remarks'       => 'Monthly import',
            ];
            $existing = Attendance::where('employee_id', $emp->id)->where('date', $date)->first();
            if ($existing) { $existing->update($payload); $this->updated++; }
            else { Attendance::create(array_merge($payload, ['employee_id' => $emp->id, 'date' => $date])); $this->saved++; }
            return;
        }

        // Calculate working hours ourselves — don't trust Work+OT column
        $workHours = $this->calcWorkHours($checkIn, $checkOut);

        // Determine status from check-in time + calculated hours
        $status = $this->resolveStatus($checkIn, $workHours);

        $payload = [
            'status'        => $status,
            'check_in'      => $checkIn,
            'check_out'     => $checkOut,
            'working_hours' => $workHours,
            'ot_hours'      => null,   // OT handled separately by the system
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

    // ── Resolve final check-out: try col R (index 17) then walk backwards ────
    private function resolveCheckOut(mixed $row): ?string
    {
        // Primary: final Out column (index 17 = col R)
        $out = $this->parseTime($row[17] ?? '');
        if ($out) return $out;

        // Fallback: walk backwards through Out columns (Out7→Out6→…→Out1)
        // Odd indices 3,5,7,9,11,13,15 are Out1-Out7
        foreach ([15, 13, 11, 9, 7, 5, 3] as $colIdx) {
            $t = $this->parseTime($row[$colIdx] ?? '');
            if ($t) return $t;
        }

        return null;
    }

    // ── Calculate working hours from check-in and check-out ─────────────────
    private function calcWorkHours(?string $checkIn, ?string $checkOut): ?float
    {
        if (!$checkIn || !$checkOut) return null;
        try {
            $in  = Carbon::createFromFormat('H:i:s', $checkIn);
            $out = Carbon::createFromFormat('H:i:s', $checkOut);
            if ($out->lte($in)) return null;
            return round(abs($out->diffInMinutes($in)) / 60, 2);
        } catch (\Throwable) {}
        return null;
    }

    // ── Status from check-in time + calculated hours (uses AppSettings) ──────
    private function resolveStatus(?string $checkIn, ?float $workHours): string
    {
        if (!$checkIn) return 'absent';

        try {
            $in    = Carbon::createFromFormat('H:i:s', $checkIn);
            $inMin = $in->hour * 60 + $in->minute;

            // Check-in at or after 11:00 AM → half day regardless of work hours
            if ($inMin >= 11 * 60) return 'half_day';

            // Worked > 0 but < 4 hours → half day
            $hours = $workHours ?? 0.0;
            if ($hours > 0.0 && $hours < 4.0) return 'half_day';

            // Late: checked in after (office_start + daily_grace)
            $lateThreshold = AppSettings::getOfficeStartMins() + AppSettings::getDailyGraceMinutes();
            if ($inMin > $lateThreshold) return 'late';

        } catch (\Throwable) {}

        return 'present';
    }

    // ── Extract "May-2026" from the Dept header row ──────────────────────────
    private function extractMonth(mixed $row): void
    {
        foreach ($row as $cell) {
            $val = trim((string) $cell);
            if (preg_match('/^([A-Za-z]+)-(\d{4})$/', $val, $m)) {
                foreach (['F-Y', 'M-Y'] as $fmt) {
                    try {
                        $dt = Carbon::createFromFormat($fmt, $m[1] . '-' . $m[2]);
                        $this->reportMonth = $dt->format('F Y');
                        return;
                    } catch (\Throwable) {}
                }
            }
        }
    }

    // ── Extract employee name from the Empcode row ───────────────────────────
    // Row layout: Empcode | 0021 | … | Name | … | Raj mohan
    private function extractNameFromRow(mixed $row): string
    {
        $foundNameLabel = false;
        foreach ($row as $cell) {
            $val = trim((string) $cell);
            if ($foundNameLabel && $val !== '') {
                return $val;
            }
            if (strtolower($val) === 'name') {
                $foundNameLabel = true;
            }
        }
        return '';
    }

    // ── Employee lookup by code ──────────────────────────────────────────────
    private function findByCode(string $rawCode): ?Employee
    {
        foreach ($this->codeKeys($rawCode) as $key) {
            if (isset($this->empCodeMap[$key])) {
                return $this->empCodeMap[$key];
            }
        }
        return null;
    }

    // ── Employee lookup by name (case-insensitive fallback) ──────────────────
    private function findByName(string $rawName): ?Employee
    {
        $key = strtolower(trim($rawName));
        return $this->empNameMap[$key] ?? null;
    }

    private function codeKeys(string $code): array
    {
        $lower         = strtolower(trim($code));
        $stripped      = strtolower(preg_replace('/[^a-z0-9]/i', '', $code));
        $trimDot       = rtrim($lower, '.');
        $noLeadingZero = ltrim($stripped, '0') ?: $stripped;
        return array_unique(array_filter([$lower, $stripped, $trimDot, $noLeadingZero]));
    }

    // ── Date helpers: handle string, Carbon/DateTime, and Excel serial ────────
    private function looksLikeDate(mixed $val): bool
    {
        if ($val instanceof \DateTime || $val instanceof Carbon) return true;
        if (is_numeric($val) && (float)$val > 40000) return true;  // Excel serial (post-2009)
        return (bool) preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', trim((string) $val));
    }

    private function parseDate(mixed $raw): ?string
    {
        try {
            // Carbon / DateTime from Maatwebsite (formatted date cell)
            if ($raw instanceof \DateTime || $raw instanceof Carbon) {
                return Carbon::instance($raw)->format('Y-m-d');
            }
            // Numeric Excel serial date (e.g. 46776 = 01/05/2026)
            if (is_numeric($raw) && (float)$raw > 40000) {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $raw);
                return Carbon::instance($dt)->format('Y-m-d');
            }
            // Text string: dd/MM/yyyy
            $str = trim((string) $raw);
            $d   = Carbon::createFromFormat('d/m/Y', $str);
            if ($d && $d->year > 1900 && $d->year < 2100) {
                return $d->format('Y-m-d');
            }
        } catch (\Throwable) {}
        return null;
    }

    // ── Time helpers: handle string "HH:MM" AND Excel time fraction (0–1) ────
    private function parseTime(mixed $raw): ?string
    {
        // Excel time fraction: 0.370833… = 08:54
        if (is_numeric($raw) && !($raw instanceof \DateTime)) {
            $f = (float) $raw;
            if ($f > 0.0 && $f < 1.0) {
                $totalMins = (int) round($f * 24 * 60);
                $h = intdiv($totalMins, 60);
                $m = $totalMins % 60;
                return sprintf('%02d:%02d:00', $h, $m);
            }
            return null;  // 0 = midnight / no punch
        }

        $str = trim((string) ($raw ?? ''));
        if ($str === '' || $str === '--:--' || $str === '00:00' || $str === '0') return null;

        // HH:MM format
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $str, $m)) {
            return sprintf('%02d:%02d:00', (int) $m[1], (int) $m[2]);
        }
        return null;
    }
}
