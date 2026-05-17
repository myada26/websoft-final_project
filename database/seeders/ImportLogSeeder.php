<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ImportLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class ImportLogSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser   = User::where('role', 'SSC_ADMIN')->first();
        $activeYear  = AcademicYear::where('is_active', true)->first();

        if (! $adminUser || ! $activeYear) {
            $this->command->warn('ImportLogSeeder: SSC_ADMIN user or active academic year not found — skipping.');
            return;
        }

        $statuses = ['SUCCESS', 'PARTIAL', 'FAILED', 'PENDING'];

        foreach (range(1, 10) as $i) {
            $status       = $statuses[array_rand($statuses)];
            $rows         = $status === 'FAILED' ? 0 : rand(50, 1000);
            $failureCount = in_array($status, ['PARTIAL', 'FAILED']) ? rand(1, 20) : 0;

            ImportLog::create([
                'type'                => 'STUDENT_ENROLLMENT',
                'filename'            => "enrollment_batch_{$i}.xlsx",
                'uploaded_by_user_id' => $adminUser->id,
                'academic_year_id'    => $activeYear->id,
                'rows_processed'      => $rows,
                'failures_count'      => $failureCount,
                'failure_details'     => $failureCount > 0
                    ? array_map(fn($n) => ['row' => $n, 'errors' => ['Invalid student type'], 'values' => ["row_{$n}"]], range(1, min($failureCount, 3)))
                    : null,
                'status'              => $status,
                'started_at'          => now()->subDays(rand(1, 30)),
                'completed_at'        => $status !== 'PENDING' ? now()->subDays(rand(0, 29)) : null,
                'created_at'          => now()->subDays(rand(1, 30)),
                'updated_at'          => now()->subDays(rand(0, 1)),
            ]);
        }
    }
}
