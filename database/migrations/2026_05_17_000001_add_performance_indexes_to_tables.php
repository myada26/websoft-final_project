<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NFR-001 / NFR-002 / NFR-013 — Performance indexes.
 *
 * Only adds indexes on columns that are frequently used in WHERE / JOIN clauses
 * but have no existing index. Columns already covered by unique constraints or
 * composite indexes in earlier migrations are intentionally omitted.
 *
 * Skipped (already indexed):
 *   students.student_number               — unique + explicit index (migration 000005)
 *   student_enrollments.(student_id, academic_year_id) — unique + index (migration 000010)
 *   transactions.(student_id, academic_year_id)        — index (migration 000014)
 *   transactions.(academic_year_id, created_at)        — index (migration 000014)
 *   transactions.(organization_id, is_void, …)         — index (migration 000014)
 *   events.(organization_id, status/academic_year_id)  — indexes (migration 010001)
 *   audit_logs action+ts / entity+ts                   — indexes (migration 200005)
 */
return new class extends Migration
{
    public function up(): void
    {
        // fee_profiles: POS transaction create + org fee-profile list
        Schema::table('fee_profiles', function (Blueprint $table) {
            $table->index(['organization_id', 'is_active'], 'fee_profiles_org_active_idx');
        });

        // users: scoping org users everywhere (officer dropdown, user management)
        Schema::table('users', function (Blueprint $table) {
            $table->index('organization_id', 'users_org_idx');
        });

        // audit_logs: eager-loading user on dashboard / audit-log listing
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('user_id', 'audit_logs_user_idx');
        });

        // remittances: status-count / status-amount summary in RemittanceController
        Schema::table('remittances', function (Blueprint $table) {
            $table->index(['organization_id', 'status'], 'remittances_org_status_idx');
        });

        // transactions: officer filter in transaction index (processed_by_user_id)
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('processed_by_user_id', 'transactions_officer_idx');
        });

        // student_fines: bulk-update to PAID after fine transaction is recorded
        Schema::table('student_fines', function (Blueprint $table) {
            $table->index('transaction_id', 'student_fines_txn_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fee_profiles', function (Blueprint $table) {
            $table->dropIndex('fee_profiles_org_active_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_org_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_user_idx');
        });

        Schema::table('remittances', function (Blueprint $table) {
            $table->dropIndex('remittances_org_status_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_officer_idx');
        });

        Schema::table('student_fines', function (Blueprint $table) {
            $table->dropIndex('student_fines_txn_idx');
        });
    }
};
