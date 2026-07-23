<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $designations = ['Manager', 'Senior Executive', 'Executive', 'Intern'];
        $departments  = Department::all();

        foreach ($departments as $dept) {
            foreach ($designations as $title) {
                Designation::firstOrCreate(
                    ['name' => $title, 'department_id' => $dept->id],
                    ['status' => 'active']
                );
            }
        }
    }
}
