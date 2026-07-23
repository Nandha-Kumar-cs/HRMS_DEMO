<?php
namespace App\Http\Controllers;

use App\Helpers\AppSettings;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /** OT settings page */
    public function otIndex()
    {
        $triggerTime  = AppSettings::getOtTriggerTime();
        $baselineTime = AppSettings::getOtBaselineTime();
        return view('settings.ot', compact('triggerTime', 'baselineTime'));
    }

    /** Save OT settings */
    public function otUpdate(Request $request)
    {
        $request->validate([
            'ot_trigger_time'  => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'ot_baseline_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
        ], [
            'ot_trigger_time.regex'  => 'OT Trigger Time must be in HH:MM format.',
            'ot_baseline_time.regex' => 'OT Baseline Time must be in HH:MM format.',
        ]);

        // Additional logical validation: trigger must be after baseline
        $triggerMins  = self::timeToMins($request->ot_trigger_time);
        $baselineMins = self::timeToMins($request->ot_baseline_time);

        if ($triggerMins <= $baselineMins) {
            return back()->withErrors(['ot_trigger_time' => 'OT Trigger Time must be later than the Baseline Time.'])->withInput();
        }

        AppSettings::set('ot_trigger_time',  $request->ot_trigger_time);
        AppSettings::set('ot_baseline_time', $request->ot_baseline_time);
        AppSettings::flush();

        return back()->with('success', 'OT settings updated successfully.');
    }

    private static function timeToMins(string $time): int
    {
        $parts = explode(':', $time);
        return (int)($parts[0] ?? 0) * 60 + (int)($parts[1] ?? 0);
    }
}
