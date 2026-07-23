<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comp_offs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('holiday_date');
            $table->string('holiday_name', 150);
            $table->date('availed_date')->nullable();
            $table->enum('status', ['pending', 'availed', 'lapsed'])->default('pending');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'holiday_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comp_offs');
    }
};
