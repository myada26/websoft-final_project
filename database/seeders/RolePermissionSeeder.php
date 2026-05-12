<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = DB::table('permissions')->pluck('id', 'slug')->toArray();

        $rolePermissions = [
            // SSC_ADMIN - All permissions
            'SSC_ADMIN' => [
                'students:view',
                'students:enroll',
                'users:manage',
                'audit:view',
                'transactions:view',
                'pos:create',
                'remit:view',
                'remit:create',
                'remit:verify',
                'remit:accept',
                'void:request',
                'void:approve',
                'void:review',
                'reports:view',
                'attendance:record',
                'attendance:view',
                'event:create',
                'event:approve',
            ],

            // CHAIRPERSON
            'CHAIRPERSON' => [
                'students:view',
                'students:enroll',
                'users:manage',
                'void:approve',
                'audit:view',
                'attendance:view',
                'event:create',
                'event:approve',
                'transactions:view',
                'reports:view',
                'remit:view',
                'remit:accept',
            ],

            // TREASURER
            'TREASURER' => [
                'students:view',
                'transactions:view',
                'pos:create',
                'void:request',
                'remit:view',
                'remit:create',
                'attendance:view',
            ],

            // COLLECTOR
            'COLLECTOR' => [
                'students:view',
                'pos:create',
                'void:request',
                'attendance:view',
            ],

            // AUDITOR
            'AUDITOR' => [
                'transactions:view',
                'remit:view',
                'remit:verify',
                'remit:accept',
                'void:review',
                'void:approve',
                'audit:view',
                'attendance:view',
                'reports:view',
                'event:approve',
            ],

            // SECRETARY
            'SECRETARY' => [
                'attendance:record',
                'attendance:view',
                'students:view',
            ],
        ];

        $rows = [];
        foreach ($rolePermissions as $role => $slugs) {
            foreach ($slugs as $slug) {
                if (isset($permissions[$slug])) {
                    $rows[] = [
                        'role' => $role,
                        'permission_id' => $permissions[$slug],
                        'created_at' => now(),
                    ];
                }
            }
        }

        DB::table('role_permissions')->insertOrIgnore($rows);
    }
}