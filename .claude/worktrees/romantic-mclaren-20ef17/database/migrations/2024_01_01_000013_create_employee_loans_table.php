<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('type', ['loan', 'advance'])->default('loan');
            $table->decimal('amount', 12, 2);
            $table->decimal('interest_rate', 5, 2)->default(0)->nullable();
            $table->date('date_given');
            $table->decimal('monthly_deduction', 12, 2);
            $table->unsignedInteger('total_months');
            $table->unsignedInteger('paid_months')->default(0);
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};
