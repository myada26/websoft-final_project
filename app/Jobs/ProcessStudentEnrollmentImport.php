<?php

namespace App\Jobs;

use App\Imports\StudentEnrollmentImport;
use App\Models\AuditLog;
use App\Models\ImportLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessStudentEnrollmentImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout       = 1800;  // 30 minutes — bulk-upsert path now completes ~1 min per 10k rows
    public int $tries         = 1;     // never auto-retry: committed chunks would double-insert on retry
    public int $maxExceptions = 1;

    public function __construct(
        public string $filePath,
        public int    $uploadedByUserId,
        public int    $importLogId,
        public string $orgType = 'UNIVERSITY_WIDE',
        public ?int   $orgLinkedCollegeId = null,
        public ?int   $orgLinkedDepartmentId = null
    ) {}

    public function handle(): void
    {
        $importLog = ImportLog::find($this->importLogId);
        if (! $importLog) {
            Log::warning("ProcessStudentEnrollmentImport: ImportLog #{$this->importLogId} missing — abort.");
            return;
        }

        $importLog->update(['status' => 'PROCESSING', 'started_at' => now()]);

        $absolutePath = Storage::path($this->filePath);
        if (! is_file($absolutePath)) {
            $this->markFailed($importLog, "Uploaded file no longer exists at {$absolutePath}");
            return;
        }

        $import = new StudentEnrollmentImport(
            $this->orgType,
            $this->orgLinkedCollegeId,
            $this->orgLinkedDepartmentId
        );

        try {
            Excel::import($import, $absolutePath);
        } catch (\Throwable $e) {
            Log::error('StudentEnrollmentImport threw', [
                'import_log_id' => $this->importLogId,
                'error'         => $e->getMessage(),
            ]);
            $this->markFailed($importLog, $e->getMessage());
            $this->cleanup();
            return;
        }

        $rowCount     = $import->getRowCount();
        $failures     = $import->failures();
        $failureCount = count($failures);

        $status = match (true) {
            $failureCount === 0 && $rowCount > 0 => 'SUCCESS',
            $rowCount === 0                       => 'FAILED',
            default                               => 'PARTIAL',
        };

        $importLog->update([
            'rows_processed'  => $rowCount,
            'failures_count'  => $failureCount,
            'failure_details' => $failureCount > 0 ? $failures : null,
            'status'          => $status,
            'completed_at'    => now(),
        ]);

        AuditLog::create([
            'user_id'     => $this->uploadedByUserId,
            'action'      => 'STUDENT_IMPORT_COMPLETED',
            'entity_type' => 'IMPORT_LOG',
            'entity_id'   => $this->importLogId,
            'details'     => [
                'filename'        => basename($this->filePath),
                'rows_processed'  => $rowCount,
                'failures_count'  => $failureCount,
                'status'          => $status,
            ],
            'ip_address'  => null,
            'timestamp'   => now(),
        ]);

        $this->cleanup();
    }

    public function failed(\Throwable $exception): void
    {
        $importLog = ImportLog::find($this->importLogId);
        if ($importLog && $importLog->status !== 'FAILED') {
            $this->markFailed($importLog, $exception->getMessage());
        }
        $this->cleanup();
    }

    private function markFailed(ImportLog $log, string $error): void
    {
        $log->update([
            'status'          => 'FAILED',
            'completed_at'    => now(),
            'failure_details' => [['row' => 0, 'errors' => [$error], 'values' => []]],
        ]);
    }

    private function cleanup(): void
    {
        try {
            Storage::delete($this->filePath);
        } catch (\Throwable) {
            // best-effort cleanup
        }
    }
}
