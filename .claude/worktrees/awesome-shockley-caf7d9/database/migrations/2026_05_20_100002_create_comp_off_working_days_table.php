<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comp_off_working_days', function (Blueprint $table) {
            $table->id();
            $table->date('work_date')->unique();                      // the holiday/off-day worked
            $table->enum('day_type', ['sunday', 'saturday', 'public_holiday']);
            $table->string('holiday_name')->nullable();               // e.g. "Diwali", "Sunday"
            $table->string('reason')->nullable();                     // admin note: why company worked
            $table->foreignId('declared_by')->nullable()             // who declared it
                  ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('work_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comp_off_working_days');
    }
};
