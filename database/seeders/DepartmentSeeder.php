<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = ['HR', 'IT', 'Finance', 'Operations', 'Sales', 'Marketing'];
        foreach ($departments as $name) {
            Department::firstOrCreate(['name' => $name], ['status' => 'active']);
        }
    }
}
