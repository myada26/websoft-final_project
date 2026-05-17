<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventsSeeder extends Seeder
{
    public function run(): void
    {
        $coeOrgId = DB::table('organizations')->where('name', 'COE Council')->first()?->id;
        $chairpersonUserId = DB::table('users')->where('role', 'CHAIRPERSON')->first()?->id;
        $academicYearId = DB::table('academic_years')->where('is_active', true)->first()?->id;

        if (!$coeOrgId || !$chairpersonUserId || !$academicYearId) {
            $this->command->warn('Required data not found. Skipping events seeding.');
            return;
        }

        DB::table('fine_collection_windows')->insert([
            'organization_id' => $coeOrgId,
            'academic_year_id' => $academicYearId,
            'opened_by_user_id' => $chairpersonUserId,
            'opened_at' => now(),
            'status' => 'OPEN',
        ]);

        $halfDayEventId = DB::table('events')->insertGetId([
            'organization_id' => $coeOrgId,
            'academic_year_id' => $academicYearId,
            'name' => 'General Assembly',
            'date' => now()->addDays(7)->format('Y-m-d'),
            'venue' => 'COE Auditorium',
            'time_type' => 'HALF_DAY',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status' => 'APPROVED',
            'created_by_user_id' => $chairpersonUserId,
            'approved_by_user_id' => $chairpersonUserId,
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->populateAttendance($halfDayEventId, $academicYearId, ['MORNING_IN', 'MORNING_OUT']);

        $fullDayEventId = DB::table('events')->insertGetId([
            'organization_id' => $coeOrgId,
            'academic_year_id' => $academicYearId,
            'name' => 'General Assembly (Full Day)',
            'date' => now()->addDays(14)->format('Y-m-d'),
            'venue' => 'COE Auditorium',
            'time_type' => 'FULL_DAY',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 'APPROVED',
            'created_by_user_id' => $chairpersonUserId,
            'approved_by_user_id' => $chairpersonUserId,
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->populateAttendance($fullDayEventId, $academicYearId, ['MORNING_IN', 'MORNING_OUT', 'AFTERNOON_IN', 'AFTERNOON_OUT']);

        $draftEventId = DB::table('events')->insertGetId([
            'organization_id' => $coeOrgId,
            'academic_year_id' => $academicYearId,
            'name' => 'COE Night',
            'date' => now()->addDays(21)->format('Y-m-d'),
            'venue' => 'CMU Grandstand',
            'time_type' => 'FULL_DAY',
            'start_time' => '18:00:00',
            'end_time' => '23:00:00',
            'status' => 'DRAFT',
            'created_by_user_id' => $chairpersonUserId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->populateAttendance($draftEventId, $academicYearId, ['MORNING_IN', 'MORNING_OUT', 'AFTERNOON_IN', 'AFTERNOON_OUT']);
    }

    private function populateAttendance(int $eventId, int $academicYearId, array $slots): void
    {
        $studentIds = DB::table('student_enrollments')
            ->where('academic_year_id', $academicYearId)
            ->pluck('student_id');

        $rows = [];
        foreach ($studentIds as $studentId) {
            foreach ($slots as $slot) {
                $rows[] = [
                    'event_id' => $eventId,
                    'student_id' => $studentId,
                    'slot' => $slot,
                    'is_present' => false,
                    'recorded_by_user_id' => null,
                    'updated_at' => now(),
                ];
            }
        }

        if ($rows !== []) {
            DB::table('event_attendance')->insertOrIgnore($rows);
        }
    }
}
