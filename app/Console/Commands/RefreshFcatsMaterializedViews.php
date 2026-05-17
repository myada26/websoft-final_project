<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * [Lab 7] RefreshFcatsMaterializedViews — rebuilds mv_collection_summary without
 * locking reads (CONCURRENTLY). Scheduled every 30 minutes via Lab6ServiceProvider.
 *
 * Requires the unique index idx_mv_collection_summary_pk to exist (created by the
 * create_mv_collection_summary migration) — CONCURRENTLY will fail without it.
 */
class RefreshFcatsMaterializedViews extends Command // [Lab 7]
{
    protected $signature   = 'fcats:refresh-views';
    protected $description = '[Lab 7] Refresh FCATS PostgreSQL materialized views (CONCURRENTLY — no read lock)';

    public function handle(): int
    {
        $start = now(); // [Lab 7]

        try { // [Lab 7]
            DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY mv_collection_summary'); // [Lab 7]
        } catch (Throwable $e) { // [Lab 7]
            $this->error("[Lab 7] Failed to refresh mv_collection_summary: {$e->getMessage()}"); // [Lab 7]
            return self::FAILURE; // [Lab 7]
        } // [Lab 7]

        $ms = $start->diffInMilliseconds(now()); // [Lab 7]
        $this->info("[Lab 7] Materialized view refreshed in {$ms}ms"); // [Lab 7]

        return self::SUCCESS; // [Lab 7]
    }
}
