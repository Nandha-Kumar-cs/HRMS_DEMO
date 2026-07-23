<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_benefits', function (Blueprint $table) {
            // Add frequency mode for recurring benefits
            $table->enum('frequency', ['weekly', 'fortnightly', 'monthly', 'quarterly', 'half_yearly', 'annual'])
                ->default('monthly')
                ->after('benefit_fund_type_id')
                ->comment('Frequency of benefit recurrence');

            // Add date range for recurring benefits (replace effective_month model)
            $table->date('start_date')
                ->nullable()
                ->after('frequency')
                ->comment('Start date for recurring benefit (replaces effective_month)');

            $table->date('end_date')
                ->nullable()
                ->after('start_date')
                ->comment('End date for recurring benefit; null means indefinite/ongoing');

            // Optional: custom benefit name (separate from fund type)
            $table->string('benefit_name')
                ->nullable()
                ->after('end_date')
                ->comment('Custom benefit name; if null, uses fund type name');

            // Add index for efficient queries
            $table->index(['employee_id', 'frequency', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_benefits', function (Blueprint $table) {
            $table->dropIndex(['employee_id', 'frequency', 'status']);
            $table->dropColumn(['frequency', 'start_date', 'end_date', 'benefit_name']);
        });
    }
};
