<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_loan_id')->constrained('employee_loans')->cascadeOnDelete();
            $table->decimal('amount_paid', 12, 2);
            $table->date('payment_date');
            $table->unsignedBigInteger('salary_slip_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('salary_slip_id')->references('id')->on('salary_slips')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
    }
};
