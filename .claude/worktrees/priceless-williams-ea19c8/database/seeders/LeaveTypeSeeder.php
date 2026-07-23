<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Annual Leave',   'days_allowed' => 18, 'carry_forward' => true,  'is_paid' => true,  'status' => 'active'],
            ['name' => 'Sick Leave',     'days_allowed' => 12, 'carry_forward' => false, 'is_paid' => true,  'status' => 'active'],
            ['name' => 'Casual Leave',   'days_allowed' => 6,  'carry_forward' => false, 'is_paid' => true,  'status' => 'active'],
            ['name' => 'Unpaid Leave',   'days_allowed' => 0,  'carry_forward' => false, 'is_paid' => false, 'status' => 'active'],
            ['name' => 'Maternity Leave','days_allowed' => 90, 'carry_forward' => false, 'is_paid' => true,  'status' => 'active'],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}
