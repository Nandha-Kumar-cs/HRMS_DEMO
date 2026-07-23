<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add is_comp_off flag to leave_types ────────────────────────────
        Schema::table('leave_types', function (Blueprint $table) {
            $table->boolean('is_comp_off')->default(false)->after('is_paid');
        });

        // ── 2. Create comp_off_credits table ──────────────────────────────────
        Schema::create('comp_off_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->enum('day_type', ['sunday', 'saturday', 'public_holiday']);
            $table->string('holiday_name')->nullable();
            $table->enum('status', ['credited', 'cancelled'])->default('credited');
            $table->timestamps();

            // One credit record per employee per date
            $table->unique(['employee_id', 'work_date'], 'comp_off_emp_date_unique');
            $table->index('work_date');
            $table->index('status');
        });

        // ── 3. Seed the "Comp Off" leave type (skip if already exists) ────────
        $exists = DB::table('leave_types')->where('is_comp_off', 1)->exists();
        if (!$exists) {
            DB::table('leave_types')->insert([
                'name'          => 'Comp Off',
                'days_allowed'  => 365,
                'carry_forward' => 1,
                'is_paid'       => 1,   // no salary deduction
                'is_comp_off'   => 1,
                'status'        => 'active',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comp_off_credits');

        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn('is_comp_off');
        });
    }
};
