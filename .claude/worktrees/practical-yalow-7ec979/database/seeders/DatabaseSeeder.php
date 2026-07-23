<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            DepartmentSeeder::class,
            DesignationSeeder::class,
            SalaryComponentSeeder::class,
            LeaveTypeSeeder::class,
            SettingsSeeder::class,
        ]);
    }
}
