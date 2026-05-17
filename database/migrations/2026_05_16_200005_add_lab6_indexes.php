<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // audit_logs: composite indexes for audit dashboard queries (Section 8.3)
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['action', 'timestamp'],                'audit_logs_action_ts_idx');
            $table->index(['entity_type', 'entity_id', 'timestamp'], 'audit_logs_entity_ts_idx');
        });

        // transactions: composite index for daily report generation (Section 8.3)
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['created_at', 'organization_id', 'transaction_type'], 'transactions_report_idx');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_action_ts_idx');
            $table->dropIndex('audit_logs_entity_ts_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_report_idx');
        });
    }
};
