<?php
namespace App\Helpers;

use App\Models\Setting;

class AppSettings
{
    /** In-request static cache to avoid repeated DB hits */
    private static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, self::$cache)) {
            try {
                $row = Setting::where('key', $key)->first();
                self::$cache[$key] = $row?->value;
            } catch (\Throwable) {
                // Table might not exist yet during migrations
                self::$cache[$key] = null;
            }
        }
        $val = self::$cache[$key];
        return $val !== null ? $val : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::where('key', $key)->update(['value' => $value]);
        self::$cache[$key] = $value;
    }

    public static function flush(): void
    {
        self::$cache = [];
    }

    // ── Office hours ─────────────────────────────────────────────────

    /** Office start time as "HH:MM" (default 09:00) */
    public static function getOfficeStartTime(): string
    {
        return self::get('office_start_time', '09:00');
    }

    /** Office start time as minutes-since-midnight (default 09:00 = 540) */
    public static function getOfficeStartMins(): int
    {
        return self::timeToMins(self::getOfficeStartTime());
    }

    // ── OT convenience methods ───────────────────────────────────────

    /** OT trigger checkout time as minutes-since-midnight (default 20:30 = 1230) */
    public static function getOtTriggerMins(): int
    {
        return self::timeToMins(self::get('ot_trigger_time', '20:30'));
    }

    /** OT baseline time as minutes-since-midnight (default 18:15 = 1095) */
    public static function getOtBaselineMins(): int
    {
        return self::timeToMins(self::get('ot_baseline_time', '18:15'));
    }

    /** OT trigger time formatted as HH:MM string */
    public static function getOtTriggerTime(): string
    {
        return self::get('ot_trigger_time', '20:30');
    }

    /** OT baseline time formatted as HH:MM string */
    public static function getOtBaselineTime(): string
    {
        return self::get('ot_baseline_time', '18:15');
    }

    /**
     * Format decimal OT hours as "Xh Ym" for display.
     * e.g. 2.78 → "2h 47m"  (NOT "2.78 hrs" which looks like 2h 78m)
     */
    public static function fmtOtHours(float $decimalHours): string
    {
        $totalMins = (int) round($decimalHours * 60);
        $h = intdiv($totalMins, 60);
        $m = $totalMins % 60;
        return ($h > 0 ? $h . 'h ' : '') . $m . 'm';
    }

    /** Public alias for use in controllers that need to convert a time string. */
    public static function timeToMinsPublic(string $time): int
    {
        return self::timeToMins($time);
    }

    private static function timeToMins(string $time): int
    {
        $parts = explode(':', $time);
        return (int)($parts[0] ?? 0) * 60 + (int)($parts[1] ?? 0);
    }
}
