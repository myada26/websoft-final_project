<?php

namespace App\Console\Commands;

use App\Jobs\GenerateDailySummaryReport as GenerateDailySummaryReportJob;
use Illuminate\Console\Command;

class GenerateDailySummaryReport extends Command
{
    protected $signature = 'fcats:report:daily {--date= : Date in Y-m-d format (defaults to today)}';

    protected $description = 'Generate daily collection summary reports and email Chairpersons';

    public function handle(): int
    {
        $date = $this->option('date') ?? today()->toDateString();

        $this->info("Generating daily summary for {$date}...");

        GenerateDailySummaryReportJob::dispatchSync($date);

        $this->info('Daily summary reports generated successfully.');

        return self::SUCCESS;
    }
}
