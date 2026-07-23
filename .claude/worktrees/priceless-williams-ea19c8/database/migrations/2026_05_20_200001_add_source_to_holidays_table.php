<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            // 'manual'   = admin typed it in the Add form
            // 'national' = admin clicked Quick Add national holiday button
            // 'import'   = admin imported via Excel
            $table->enum('source', ['manual', 'national', 'import'])
                  ->default('manual')
                  ->after('holiday_type_id');
        });

        // Tag all existing holidays as 'manual' (safest assumption — they were all entered before this feature)
        DB::table('holidays')->update(['source' => 'manual']);
    }

    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
