<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('ot_enabled')
                  ->default(false)
                  ->after('variable_salary')
                  ->comment('When true, OT is auto-calculated from check-out time on the attendance sheet');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('ot_enabled');
        });
    }
};
