<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('ot_hours', 5, 2)->nullable()->default(null)->after('working_hours')
                  ->comment('Manually entered overtime hours for this day');
            $table->decimal('ot_amount', 10, 2)->nullable()->default(null)->after('ot_hours')
                  ->comment('Manually entered or auto-calculated OT amount for this day');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['ot_hours', 'ot_amount']);
        });
    }
};
