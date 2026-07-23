<?php

namespace App\Helpers;

use App\Models\Holiday;
use Carbon\Carbon;

class WorkCalendar
{
    /**
     * Returns true if the given date is a working day.
     * A date marked is_working_day=true in the holidays table always overrides
     * the Sunday / 1st-3rd-Saturday rule.
     */
    public static function isWorkingDay(Carbon $date): bool
    {
        $holiday = Holiday::where('date', $date->toDateString())->first();

        // Explicit working-day override wins everything
        if ($holiday && $holiday->is_working_day) return true;

        // Standard off rules
        if ($date->isSunday()) return false;
        if (static::isFirstOrThirdSaturday($date)) return false;
        if ($holiday) return false; // holiday with is_working_day=false

        return true;
    }

    /**
     * Returns true if the given date is the 1st or 3rd Saturday of its month.
     */
    public static function isFirstOrThirdSaturday(Carbon $date): bool
    {
        if (!$date->isSaturday()) return false;

        $satCount = 0;
        $d = $date->copy()->startOfMonth();
        while ($d->lte($date)) {
            if ($d->isSaturday()) $satCount++;
            $d->addDay();
        }

        return in_array($satCount, [1, 3], true);
    }

    /**
     * Count working days between $start and $end (inclusive).
     */
    public static function countWorkingDays(Carbon $start, Carbon $end): int
    {
        // Load all holidays in range keyed by date string
        $holidayMap = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn($h) => Carbon::parse($h->date)->toDateString());

        $count = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key     = $d->toDateString();
            $holiday = $holidayMap[$key] ?? null;

            // Explicit working-day override — always count it
            if ($holiday && $holiday->is_working_day) { $count++; continue; }

            if ($d->isSunday()) continue;
            if (static::isFirstOrThirdSaturday($d)) continue;
            if ($holiday) continue; // regular holiday (is_working_day=false)

            $count++;
        }
        return $count;
    }

    /**
     * Returns non-working dates in range as ['YYYY-MM-DD' => 'label'].
     * Dates marked is_working_day=true are excluded.
     */
    public static function getNonWorkingDates(Carbon $start, Carbon $end): array
    {
        $holidayMap = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn($h) => Carbon::parse($h->date)->toDateString());

        $result = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key     = $d->toDateString();
            $holiday = $holidayMap[$key] ?? null;

            // Skip dates explicitly declared as working days
            if ($holiday && $holiday->is_working_day) continue;

            if ($d->isSunday()) {
                $result[$key] = 'Sunday';
            } elseif (static::isFirstOrThirdSaturday($d)) {
                $satCount = 0;
                $tmp = $d->copy()->startOfMonth();
                while ($tmp->lte($d)) {
                    if ($tmp->isSaturday()) $satCount++;
                    $tmp->addDay();
                }
                $result[$key] = $satCount === 1 ? '1st Saturday' : '3rd Saturday';
            } elseif ($holiday) {
                $result[$key] = $holiday->name;
            }
        }
        return $result;
    }

    /**
     * Returns the most recent working day strictly before the given date.
     */
    public static function getPreviousWorkingDay(Carbon $date): Carbon
    {
        $d = $date->copy()->subDay();
        while (!static::isWorkingDay($d)) {
            $d->subDay();
        }
        return $d;
    }

    /**
     * Returns all 1st and 3rd Saturdays for the given year.
     */
    public static function getFirstAndThirdSaturdays(int $year): array
    {
        $result = [];
        for ($month = 1; $month <= 12; $month++) {
            $start    = Carbon::create($year, $month, 1);
            $end      = $start->copy()->endOfMonth();
            $satCount = 0;
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                if ($d->isSaturday()) {
                    $satCount++;
                    if (in_array($satCount, [1, 3], true)) {
                        $label    = ($satCount === 1 ? '1st' : '3rd') . ' Saturday — ' . $d->format('F');
                        $result[] = ['date' => $d->toDateString(), 'label' => $label];
                    }
                }
            }
        }
        return $result;
    }
}
