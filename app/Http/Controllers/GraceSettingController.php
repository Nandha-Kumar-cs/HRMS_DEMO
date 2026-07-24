<?php

namespace App\Http\Controllers;

use App\Helpers\AppSettings;
use App\Models\Attendance;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GraceSettingController extends Controller
{
    public function show()
    {
        $dailyGraceMinutes   = AppSettings::getDailyGraceMinutes();
        $monthlyGraceMinutes = AppSettings::getMonthlyGraceMinutes();
        $officeStartTime     = AppSettings::getOfficeStartTime();

        return view('settings.grace', compact(
            'dailyGraceMinutes', 'monthlyGraceMinutes', 'officeStartTime'
        ));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'daily_grace_minutes'   => ['required', 'integer', 'min:0', 'max:180'],
            'monthly_grace_minutes' => ['required', 'integer', 'min:0', 'max:480'],
        ]);

        foreach ($data as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
        }

        AppSettings::flush();

        $updated = $this->recalculateAttendanceStatuses();

        return back()->with('success', "Grace settings saved. {$updated} attendance records recalculated.");
    }

    /**
     * Recalculate present/late/half_day status for all records based on current grace setting.
     * Rules (in priority order):
     *   1. check_in >= 11:00 AM → half_day
     *   2. worked < 4 hours (working_hours > 0 and < 4) → half_day
     *   3. check_in > (office_start + grace) → late
     *   4. otherwise → present
     * Never touches on_leave, comp_off, on_duty, or absent.
     */
    public static function recalculateAttendanceStatuses(): int
    {
        $thresholdMin = AppSettings::getOfficeStartMins() + AppSettings::getDailyGraceMinutes();
        $halfDayCheckin = 11 * 60; // 11:00 AM in minutes

        $affected = ['present', 'late', 'half_day'];

        // Rule 1: check-in >= 11:00 AM → half_day
        $r1 = DB::table('attendances')
            ->whereIn('status', $affected)
            ->whereNotNull('check_in')
            ->whereRaw('(HOUR(check_in) * 60 + MINUTE(check_in)) >= ?', [$halfDayCheckin])
            ->update(['status' => 'half_day', 'updated_at' => now()]);

        // Rule 2: worked < 4 hours (and check-in < 11 AM) → half_day
        $r2 = DB::table('attendances')
            ->whereIn('status', $affected)
            ->whereNotNull('check_in')
            ->whereRaw('(HOUR(check_in) * 60 + MINUTE(check_in)) < ?', [$halfDayCheckin])
            ->whereNotNull('working_hours')
            ->whereRaw('working_hours > 0 AND working_hours < 4')
            ->update(['status' => 'half_day', 'updated_at' => now()]);

        // Rule 3: check-in > threshold (and check-in < 11 AM, work hours OK) → late
        $r3 = DB::table('attendances')
            ->whereIn('status', ['present', 'late'])
            ->whereNotNull('check_in')
            ->whereRaw('(HOUR(check_in) * 60 + MINUTE(check_in)) < ?', [$halfDayCheckin])
            ->whereRaw('(HOUR(check_in) * 60 + MINUTE(check_in)) > ?', [$thresholdMin])
            ->update(['status' => 'late', 'updated_at' => now()]);

        // Rule 4: everything else with check_in < 11 AM and within threshold → present
        $r4 = DB::table('attendances')
            ->whereIn('status', ['present', 'late'])
            ->whereNotNull('check_in')
            ->whereRaw('(HOUR(check_in) * 60 + MINUTE(check_in)) < ?', [$halfDayCheckin])
            ->whereRaw('(HOUR(check_in) * 60 + MINUTE(check_in)) <= ?', [$thresholdMin])
            ->update(['status' => 'present', 'updated_at' => now()]);

        return $r1 + $r2 + $r3 + $r4;
    }
}
