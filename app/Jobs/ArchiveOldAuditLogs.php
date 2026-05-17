<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\AuditLogArchive;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ArchiveOldAuditLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries   = 1;

    public function handle(): void
    {
        $cutoff   = now()->subYear();
        $archived = 0;

        AuditLog::where('timestamp', '<', $cutoff)
            ->chunkById(500, function ($logs) use (&$archived) {
                $rows = $logs->map(fn($log) => [
                    'original_id'  => $log->id,
                    'user_id'      => $log->user_id,
                    'action'       => $log->action,
                    'entity_type'  => $log->entity_type,
                    'entity_id'    => $log->entity_id,
                    'details'      => is_array($log->details) ? json_encode($log->details) : $log->details,
                    'ip_address'   => $log->ip_address,
                    'content_hash' => hash('sha256', json_encode([
                        'action'      => $log->action,
                        'entity_type' => $log->entity_type,
                        'entity_id'   => $log->entity_id,
                        'details'     => $log->details,
                        'timestamp'   => (string) $log->timestamp,
                    ])),
                    'timestamp'    => $log->timestamp,
                    'archived_at'  => now(),
                ])->all();

                AuditLogArchive::insert($rows);
                AuditLog::whereIn('id', $logs->pluck('id'))->delete();
                $archived += count($rows);
            });

        Log::info("FCATS audit log archival completed: {$archived} records archived.");
    }
}
