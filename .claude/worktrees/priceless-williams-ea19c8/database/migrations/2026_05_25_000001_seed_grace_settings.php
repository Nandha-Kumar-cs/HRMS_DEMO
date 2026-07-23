<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            [
                'key'         => 'daily_grace_minutes',
                'value'       => '15',
                'label'       => 'Daily Late Grace (minutes)',
                'description' => 'Minutes after office start before check-in is counted as late. E.g. 15 means 09:15 is the late threshold when office starts at 09:00.',
                'type'        => 'integer',
                'group'       => 'grace',
            ],
            [
                'key'         => 'monthly_grace_minutes',
                'value'       => '90',
                'label'       => 'Monthly Late Permission (minutes)',
                'description' => 'Total late minutes allowed per month before the 2× deduction penalty applies.',
                'type'        => 'integer',
                'group'       => 'grace',
            ],
        ];

        foreach ($rows as $row) {
            DB::table('settings')->updateOrInsert(['key' => $row['key']], $row);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['daily_grace_minutes', 'monthly_grace_minutes'])->delete();
    }
};
