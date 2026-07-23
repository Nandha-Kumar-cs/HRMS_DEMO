<?php

namespace App\Http\Controllers;

use App\Helpers\AppSettings;
use App\Models\Setting;
use Illuminate\Http\Request;

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
            'daily_grace_minutes'   => ['required', 'integer', 'min:0', 'max:120'],
            'monthly_grace_minutes' => ['required', 'integer', 'min:0', 'max:480'],
        ]);

        foreach ($data as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
        }

        AppSettings::flush();

        return back()->with('success', 'Grace & late permission settings saved successfully.');
    }
}
