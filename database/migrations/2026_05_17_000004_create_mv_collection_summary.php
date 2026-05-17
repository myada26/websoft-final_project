<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// [Lab 7] Creates the mv_collection_summary materialized view (PostgreSQL only).
// REFRESH MATERIALIZED VIEW CONCURRENTLY requires the unique index created below.
return new class extends Migration // [Lab 7]
{
    public function up(): void
    {
        // Safe to call multiple times — pg_trgm may already exist from the index migration
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        DB::statement("
            CREATE MATERIALIZED VIEW mv_collection_summary AS
            SELECT
                organization_id,
                academic_year_id,
                transaction_type,
                payment_method,
                COUNT(*) AS total_transactions,
                SUM(amount_paid) AS total_collected,
                COUNT(*) FILTER (WHERE is_void = TRUE) AS voided_count
            FROM transactions
            WHERE is_void = FALSE
            GROUP BY organization_id, academic_year_id, transaction_type, payment_method
        ");

        // CONCURRENTLY refresh requires a unique index covering all GROUP BY columns
        DB::statement("
            CREATE UNIQUE INDEX idx_mv_collection_summary_pk
            ON mv_collection_summary (organization_id, academic_year_id, transaction_type, payment_method)
        ");
    }

    public function down(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS mv_collection_summary');
    }
};
