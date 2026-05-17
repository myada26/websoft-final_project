<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\DailySummaryReportNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateDailySummaryReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private ?string $date = null
    ) {}

    public function handle(): void
    {
        $date  = $this->date ?? today()->toDateString();
        $orgs  = Organization::where('is_active', true)->get();
        $count = 0;

        foreach ($orgs as $org) {
            $summary = Transaction::where('organization_id', $org->id)
                ->whereDate('created_at', $date)
                ->where('is_void', false)
                ->selectRaw('transaction_type, payment_method, SUM(amount_paid) as total, COUNT(*) as count')
                ->groupBy('transaction_type', 'payment_method')
                ->get();

            if ($summary->isEmpty()) {
                continue;
            }

            // Notify the Chairperson of each org
            $chairperson = User::where('organization_id', $org->id)
                ->where('role', 'CHAIRPERSON')
                ->where('is_active', true)
                ->first();

            if ($chairperson?->email) {
                $chairperson->notify(new DailySummaryReportNotification($org, $summary, $date));
            }

            $count++;
        }

        Log::info("FCATS daily summary: generated for {$count} organizations on {$date}.");
    }
}
