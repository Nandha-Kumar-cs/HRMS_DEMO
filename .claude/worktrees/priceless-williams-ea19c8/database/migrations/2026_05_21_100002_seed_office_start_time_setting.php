<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::firstOrCreate(
            ['key' => 'office_start_time'],
            [
                'value'       => '09:00',
                'label'       => 'Office Start Time',
                'type'        => 'time',
                'group'       => 'ot',
                'description' => 'Check-in time considered late after this (HH:MM, 24-hr format)',
            ]
        );
    }

    public function down(): void
    {
        \DB::table('settings')->where('key', 'office_start_time')->delete();
    }
};
