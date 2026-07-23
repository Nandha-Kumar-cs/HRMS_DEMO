<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('color', 20)->default('secondary'); // bootstrap color
            $table->timestamps();
        });

        // Seed default types
        DB::table('holiday_types')->insert([
            ['name' => 'Public',    'color' => 'danger',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'National',  'color' => 'primary', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Religious', 'color' => 'warning', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Festival',  'color' => 'success', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Optional',  'color' => 'secondary','created_at'=> now(), 'updated_at' => now()],
        ]);

        // Add holiday_type_id to holidays table
        Schema::table('holidays', function (Blueprint $table) {
            $table->foreignId('holiday_type_id')
                ->nullable()
                ->after('name')
                ->constrained('holiday_types')
                ->nullOnDelete();
        });

        // Migrate existing data: type='public' → Public, type='optional' → Optional
        $publicId   = DB::table('holiday_types')->where('name', 'Public')->value('id');
        $optionalId = DB::table('holiday_types')->where('name', 'Optional')->value('id');

        DB::table('holidays')->where('type', 'public')->update(['holiday_type_id' => $publicId]);
        DB::table('holidays')->where('type', 'optional')->update(['holiday_type_id' => $optionalId]);

        // Drop the old enum column
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->enum('type', ['public', 'optional'])->default('public')->after('name');
            $table->dropForeign(['holiday_type_id']);
            $table->dropColumn('holiday_type_id');
        });

        Schema::dropIfExists('holiday_types');
    }
};
