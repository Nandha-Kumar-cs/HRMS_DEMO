<?php

namespace App\Helpers;

use App\Models\Holiday;
use Carbon\Carbon;

class WorkCalendar
{
    /**
     * Returns true if the given date is a working day:
     *   NOT Sunday, NOT 1st/3rd Saturday, NOT a DB holiday.
     */
    public static function isWorkingDay(Carbon $date): bool
    {
        if ($date->isSunday()) return false;
        if (static::isFirstOrThirdSaturday($date)) return false;
        if (Holiday::where('date', $date->toDateString())->exists()) return false;
        return true;
    }

    /**
     * Returns true if the given date is the 1st or 3rd Saturday of its month.
     */
    public static function isFirstOrThirdSaturday(Carbon $date): bool
    {
        if (!$date->isSaturday()) return false;

        // Count how many Saturdays have occurred in this month up to and including $date
        $satCount = 0;
        $d = $date->copy()->startOfMonth();
        while ($d->lte($date)) {
            if ($d->isSaturday()) $satCount++;
            $d->addDay();
        }

        return in_array($satCount, [1, 3], true);
    }

    /**
     * Count working days between $start and $end (inclusive),
     * excluding Sundays, 1st/3rd Saturdays, and DB holidays.
     */
    public static function countWorkingDays(Carbon $start, Carbon $end): int
    {
        // Load holidays in range once
        $holidays = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->flip() // use as a hash-map for O(1) lookup
            ->toArray();

        $count = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if ($d->isSunday()) continue;
            if (static::isFirstOrThirdSaturday($d)) continue;
            if (isset($holidays[$d->toDateString()])) continue;
            $count++;
        }
        return $count;
    }

    /**
     * Returns a flat list of all non-working day dates (as date strings)
     * in the given range: Sundays, 1st/3rd Saturdays, and DB holidays.
     *
     * Returns an associative array: ['YYYY-MM-DD' => 'label', ...]
     */
    public static function getNonWorkingDates(Carbon $start, Carbon $end): array
    {
        $dbHolidays = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('name', 'date')
            ->mapWithKeys(fn($name, $date) => [Carbon::parse($date)->toDateString() => $name])
            ->toArray();

        $result = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->toDateString();
            if ($d->isSunday()) {
                $result[$key] = 'Sunday';
            } elseif (static::isFirstOrThirdSaturday($d)) {
                // Determine 1st vs 3rd
                $satCount = 0;
                $tmp = $d->copy()->startOfMonth();
                while ($tmp->lte($d)) {
                    if ($tmp->isSaturday()) $satCount++;
                    $tmp->addDay();
                }
                $result[$key] = $satCount === 1 ? '1st Saturday' : '3rd Saturday';
            } elseif (isset($dbHolidays[$key])) {
                $result[$key] = $dbHolidays[$key];
            }
        }
        return $result;
    }

    /**
     * Returns all 1st and 3rd Saturdays for the given year
     * as an array of ['date' => 'YYYY-MM-DD', 'label' => '1st/3rd Saturday — Month'].
     */
    public static function getFirstAndThirdSaturdays(int $year): array
    {
        $result = [];
        for ($month = 1; $month <= 12; $month++) {
            $start = Carbon::create($year, $month, 1);
            $end   = $start->copy()->endOfMonth();
            $satCount = 0;
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                if ($d->isSaturday()) {
                    $satCount++;
                    if (in_array($satCount, [1, 3], true)) {
                        $label = ($satCount === 1 ? '1st' : '3rd') . ' Saturday — ' . $d->format('F');
                        $result[] = ['date' => $d->toDateString(), 'label' => $label];
                    }
                }
            }
        }
        return $result;
    }
}
