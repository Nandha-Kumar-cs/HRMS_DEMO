<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extend the attendance status enum to include comp_off and on_duty.
        // Using DB::statement because Laravel's schema builder cannot alter ENUM columns.
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status
            ENUM('present','absent','half_day','late','on_leave','comp_off','on_duty')
            NOT NULL DEFAULT 'absent'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status
            ENUM('present','absent','half_day','late','on_leave')
            NOT NULL DEFAULT 'absent'");
    }
};
