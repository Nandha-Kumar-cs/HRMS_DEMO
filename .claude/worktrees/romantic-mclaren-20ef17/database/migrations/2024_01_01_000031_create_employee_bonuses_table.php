<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'monthly_bonus',
                'performance',
                'festival',
                'overtime',
                'one_time',
            ]);
            $table->decimal('amount', 12, 2);
            $table->string('reason', 255);
            $table->tinyInteger('payroll_month'); // 1-12
            $table->smallInteger('payroll_year'); // e.g. 2026
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->timestamps();

            $table->index(['employee_id', 'payroll_year', 'payroll_month']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_bonuses');
    }
};
