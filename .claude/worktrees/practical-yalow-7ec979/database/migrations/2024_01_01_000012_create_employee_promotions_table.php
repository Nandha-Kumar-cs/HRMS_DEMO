<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedBigInteger('previous_designation_id')->nullable();
            $table->unsignedBigInteger('new_designation_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->date('effective_date');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('previous_designation_id')->references('id')->on('designations')->nullOnDelete();
            $table->foreign('new_designation_id')->references('id')->on('designations')->restrictOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_promotions');
    }
};
