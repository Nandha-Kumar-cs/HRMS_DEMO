<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->unique();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active');
            $table->date('joining_date')->nullable();
            $table->date('probation_end')->nullable();
            $table->unsignedBigInteger('reporting_manager_id')->nullable();
            $table->decimal('fixed_salary', 12, 2)->default(0);
            $table->decimal('variable_salary', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('reporting_manager_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
