<?php

namespace App\Console\Commands;

use App\Jobs\ArchiveOldAuditLogs as ArchiveOldAuditLogsJob;
use Illuminate\Console\Command;

class ArchiveOldAuditLogs extends Command
{
    protected $signature = 'fcats:audit:archive';

    protected $description = 'Archive audit_logs older than 1 year to audit_logs_archive (NFR-014)';

    public function handle(): int
    {
        $this->info('Starting audit log archival...');

        ArchiveOldAuditLogsJob::dispatchSync();

        $this->info('Audit log archival completed.');

        return self::SUCCESS;
    }
}
