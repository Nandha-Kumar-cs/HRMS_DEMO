<?php

namespace Database\Seeders;

use App\Models\SalaryComponent;
use Illuminate\Database\Seeder;

class AdditionalSalaryComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            ['name' => 'Medical Allowance', 'type' => 'allowance', 'calculation_type' => 'fixed',      'value' => 1250],
            ['name' => 'Conveyance',         'type' => 'allowance', 'calculation_type' => 'fixed',      'value' => 1600],
            ['name' => 'ESI',                'type' => 'deduction', 'calculation_type' => 'percentage', 'value' => 0.75],
            ['name' => 'Professional Tax',   'type' => 'deduction', 'calculation_type' => 'fixed',      'value' => 200],
        ];

        foreach ($components as $component) {
            SalaryComponent::firstOrCreate(['name' => $component['name']], $component);
        }
    }
}
