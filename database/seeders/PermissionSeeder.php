<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['slug' => 'students:view', 'description' => 'View enrolled students',              'module' => 'STUDENTS'],
            ['slug' => 'students:enroll', 'description' => 'Enroll students',                   'module' => 'STUDENTS'],
            ['slug' => 'users:manage',  'description' => 'Manage organization users',           'module' => 'USERS'],
            ['slug' => 'audit:view',    'description' => 'View audit logs',                     'module' => 'AUDIT'],
            ['slug' => 'transactions:view', 'description' => 'View transaction history',         'module' => 'POS'],
            ['slug' => 'pos:create',    'description' => 'Process a payment transaction',        'module' => 'POS'],
            ['slug' => 'remit:view',    'description' => 'View remittance records',              'module' => 'REMITTANCE'],
            ['slug' => 'remit:create',  'description' => 'Create a remittance batch',             'module' => 'REMITTANCE'],
            ['slug' => 'remit:verify',  'description' => 'Digitally verify/sign a remittance',   'module' => 'REMITTANCE'],
            ['slug' => 'remit:accept',  'description' => 'Mark remittance as received/banked',   'module' => 'REMITTANCE'],
            ['slug' => 'void:request',  'description' => 'Request a receipt void',               'module' => 'VOID'],
            ['slug' => 'void:approve',  'description' => 'Approve a void request',               'module' => 'VOID'],
            ['slug' => 'void:review',   'description' => 'Review void requests',                 'module' => 'VOID'],
            ['slug' => 'reports:view',  'description' => 'Access financial reports',             'module' => 'REPORTS'],
        ];

        DB::table('permissions')->insertOrIgnore($permissions);
    }
}
