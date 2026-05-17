<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::limit(10)->get();

        if ($users->isEmpty()) {
            $this->command->warn('AuditLogSeeder: No users found — skipping.');
            return;
        }

        $actions = [
            'TRANSACTION_CREATED', 'TRANSACTION_VOIDED',
            'VOID_REQUESTED', 'VOID_APPROVED', 'VOID_REJECTED',
            'FEE_PROFILE_CREATED', 'FEE_PROFILE_UPDATED',
            'REMITTANCE_CREATED', 'REMITTANCE_VERIFIED', 'REMITTANCE_ACCEPTED',
            'STUDENT_IMPORT_COMPLETED', 'STUDENT_ENROLLED_MANUAL',
            'BACKUP_COMPLETED', 'EXPORT_GENERATED',
            'FINE_WINDOW_OPENED', 'FINE_WINDOW_CLOSED',
        ];

        $entityTypes = [
            'TRANSACTION', 'FEE_PROFILE', 'REMITTANCE',
            'VOID_REQUEST', 'IMPORT_LOG', 'EXPORT_LOG',
        ];

        foreach (range(1, 100) as $i) {
            $user   = $users->random();
            $action = $actions[array_rand($actions)];

            AuditLog::create([
                'user_id'     => $user->id,
                'action'      => $action,
                'entity_type' => $entityTypes[array_rand($entityTypes)],
                'entity_id'   => rand(1, 50),
                'details'     => [
                    'note'     => "Mock audit entry #{$i}",
                    'org_id'   => $user->organization_id,
                ],
                'ip_address'  => '192.168.1.' . rand(1, 255),
                'timestamp'   => now()->subDays(rand(0, 365))->subMinutes(rand(0, 1440)),
            ]);
        }
    }
}
