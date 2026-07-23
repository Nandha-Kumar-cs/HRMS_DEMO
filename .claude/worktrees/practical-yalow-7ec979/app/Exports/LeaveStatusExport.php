<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaveStatusExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    public function __construct(
        private readonly mixed $employees,
        private readonly array $grid,
        private readonly int   $year
    ) {}

    public function collection(): Collection
    {
        $rows = collect();
        $i    = 1;

        foreach ($this->employees as $emp) {
            $row = [
                $i++,
                $emp->employee_code,
                $emp->full_name,
                $emp->department?->name ?? '—',
            ];

            for ($m = 1; $m <= 12; $m++) {
                $cell = $this->grid[$emp->id][$m] ?? ['days' => 0, 'balance' => 1];
                // Show balance: +1, 0, -1, -2 …
                $row[] = $cell['balance'];
            }

            // Annual balance
            $annual = $this->grid[$emp->id]['annual'] ?? ['balance' => 12];
            $row[]  = $annual['balance'];

            $rows->push($row);
        }

        return $rows;
    }

    public function headings(): array
    {
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = date('M', mktime(0, 0, 0, $m, 1));
        }

        return array_merge(
            ['#', 'Emp Code', 'Employee Name', 'Department'],
            $months,
            ['Annual Balance']
        );
    }

    public function styles(Worksheet $sheet): array
    {
        // Header
        $lastCol = 'R'; // D(4) + 12 months + 1 annual = col R
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e293b']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Color-code balance cells (columns E to R, rows 2+)
        $lastRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $lastRow; $row++) {
            for ($col = 5; $col <= 17; $col++) {  // E(5) through Q(17) = 13 cols (12 months + annual)
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $val = $sheet->getCell("{$colLetter}{$row}")->getValue();

                if ($val > 0) {
                    $sheet->getStyle("{$colLetter}{$row}")->getFont()->getColor()->setRGB('16a34a');
                } elseif ($val < 0) {
                    $sheet->getStyle("{$colLetter}{$row}")->getFont()->getColor()->setRGB('dc2626');
                    $sheet->getStyle("{$colLetter}{$row}")->getFont()->setBold(true);
                }

                $sheet->getStyle("{$colLetter}{$row}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        // Freeze first 4 cols + header row
        $sheet->freezePane('E2');
        $sheet->setAutoFilter("A1:{$lastCol}1");

        return [];
    }

    public function title(): string
    {
        return "Leave Status {$this->year}";
    }
}
