<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::firstOrCreate(
            ['key' => 'ot_trigger_time'],
            ['value' => '20:30', 'label' => 'OT Trigger Time',  'type' => 'time', 'group' => 'ot',
             'description' => 'Minimum checkout time for OT eligibility (e.g. 20:30 = 8:30 PM)']
        );
        Setting::firstOrCreate(
            ['key' => 'ot_baseline_time'],
            ['value' => '18:15', 'label' => 'OT Baseline Time', 'type' => 'time', 'group' => 'ot',
             'description' => 'OT hours are counted from this time onwards (e.g. 18:15 = 6:15 PM)']
        );
    }

    public function down(): void
    {
        \DB::table('settings')->whereIn('key', ['ot_trigger_time', 'ot_baseline_time'])->delete();
    }
};
