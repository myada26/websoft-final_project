<?php

namespace App\Providers;

use App\Console\Commands\ArchiveOldAuditLogs;
use App\Console\Commands\GenerateDailySummaryReport;
use App\Console\Commands\RefreshFcatsMaterializedViews; // [Lab 7]
use App\Jobs\ArchiveOldAuditLogs as ArchiveOldAuditLogsJob;
use App\Jobs\GenerateDailySummaryReport as GenerateDailySummaryReportJob;
use App\Models\FeeProfile;
use App\Models\Remittance;
use App\Models\Transaction;
use App\Models\VoidRequest;
use App\Notifications\BackupFailedNotification;
use App\Observers\FeeProfileObserver;
use App\Observers\RemittanceObserver;
use App\Observers\TransactionObserver;
use App\Observers\VoidRequestObserver;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class Lab6ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register custom Artisan commands so they appear in php artisan list
        $this->commands([
            ArchiveOldAuditLogs::class,
            GenerateDailySummaryReport::class,
            RefreshFcatsMaterializedViews::class, // [Lab 7]
        ]);
    }

    public function boot(): void
    {
        // ── Model Observers (Phase 3 — Audit Logging) ────────────────────────
        Transaction::observe(TransactionObserver::class);
        FeeProfile::observe(FeeProfileObserver::class);
        Remittance::observe(RemittanceObserver::class);
        VoidRequest::observe(VoidRequestObserver::class);

        // ── Task Scheduler (Phase 2 — Automated Operations) ──────────────────
        // In Laravel 11+, scheduling is registered via the service provider boot().
        // Commands must also be registered below so Artisan can discover them.
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            // Full database + files backup (daily 02:00)
            $schedule->command('backup:run')
                ->dailyAt('02:00')
                ->withoutOverlapping()
                ->onFailure(function () {
                    Notification::route('mail', config('mail.from.address'))
                        ->notify(new BackupFailedNotification('Scheduled backup:run failed.'));
                })
                ->onSuccess(fn() => \Illuminate\Support\Facades\Log::info('FCATS backup completed: ' . now()));

            // Prune old backups per retention policy (daily 03:00)
            $schedule->command('backup:clean')->dailyAt('03:00');

            // Daily collection summary report emailed to Chairpersons (06:00)
            $schedule->job(new GenerateDailySummaryReportJob())->dailyAt('06:00');

            // Remind Treasurers of open fine collection windows (weekdays 07:30)
            $schedule->call(function () {
                \App\Models\FineCollectionWindow::where('status', 'OPEN')
                    ->with('organization')
                    ->get()
                    ->each(fn($window) =>
                        \Illuminate\Support\Facades\Log::info(
                            "FCATS: Fine collection window open for {$window->organization->name}"
                        )
                    );
            })->weekdays()->at('07:30');

            // Expire PHP sessions
            $schedule->command('session:gc')->daily();

            // Prune old database notifications (weekly)
            $schedule->command('model:prune', [
                '--model' => \Illuminate\Notifications\DatabaseNotification::class,
            ])->weekly();

            // Archive audit logs older than 1 year (monthly)
            $schedule->job(new ArchiveOldAuditLogsJob())
                ->monthly()
                ->withoutOverlapping();

            // [Lab 7] Refresh collection summary materialized view every 30 minutes
            $schedule->command('fcats:refresh-views') // [Lab 7]
                ->everyThirtyMinutes() // [Lab 7]
                ->withoutOverlapping() // [Lab 7]
                ->appendOutputTo(storage_path('logs/materialized_views.log')); // [Lab 7]
        });
    }

    public function provides(): array
    {
        return [];
    }
}
