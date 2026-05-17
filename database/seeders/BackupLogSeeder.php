<?php

namespace Database\Seeders;

use App\Models\BackupLog;
use Illuminate\Database\Seeder;

class BackupLogSeeder extends Seeder
{
    public function run(): void
    {
        // Generate 30 days of mock backup log entries
        foreach (range(1, 30) as $daysAgo) {
            $success = rand(0, 9) > 1;           // ~90% success rate

            BackupLog::create([
                'status'        => $success ? 'SUCCESS' : 'FAILED',
                'filename'      => $success ? 'FCATS-' . now()->subDays($daysAgo)->format('Y-m-d-H-i-s') . '.zip' : null,
                'size_bytes'    => $success ? rand(10_000_000, 200_000_000) : null,
                'disk'          => 'local',
                'error_message' => ! $success ? 'Connection refused to backup destination.' : null,
                'executed_at'   => now()->subDays($daysAgo)->setTime(2, 0, 0),
                'created_at'    => now()->subDays($daysAgo)->setTime(2, 0, 0),
                'updated_at'    => now()->subDays($daysAgo)->setTime(2, 0, 0),
            ]);
        }
    }
}
