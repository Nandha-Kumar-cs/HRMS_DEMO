<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_name');
            $table->string('asset_type');   // laptop, mouse, keyboard, monitor, id_card, sim_card, mobile, other
            $table->string('serial_number')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['available', 'assigned', 'returned', 'damaged'])->default('available');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_assets');
    }
};
