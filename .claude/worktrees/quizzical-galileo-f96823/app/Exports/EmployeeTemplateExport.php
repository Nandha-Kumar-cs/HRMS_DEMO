<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'Empcode',
            'Enroll No',
            'Name',
            'Father',
            'Address',
            'CountryCode',
            'Mobile',
            'Email',
            'CompID',
            'DeptID',
            'DesgID',
            'CatID',
            'ActiveStatus(Active/De Active)',
            'Shift',
            'Auto Shift(A/B/C)',
            'WO1',
            'WO2',
            'Sat Off',
            'Sat Half Day',
            'Status(T/F)',
            'DOJ',
            'ENT(1/2)',
            'CountryCode1',
            'Mobile1',
            'DOB',
            'DOR',
        ];
    }

    public function array(): array
    {
        // One sample row so the user knows the expected format
        return [
            [
                'EMP001',          // Empcode
                '',                // Enroll No
                'John Doe',        // Name
                '',                // Father
                '',                // Address
                '',                // CountryCode
                '9999999999',      // Mobile
                'john@example.com',// Email
                '',                // CompID
                'IT',              // DeptID  — use department name from your system
                'Manager',         // DesgID  — use designation name from your system
                '',                // CatID
                'Active',          // ActiveStatus: Active / De Active
                '',                // Shift
                '',                // Auto Shift
                '',                // WO1
                '',                // WO2
                '',                // Sat Off
                '',                // Sat Half Day
                '',                // Status(T/F)
                '01/01/2024',      // DOJ  dd/mm/yyyy
                '',                // ENT
                '',                // CountryCode1
                '',                // Mobile1
                '15/05/1990',      // DOB  dd/mm/yyyy
                '',                // DOR
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '1e293b']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
