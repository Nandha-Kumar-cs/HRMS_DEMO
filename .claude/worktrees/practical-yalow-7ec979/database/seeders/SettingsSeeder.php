<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder {
    public function run(): void {
        $rows = [
            [
                'key'         => 'ot_trigger_time',
                'value'       => '20:30',
                'label'       => 'OT Trigger Time',
                'description' => 'Minimum checkout time (HH:MM, 24-hr) for an employee to be eligible for OT calculation.',
                'type'        => 'time',
                'group'       => 'ot',
            ],
            [
                'key'         => 'ot_baseline_time',
                'value'       => '18:15',
                'label'       => 'OT Baseline Time',
                'description' => 'OT hours are counted from this time onwards (HH:MM, 24-hr). Typically office closing time.',
                'type'        => 'time',
                'group'       => 'ot',
            ],
        ];
        foreach ($rows as $row) {
            DB::table('settings')->updateOrInsert(['key' => $row['key']], $row + ['created_at' => now(), 'updated_at' => now()]);
        }
    }
}
