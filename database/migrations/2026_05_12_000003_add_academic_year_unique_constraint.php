<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add unique constraint on is_active (only one active at a time)
        // SQLite doesn't support adding UNIQUE on existing column easily,
        // so we use a trigger approach or handle it at application level.
        // For now, we'll add a unique index on is_active where it's true
        
        // Note: This is handled at application level in AcademicYearController
        // We'll add a unique index as a safety net for future migrations
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS academic_years_is_active_unique
            ON academic_years (is_active) WHERE is_active = true');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS academic_years_is_active_unique');
    }
};