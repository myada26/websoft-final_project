<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// [Lab 7] Prompt 4 — three genuinely missing indexes confirmed absent after auditing
// all existing migrations. See prompt spec for the full list of indexes that already exist.
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. idx_txn_type_filter ────────────────────────────────────────────
        // Purpose: FR-0024 collection summary queries filtered by transaction_type
        // (e.g. "show only FEE transactions for this org this semester").
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(
                ['organization_id', 'transaction_type', 'is_void'],
                'idx_txn_type_filter'
            );
        });

        // ── 2. idx_enroll_semester_program ────────────────────────────────────
        // Purpose: FR-0014 POS enrolled-student listing filtered by semester + program.
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->index(
                ['academic_year_id', 'program_id'],
                'idx_enroll_semester_program'
            );
        });

        // ── 3. idx_students_gin_trgm ──────────────────────────────────────────
        // Purpose: ILIKE full-text student search (name + student_number).
        // Blueprint has no GIN/trigram support — raw SQL required (PostgreSQL only).
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement("
            CREATE INDEX idx_students_gin_trgm ON students
            USING GIN ((first_name || ' ' || last_name || ' ' || student_number) gin_trgm_ops)
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_txn_type_filter');
        DB::statement('DROP INDEX IF EXISTS idx_enroll_semester_program');
        DB::statement('DROP INDEX IF EXISTS idx_students_gin_trgm');
    }
};
