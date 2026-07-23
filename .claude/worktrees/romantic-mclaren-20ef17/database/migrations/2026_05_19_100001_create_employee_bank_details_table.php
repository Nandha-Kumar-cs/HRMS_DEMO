<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employee_bank_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained()->onDelete('cascade');
            $table->string('bank_name', 100);
            $table->string('account_holder_name', 150);
            $table->string('account_number', 30)->unique();
            $table->string('ifsc_code', 20);
            $table->string('branch_name', 100);
            $table->string('upi_id', 100)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('employee_bank_details'); }
};
