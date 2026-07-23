<?php

namespace App\Imports;

use App\Models\Holiday;
use App\Models\HolidayType;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class HolidayImport implements ToCollection
{
    public int $imported = 0;
    public int $skipped  = 0;
    public array $errors = [];

    private int $year;
    private ?int $typeId;

    public function __construct(int $year, ?int $typeId = null)
    {
        $this->year   = $year;
        $this->typeId = $typeId ?: HolidayType::where('name', 'Public')->value('id');
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            // Skip header row (if first cell looks non-numeric)
            if ($index === 0 && !is_numeric($row[0])) {
                continue;
            }

            $month = (int) ($row[0] ?? 0);
            $day   = (int) ($row[1] ?? 0);
            $name  = trim((string) ($row[2] ?? ''));

            if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
                $this->errors[] = 'Row ' . ($index + 1) . ": invalid month ({$month}) or day ({$day}) — skipped.";
                $this->skipped++;
                continue;
            }

            try {
                $date = Carbon::create($this->year, $month, $day)->toDateString();
            } catch (\Throwable) {
                $this->errors[] = 'Row ' . ($index + 1) . ": date {$this->year}-{$month}-{$day} is invalid — skipped.";
                $this->skipped++;
                continue;
            }

            Holiday::updateOrCreate(
                ['date' => $date],
                [
                    'name'            => $name ?: 'Holiday',
                    'holiday_type_id' => $this->typeId,
                    'source'          => 'import',
                ]
            );

            $this->imported++;
        }
    }
}
