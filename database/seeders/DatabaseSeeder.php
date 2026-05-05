<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
        ]);

        $academicYearId = DB::table('academic_years')->insertGetId([
            'name' => '2024-2025 2nd Sem',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $collegeId = DB::table('colleges')->insertGetId([
            'name' => 'College of Engineering',
            'code' => 'COE',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $departmentId = DB::table('departments')->insertGetId([
            'college_id' => $collegeId,
            'name' => 'Civil Engineering',
            'code' => 'CE',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $programId = DB::table('programs')->insertGetId([
            'department_id' => $departmentId,
            'name' => 'BS Civil Engineering',
            'code' => 'BSCE',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sscOrgId = DB::table('organizations')->insertGetId([
            'name' => 'Supreme Student Council',
            'type' => 'SSC',
            'linked_college_id' => null,
            'linked_department_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $coeOrgId = DB::table('organizations')->insertGetId([
            'name' => 'COE Council',
            'type' => 'COLLEGE_COUNCIL',
            'linked_college_id' => $collegeId,
            'linked_department_id' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminStudentId = DB::table('students')->insertGetId([
            'student_number' => '2023-001',
            'first_name' => 'SSC',
            'last_name' => 'Admin',
            'middle_name' => null,
            'created_source' => 'MANUAL',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orgStudents = [
            ['student_number' => '2023-002', 'first_name' => 'Chair', 'last_name' => 'Person'],
            ['student_number' => '2023-003', 'first_name' => 'Tess', 'last_name' => 'Treasurer'],
            ['student_number' => '2023-004', 'first_name' => 'Cole', 'last_name' => 'Collector'],
            ['student_number' => '2023-005', 'first_name' => 'Audra', 'last_name' => 'Auditor'],
        ];

        $orgStudentIds = [];
        foreach ($orgStudents as $student) {
            $orgStudentIds[$student['student_number']] = DB::table('students')->insertGetId([
                'student_number' => $student['student_number'],
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'middle_name' => null,
                'created_source' => 'MANUAL',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('student_enrollments')->insert([
            [
                'student_id' => $adminStudentId,
                'academic_year_id' => $academicYearId,
                'program_id' => $programId,
                'year_level' => 4,
                'is_regular' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        foreach ($orgStudentIds as $studentId) {
            DB::table('student_enrollments')->insert([
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
                'program_id' => $programId,
                'year_level' => 3,
                'is_regular' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $users = [
            'SSC_ADMIN' => [
                'student_id' => $adminStudentId,
                'organization_id' => $sscOrgId,
                'username' => '2023-001-SSC',
                'password_hash' => Hash::make('password'),
                'role' => 'SSC_ADMIN',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            'CHAIRPERSON' => [
                'student_id' => $orgStudentIds['2023-002'],
                'organization_id' => $coeOrgId,
                'username' => '2023-002-COE',
                'password_hash' => Hash::make('password'),
                'role' => 'CHAIRPERSON',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            'TREASURER' => [
                'student_id' => $orgStudentIds['2023-003'],
                'organization_id' => $coeOrgId,
                'username' => '2023-003-COE',
                'password_hash' => Hash::make('password'),
                'role' => 'TREASURER',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            'COLLECTOR' => [
                'student_id' => $orgStudentIds['2023-004'],
                'organization_id' => $coeOrgId,
                'username' => '2023-004-COE',
                'password_hash' => Hash::make('password'),
                'role' => 'COLLECTOR',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            'AUDITOR' => [
                'student_id' => $orgStudentIds['2023-005'],
                'organization_id' => $coeOrgId,
                'username' => '2023-005-COE',
                'password_hash' => Hash::make('password'),
                'role' => 'AUDITOR',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        $userIds = [];
        foreach ($users as $role => $user) {
            $userIds[$role] = DB::table('users')->insertGetId($user);
        }

        DB::table('or_sequences')->insert([
            'organization_id' => $coeOrgId,
            'last_or_number' => 0,
            'updated_at' => now(),
        ]);

        DB::table('fee_profiles')->insert([
            'organization_id' => $coeOrgId,
            'name' => 'COE Membership Fee',
            'amount' => 150.00,
            'category' => 'REGULAR',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rolePermissions = [
            'SSC_ADMIN' => [],
            'CHAIRPERSON' => ['students:view', 'students:enroll', 'users:manage', 'void:approve', 'audit:view'],
            'TREASURER' => ['students:view', 'transactions:view', 'pos:create', 'void:request', 'remit:view', 'remit:create'],
            'COLLECTOR' => ['students:view', 'pos:create', 'void:request'],
            'AUDITOR' => ['transactions:view', 'remit:view', 'remit:verify', 'remit:accept', 'void:review', 'audit:view'],
        ];

        $permissionIds = DB::table('permissions')->pluck('id', 'slug');
        $userPermissionRows = [];
        foreach ($rolePermissions as $role => $slugs) {
            foreach ($slugs as $slug) {
                if (isset($permissionIds[$slug])) {
                    $userPermissionRows[] = [
                        'user_id' => $userIds[$role],
                        'permission_id' => $permissionIds[$slug],
                        'granted_at' => now(),
                    ];
                }
            }
        }

        if ($userPermissionRows) {
            DB::table('user_permissions')->insert($userPermissionRows);
        }
    }
}
