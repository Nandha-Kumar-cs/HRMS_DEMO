<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefit_fund_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->string('color', 20)->default('info');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        DB::table('benefit_fund_types')->insert([
            ['name' => 'Child Education Fund', 'description' => 'Support for employees children education', 'color' => 'primary', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Medical Support Fund', 'description' => 'Medical aid for employees and family',     'color' => 'danger',  'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Family Welfare Fund',  'description' => 'General family welfare assistance',        'color' => 'success', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Insurance Benefit',    'description' => 'Group insurance contribution',             'color' => 'info',    'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Scholarship Support',  'description' => 'Higher education scholarships',            'color' => 'warning', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('benefit_fund_types');
    }
};
