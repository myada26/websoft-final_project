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
            RolePermissionSeeder::class,
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

        $bsmeProgramId = DB::table('programs')->insertGetId([
            'department_id' => $departmentId,
            'name' => 'BS Mechanical Engineering',
            'code' => 'BSME',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sscOrgId = DB::table('organizations')->insertGetId([
            'name' => 'Supreme Student Council',
            'type' => 'UNIVERSITY_WIDE',
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
            'email' => 's.brigoli.boonjefferson@cmu.edu.ph',
            'created_source' => 'MANUAL',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orgStudents = [
            ['student_number' => '2023-002', 'first_name' => 'Chair',  'last_name' => 'Person'],
            ['student_number' => '2023-003', 'first_name' => 'Tess',   'last_name' => 'Treasurer'],
            ['student_number' => '2023-004', 'first_name' => 'Cole',   'last_name' => 'Collector'],
            ['student_number' => '2023-005', 'first_name' => 'Audra',  'last_name' => 'Auditor'],
            ['student_number' => '2023-006', 'first_name' => 'Sarah',  'last_name' => 'Secretary'],
        ];

        $orgStudentIds = [];
        foreach ($orgStudents as $student) {
            $orgStudentIds[$student['student_number']] = DB::table('students')->insertGetId([
                'student_number' => $student['student_number'],
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'middle_name' => null,
                'email' => 's.brigoli.boonjefferson@cmu.edu.ph',
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
                'student_type' => 'REGULAR',
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
                'student_type' => 'REGULAR',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $bsmeStudents = [
            ['student_number' => '2024-0002', 'first_name' => 'Carlos Fidel', 'last_name' => 'Castro', 'middle_name' => 'G.', 'year' => 2],
            ['student_number' => '2024-0011', 'first_name' => 'James', 'last_name' => 'Yong', 'middle_name' => 'B.', 'year' => 1],
            ['student_number' => '2024-0012', 'first_name' => 'Grace', 'last_name' => 'Chua', 'middle_name' => 'B.', 'year' => 2],
            ['student_number' => '2024-0013', 'first_name' => 'Mark', 'last_name' => 'Sy', 'middle_name' => 'B.', 'year' => 4],
            ['student_number' => '2024-0014', 'first_name' => 'Joy', 'last_name' => 'Tan', 'middle_name' => 'B.', 'year' => 1],
            ['student_number' => '2024-0015', 'first_name' => 'Robert', 'last_name' => 'Lim', 'middle_name' => 'B.', 'year' => 3],
            ['student_number' => '2024-0016', 'first_name' => 'Emily', 'last_name' => 'Go', 'middle_name' => 'B.', 'year' => 2],
            ['student_number' => '2024-0017', 'first_name' => 'Kevin', 'last_name' => 'Ng', 'middle_name' => 'B.', 'year' => 1],
            ['student_number' => '2024-0018', 'first_name' => 'Angela', 'last_name' => 'Lee', 'middle_name' => 'B.', 'year' => 2],
            ['student_number' => '2024-0019', 'first_name' => 'Jonathan', 'last_name' => 'Co', 'middle_name' => 'B.', 'year' => 2],
        ];

        $bsmeStudentIds = [];
        foreach ($bsmeStudents as $student) {
            $bsmeStudentIds[$student['student_number']] = DB::table('students')->insertGetId([
                'student_number' => $student['student_number'],
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'middle_name' => $student['middle_name'],
                'email' => strtolower($student['first_name'] . '.' . $student['last_name']) . '@cmu.edu.ph',
                'created_source' => 'MANUAL',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $bsceAdditionalStudents = [
            ['student_number' => '2024-0001', 'first_name' => 'Boon Jefferson', 'last_name' => 'Brigoli', 'middle_name' => 'S.', 'year' => 2],
            ['student_number' => '2024-0003', 'first_name' => 'Maria', 'last_name' => 'Santos', 'middle_name' => 'A.', 'year' => 3],
            ['student_number' => '2024-0004', 'first_name' => 'John', 'last_name' => 'Dizon', 'middle_name' => 'A.', 'year' => 4],
            ['student_number' => '2024-0005', 'first_name' => 'Ana', 'last_name' => 'Bautista', 'middle_name' => 'A.', 'year' => 2],
            ['student_number' => '2024-0006', 'first_name' => 'Michael', 'last_name' => 'Ramos', 'middle_name' => 'A.', 'year' => 1],
            ['student_number' => '2024-0007', 'first_name' => 'Sarah', 'last_name' => 'Torres', 'middle_name' => 'A.', 'year' => 1],
            ['student_number' => '2024-0008', 'first_name' => 'David', 'last_name' => 'Garcia', 'middle_name' => 'A.', 'year' => 1],
            ['student_number' => '2024-0009', 'first_name' => 'Lisa', 'last_name' => 'Mendoza', 'middle_name' => 'A.', 'year' => 3],
            ['student_number' => '2024-0010', 'first_name' => 'Paul', 'last_name' => 'Cruz', 'middle_name' => 'A.', 'year' => 1],
        ];

        foreach ($bsceAdditionalStudents as $student) {
            $studentId = DB::table('students')->insertGetId([
                'student_number' => $student['student_number'],
                'first_name' => $student['first_name'],
                'last_name' => $student['last_name'],
                'middle_name' => $student['middle_name'],
                'email' => strtolower($student['first_name'] . '.' . $student['last_name']) . '@cmu.edu.ph',
                'created_source' => 'MANUAL',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('student_enrollments')->insert([
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
                'program_id' => $programId,
                'year_level' => $student['year'],
                'student_type' => 'REGULAR',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($bsmeStudentIds as $studentId) {
            $yearLevel = collect($bsmeStudents)->firstWhere('student_number', array_search($studentId, $bsmeStudentIds))['year'];
            DB::table('student_enrollments')->insert([
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
                'program_id' => $bsmeProgramId,
                'year_level' => $yearLevel,
                'student_type' => 'REGULAR',
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
            'SECRETARY' => [
                'student_id' => $orgStudentIds['2023-006'],
                'organization_id' => $coeOrgId,
                'username' => '2023-006-COE',
                'password_hash' => Hash::make('password'),
                'role' => 'SECRETARY',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            'SECRETARY_BSME' => [
                'student_id' => $bsmeStudentIds['2024-0002'],
                'organization_id' => $coeOrgId,
                'username' => '2024-0002-COE',
                'password_hash' => Hash::make('password'),
                'role' => 'SECRETARY',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        $userIds = [];
        foreach ($users as $role => $user) {
            $userIds[$role] = DB::table('users')->insertGetId($user);
        }

        DB::table('audit_logs')->insert([
            'user_id' => $userIds['SSC_ADMIN'],
            'action' => 'DATABASE_SEEDED',
            'entity_type' => 'SYSTEM',
            'entity_id' => null,
            'details' => json_encode(['seed' => static::class]),
            'ip_address' => '127.0.0.1',
            'timestamp' => now(),
        ]);

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
            'SSC_ADMIN'  => [],
            'CHAIRPERSON' => ['students:view', 'students:enroll', 'users:manage', 'void:approve', 'audit:view', 'attendance:view', 'event:create', 'event:approve'],
            'TREASURER'  => ['students:view', 'transactions:view', 'pos:create', 'void:request', 'remit:view', 'remit:create'],
            'COLLECTOR'  => ['students:view', 'pos:create', 'void:request'],
            'AUDITOR'    => ['transactions:view', 'remit:view', 'remit:verify', 'remit:accept', 'void:review', 'audit:view', 'attendance:view', 'event:approve'],
            'SECRETARY'  => ['attendance:record', 'attendance:view'],
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

        $this->call([
            EventsSeeder::class,
        ]);
    }
}
