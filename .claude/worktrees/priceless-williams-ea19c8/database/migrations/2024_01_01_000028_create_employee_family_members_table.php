<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('relationship', 50); // Father, Mother, Spouse, Son, Daughter, etc.
            $table->date('dob')->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('contact_number', 30)->nullable();
            $table->enum('dependency_status', ['dependent', 'independent'])->default('dependent');
            $table->timestamps();

            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_family_members');
    }
};
