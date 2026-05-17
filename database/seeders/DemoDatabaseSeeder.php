<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DemoDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            foreach ([
                'student_fines',
                'event_attendance',
                'events',
                'audit_logs',
                'void_requests',
                'transactions',
                'remittances',
                'role_permissions',
                'user_permissions',
                'or_sequences',
                'fee_profiles',
                'users',
                'student_enrollments',
                'students',
                'organizations',
                'resolutions',
                'programs',
                'departments',
                'colleges',
                'academic_years',
            ] as $table) {
                DB::table($table)->truncate();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->call(PermissionSeeder::class);

        DB::transaction(function (): void {
            $now = now();

            $academicYearId = DB::table('academic_years')->insertGetId([
                'name'       => '1st Semester 2024-2025',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $collegeId = DB::table('colleges')->insertGetId([
                'name'       => 'College of Information Sciences and Computing',
                'code'       => 'CISC',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $departmentId = DB::table('departments')->insertGetId([
                'college_id' => $collegeId,
                'name'       => 'Information Technology',
                'code'       => 'IT',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $programId = DB::table('programs')->insertGetId([
                'department_id' => $departmentId,
                'name'          => 'BS Information Technology',
                'code'          => 'BSIT',
                'is_active'     => true,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            $collegeCouncilId = DB::table('organizations')->insertGetId([
                'name'                  => 'CISC Student Council',
                'type'                  => 'COLLEGE_COUNCIL',
                'linked_college_id'     => $collegeId,
                'linked_department_id'  => null,
                'is_active'             => true,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);

            $departmentSocietyId = DB::table('organizations')->insertGetId([
                'name'                  => 'IT Student Society',
                'type'                  => 'CLASS_ORG',
                'linked_college_id'     => null,
                'linked_department_id'  => $departmentId,
                'is_active'             => true,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);

            foreach ([$collegeCouncilId, $departmentSocietyId] as $organizationId) {
                DB::table('or_sequences')->insert([
                    'organization_id' => $organizationId,
                    'last_or_number'  => 0,
                    'updated_at'      => $now,
                ]);
            }

            DB::table('fee_profiles')->insert([
                [
                    'organization_id' => $departmentSocietyId,
                    'name'            => 'Membership Fee',
                    'amount'          => 1000.00,
                    'category'        => 'REGULAR',
                    'is_active'       => true,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ],
                [
                    'organization_id' => $departmentSocietyId,
                    'name'            => 'Extendee Fee',
                    'amount'          => 500.00,
                    'category'        => 'EXTENDEE',
                    'is_active'       => true,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ],
            ]);

            $studentIds = $this->seedStudentsAndEnrollments($academicYearId, $programId, $now);
            $userIds    = $this->seedOfficerAccounts($studentIds, $departmentSocietyId, $now);
            $this->seedRolePermissions($userIds, $now);
            $this->seedDraftEventWithAttendance($departmentSocietyId, $academicYearId, $userIds['CHAIRPERSON'], $studentIds, $now);
        });

        $this->command?->info('Demo database seeded successfully.');
        $this->command?->line('Academic year: 1st Semester 2024-2025 (active).');
        $this->command?->line('Structure: CISC → Information Technology → BS Information Technology.');
        $this->command?->line('Students: 50 records (2024-000001 … 2024-000050), 50 active-semester enrollments.');
        $this->command?->line('Fee profiles: Membership Fee (₱1,000.00 · REGULAR), Extendee Fee (₱500.00 · EXTENDEE).');
        $this->command?->line('Demo logins:');
        $this->command?->line('  Admin (Chairperson): 2024-000001-IT / password');
        $this->command?->line('  Auditor:             2024-000002-IT / password');
        $this->command?->line('  Secretary:           2024-000003-IT / password');
        $this->command?->line('  Treasurer:           2024-000004-IT / password');
    }

    /** @return array<int, int> */
    private function seedStudentsAndEnrollments(int $academicYearId, int $programId, mixed $now): array
    {
        $firstNames = [
            'Alyssa', 'Bryan', 'Carmela', 'Daryl', 'Elaine',
            'Francis', 'Giselle', 'Harold', 'Isabel', 'Joshua',
            'Katrina', 'Lorenzo', 'Marielle', 'Nathan', 'Olivia',
            'Patrick', 'Queenie', 'Rafael', 'Sophia', 'Tristan',
            'Ulysses', 'Vanessa', 'Warren', 'Xandra', 'Yvonne',
            'Zachary', 'Andrea', 'Benedict', 'Clarisse', 'Dominic',
            'Erika', 'Felix', 'Grace', 'Hector', 'Irene',
            'Jasper', 'Kimberly', 'Leo', 'Monica', 'Nico',
            'Paula', 'Renz', 'Samantha', 'Tobias', 'Uma',
            'Victor', 'Wilma', 'Xavier', 'Ysabel', 'Zion',
        ];

        $lastNames = [
            'Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo',
            'Garcia', 'Mendoza', 'Torres', 'Flores', 'Ramos',
            'Aquino', 'Villanueva', 'Castillo', 'De Leon', 'Dela Cruz',
            'Gonzales', 'Hernandez', 'Lopez', 'Perez', 'Ramirez',
            'Rivera', 'Rodriguez', 'Sanchez', 'Fernandez', 'Diaz',
            'Morales', 'Pascual', 'Soriano', 'Tolentino', 'Velasco',
            'Manalo', 'Mercado', 'Navarro', 'Padilla', 'Quizon',
            'Salvador', 'Tomas', 'Uy', 'Valencia', 'Yap',
            'Zamora', 'Lim', 'Tan', 'Chua', 'Ong',
            'Sy', 'Go', 'Yu', 'Chan', 'Co',
        ];

        $studentRows = [];

        for ($i = 1; $i <= 50; $i++) {
            $studentRows[] = [
                'student_number' => sprintf('2024-%06d', $i),
                'first_name'     => $firstNames[$i - 1],
                'last_name'      => $lastNames[$i - 1],
                'middle_name'    => chr(64 + (($i - 1) % 26) + 1) . '.',
                'created_source' => 'SSC_BULK',
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        DB::table('students')->insert($studentRows);

        $studentIdsByNumber = DB::table('students')
            ->orderBy('student_number')
            ->pluck('id', 'student_number');

        $studentIds    = [];
        $enrollmentRows = [];

        foreach ($studentIdsByNumber as $studentNumber => $studentId) {
            $sequence = (int) substr($studentNumber, -6);
            $studentIds[$sequence] = (int) $studentId;

            $enrollmentRows[] = [
                'student_id'      => $studentId,
                'academic_year_id' => $academicYearId,
                'program_id'      => $programId,
                'year_level'      => (($sequence - 1) % 4) + 1,
                'student_type'    => $sequence % 6 === 0 ? 'IRREGULAR' : 'REGULAR',
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        DB::table('student_enrollments')->insert($enrollmentRows);

        return $studentIds;
    }

    /**
     * @param array<int, int> $studentIds
     * @return array<string, int>
     */
    private function seedOfficerAccounts(array $studentIds, int $organizationId, mixed $now): array
    {
        $officers = [
            'CHAIRPERSON' => ['student_seq' => 1, 'username' => '2024-000001-IT'],
            'AUDITOR'     => ['student_seq' => 2, 'username' => '2024-000002-IT'],
            'SECRETARY'   => ['student_seq' => 3, 'username' => '2024-000003-IT'],
            'TREASURER'   => ['student_seq' => 4, 'username' => '2024-000004-IT'],
        ];

        $userIds = [];
        foreach ($officers as $role => $officer) {
            $userIds[$role] = DB::table('users')->insertGetId([
                'student_id'            => $studentIds[$officer['student_seq']],
                'organization_id'       => $organizationId,
                'username'              => $officer['username'],
                'password_hash'         => Hash::make('password'),
                'role'                  => $role,
                'is_active'             => true,
                'last_login'            => null,
                'failed_login_attempts' => 0,
                'locked_until'          => null,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);
        }

        return $userIds;
    }

    /** @param array<string, int> $userIds */
    private function seedRolePermissions(array $userIds, mixed $now): void
    {
        $rolePermissions = [
            'CHAIRPERSON' => [
                'remit:accept',
                'void:approve',
                'reports:view',
                'event:create',
                'event:approve',
            ],
            'AUDITOR' => [
                'remit:verify',
                'void:approve',
                'reports:view',
                'audit:view',
            ],
            'TREASURER' => [
                'pos:create',
                'remit:create',
            ],
            'SECRETARY' => [
                'attendance:record',
                'attendance:view',
            ],
        ];

        $permissionIds = DB::table('permissions')->pluck('id', 'slug');
        $rows = [];

        foreach ($rolePermissions as $role => $slugs) {
            foreach ($slugs as $slug) {
                if (!isset($permissionIds[$slug])) {
                    continue;
                }

                $rows[] = [
                    'user_id'       => $userIds[$role],
                    'permission_id' => $permissionIds[$slug],
                    'granted_at'    => $now,
                ];
            }
        }

        if ($rows) {
            DB::table('user_permissions')->insert($rows);
        }
    }

    /**
     * @param array<int, int> $studentIds
     */
    private function seedDraftEventWithAttendance(
        int $organizationId,
        int $academicYearId,
        int $chairpersonUserId,
        array $studentIds,
        mixed $now
    ): void {
        $eventId = DB::table('events')->insertGetId([
            'organization_id'              => $organizationId,
            'academic_year_id'             => $academicYearId,
            'name'                         => 'General Assembly',
            'date'                         => now()->addWeek()->toDateString(),
            'venue'                        => 'CISC AVR',
            'time_type'                    => 'FULL_DAY',
            'start_time'                   => '08:00:00',
            'end_time'                     => '17:00:00',
            'status'                       => 'DRAFT',
            'created_by_user_id'           => $chairpersonUserId,
            'submitted_by_user_id'         => null,
            'submitted_at'                 => null,
            'secretary_snapshot'           => null,
            'auditor_reviewed_by_user_id'  => null,
            'auditor_reviewed_at'          => null,
            'approved_by_user_id'          => null,
            'approved_at'                  => null,
            'rejection_reason'             => null,
            'created_at'                   => $now,
            'updated_at'                   => $now,
        ]);

        $slots = ['MORNING_IN', 'MORNING_OUT', 'AFTERNOON_IN', 'AFTERNOON_OUT'];
        $rows  = [];

        foreach ($studentIds as $studentId) {
            foreach ($slots as $slot) {
                $rows[] = [
                    'event_id'           => $eventId,
                    'student_id'         => $studentId,
                    'slot'               => $slot,
                    'is_present'         => false,
                    'recorded_by_user_id' => $chairpersonUserId,
                    'recorded_at'        => $now,
                    'updated_at'         => $now,
                ];
            }
        }

        DB::table('event_attendance')->insert($rows);
    }
}
