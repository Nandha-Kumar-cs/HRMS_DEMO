<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE employee_loans MODIFY COLUMN status ENUM('active','closed','completed') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        // Revert any 'completed' rows back to 'closed' before shrinking the enum
        DB::statement("UPDATE employee_loans SET status = 'closed' WHERE status = 'completed'");
        DB::statement("ALTER TABLE employee_loans MODIFY COLUMN status ENUM('active','closed') NOT NULL DEFAULT 'active'");
    }
};
