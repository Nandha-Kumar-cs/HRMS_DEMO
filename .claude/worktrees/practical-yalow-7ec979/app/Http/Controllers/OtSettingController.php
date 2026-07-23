<?php

namespace App\Http\Controllers;

use App\Helpers\AppSettings;
use App\Models\Setting;
use Illuminate\Http\Request;

class OtSettingController extends Controller
{
    public function show()
    {
        $triggerTime     = AppSettings::getOtTriggerTime();
        $baselineTime    = AppSettings::getOtBaselineTime();
        $officeStartTime = AppSettings::getOfficeStartTime();

        return view('settings.ot', compact('triggerTime', 'baselineTime', 'officeStartTime'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'ot_trigger_time'   => ['required', 'date_format:H:i'],
            'ot_baseline_time'  => ['required', 'date_format:H:i'],
            'office_start_time' => ['required', 'date_format:H:i'],
        ]);

        // Trigger must be strictly after baseline
        $triggerMins  = AppSettings::timeToMinsPublic($data['ot_trigger_time']);
        $baselineMins = AppSettings::timeToMinsPublic($data['ot_baseline_time']);
        if ($triggerMins <= $baselineMins) {
            return back()->withInput()
                ->withErrors(['ot_trigger_time' => 'OT Trigger time must be later than Baseline time.']);
        }

        foreach ($data as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
        }

        // Flush static cache so next request reads fresh values
        AppSettings::flush();

        return back()->with('success', 'OT & attendance settings saved successfully.');
    }
}
