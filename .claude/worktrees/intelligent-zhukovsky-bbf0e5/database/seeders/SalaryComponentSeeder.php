<?php

namespace Database\Seeders;

use App\Models\SalaryComponent;
use Illuminate\Database\Seeder;

class SalaryComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            ['name' => 'HRA',   'type' => 'allowance', 'calculation_type' => 'percentage', 'value' => 20],
            ['name' => 'Basic', 'type' => 'allowance', 'calculation_type' => 'percentage', 'value' => 40],
            ['name' => 'TA',    'type' => 'allowance', 'calculation_type' => 'fixed',      'value' => 1500],
            ['name' => 'PF',    'type' => 'deduction', 'calculation_type' => 'percentage', 'value' => 5],
            ['name' => 'TDS',   'type' => 'deduction', 'calculation_type' => 'percentage', 'value' => 10],
        ];

        foreach ($components as $component) {
            SalaryComponent::firstOrCreate(['name' => $component['name']], $component);
        }
    }
}
