<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\ReceiptEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendReceiptEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $transactionId) {}

    public function handle(ReceiptEmailService $service): void
    {
        $tx = Transaction::find($this->transactionId);
        if ($tx) {
            $service->send($tx);
        }
    }
}
