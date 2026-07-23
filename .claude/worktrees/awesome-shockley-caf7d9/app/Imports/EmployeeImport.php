<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class EmployeeImport implements ToCollection, WithStartRow
{
    public int   $imported = 0;
    public int   $updated  = 0;
    public int   $skipped  = 0;
    public array $errors   = [];
    public array $warnings = [];

    private Collection $departments;
    private Collection $designations;

    // Excel column positions (0-indexed)
    // 0:Empcode 1:Enroll No 2:Name 3:Father 4:Address 5:CountryCode
    // 6:Mobile 7:Email 8:CompID 9:DeptID 10:DesgID 11:CatID
    // 12:ActiveStatus 13:Shift 14:AutoShift 15:WO1 16:WO2
    // 17:Sat Off 18:Sat Half Day 19:Status(T/F) 20:DOJ
    // 21:ENT 22:CountryCode1 23:Mobile1 24:DOB 25:DOR

    public function startRow(): int
    {
        return 2; // skip the header row
    }

    public function collection(Collection $rows): void
    {
        $this->departments  = Department::all();
        $this->designations = Designation::all();

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;
            try {
                $this->processRow($row, $rowNum);
            } catch (\Throwable $e) {
                $this->skipped++;
                $this->errors[] = "Row {$rowNum}: " . $e->getMessage();
            }
        }
    }

    private function processRow(mixed $row, int $rowNum): void
    {
        $empCode = trim((string) ($row[0] ?? ''));
        $name    = trim((string) ($row[2] ?? ''));

        // Skip completely blank rows
        if ($empCode === '' && $name === '') {
            return;
        }

        if ($empCode === '') {
            $this->skipped++;
            $this->errors[] = "Row {$rowNum}: Employee code is required — row skipped.";
            return;
        }

        if ($name === '') {
            $this->skipped++;
            $this->errors[] = "Row {$rowNum}: Employee name is required — row skipped.";
            return;
        }

        // ── Optional fields ────────────────────────────────────────────────
        $phone = trim((string) ($row[6] ?? ''));
        $email = trim((string) ($row[7] ?? ''));

        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->warnings[] = "Row {$rowNum}: Invalid email '{$email}' — email ignored.";
            $email = '';
        }

        // ── Department lookup ──────────────────────────────────────────────
        $deptId  = null;
        $deptRaw = trim((string) ($row[9] ?? ''));
        if ($deptRaw !== '') {
            $dept = $this->findByNameOrId($this->departments, $deptRaw);
            if ($dept) {
                $deptId = $dept->id;
            } else {
                $this->warnings[] = "Row {$rowNum}: Department '{$deptRaw}' not found — left blank.";
            }
        }

        // ── Designation lookup ─────────────────────────────────────────────
        $desgId  = null;
        $desgRaw = trim((string) ($row[10] ?? ''));
        if ($desgRaw !== '') {
            $desg = $this->findByNameOrId($this->designations, $desgRaw);
            if ($desg) {
                $desgId = $desg->id;
            } else {
                $this->warnings[] = "Row {$rowNum}: Designation '{$desgRaw}' not found — left blank.";
            }
        }

        // ── Status mapping ─────────────────────────────────────────────────
        $statusRaw = strtolower(trim((string) ($row[12] ?? '')));
        $status    = 'active';
        if ($statusRaw !== '' && (
            str_contains($statusRaw, 'de') ||
            str_contains($statusRaw, 'inactive') ||
            $statusRaw === '0' || $statusRaw === 'false'
        )) {
            $status = 'inactive';
        }

        // ── Dates ──────────────────────────────────────────────────────────
        $joiningDate = $this->parseDate($row[20] ?? null);
        $dob         = $this->parseDate($row[24] ?? null);

        // ── Build payload (no salary — entered manually) ───────────────────
        $payload = [
            'full_name'      => $name,
            'phone'          => $phone ?: null,
            'status'         => $status,
            'department_id'  => $deptId,
            'designation_id' => $desgId,
            'joining_date'   => $joiningDate,
            'dob'            => $dob,
        ];

        // ── Upsert by employee_code ────────────────────────────────────────
        $existing = Employee::where('employee_code', $empCode)->first();

        if ($existing) {
            // Never overwrite salary or existing email on update
            if ($email && !$existing->email) {
                $payload['email'] = $email;
            } elseif ($email && $existing->email !== $email) {
                $this->warnings[] = "Row {$rowNum}: Email differs from existing record — kept original.";
            }
            $existing->update($payload);
            $this->updated++;
        } else {
            // New employee — need a valid unique email
            if ($email && Employee::where('email', $email)->exists()) {
                $this->warnings[] = "Row {$rowNum}: Email '{$email}' already in use — placeholder assigned.";
                $email = '';
            }
            // Generate placeholder if email missing
            if (empty($email)) {
                $email = strtolower(preg_replace('/[^a-z0-9]/i', '', $empCode)) . '@import.local';
                $this->warnings[] = "Row {$rowNum}: No valid email — placeholder '{$email}' assigned. Update it later.";
            }
            Employee::create(array_merge($payload, [
                'employee_code'  => $empCode,
                'email'          => $email,
                'fixed_salary'   => 0,
                'variable_salary'=> 0,
            ]));
            $this->imported++;
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function findByNameOrId(Collection $collection, string $value): mixed
    {
        // Try name match (case-insensitive)
        $found = $collection->first(fn($item) => strtolower($item->name) === strtolower($value));
        if ($found) return $found;

        // Try numeric ID match
        if (is_numeric($value)) {
            return $collection->firstWhere('id', (int) $value);
        }

        // Partial name match (starts-with)
        return $collection->first(fn($item) => str_starts_with(strtolower($item->name), strtolower($value)));
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;

        // Excel numeric serial date
        if (is_numeric($value) && $value > 1000) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $value);
                return $dt->format('Y-m-d');
            } catch (\Throwable) {}
        }

        // String date — try multiple formats
        $str = trim((string) $value);
        foreach (['d/m/Y', 'd-m-Y', 'd/m/y', 'd-m-y', 'Y-m-d', 'm/d/Y', 'd.m.Y', 'Y/m/d'] as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, $str);
                if ($dt && $dt->year > 1900 && $dt->year < 2100) {
                    return $dt->format('Y-m-d');
                }
            } catch (\Throwable) {}
        }

        // Last resort — Carbon::parse
        try {
            $dt = Carbon::parse($str);
            if ($dt->year > 1900 && $dt->year < 2100) {
                return $dt->format('Y-m-d');
            }
        } catch (\Throwable) {}

        return null;
    }
}
