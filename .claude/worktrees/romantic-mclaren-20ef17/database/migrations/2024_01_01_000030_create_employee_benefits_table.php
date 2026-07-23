<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('benefit_fund_type_id')->constrained('benefit_fund_types')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('effective_month'); // Use 1st-of-month convention
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('description')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'effective_month']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_benefits');
    }
};
