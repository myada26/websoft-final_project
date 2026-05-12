<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\AcademicYear;
use App\Models\Organization;
use App\Models\Program;
use App\Models\StudentEnrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceToggleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
        $this->createTestData();
    }

    protected function seedDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    protected function createTestData(): void
    {
        $activeSem = AcademicYear::where('is_active', true)->first();
        $org = Organization::where('type', 'COLLEGE_COUNCIL')->first();
        $chairperson = User::where('role', 'CHAIRPERSON')->first();
        $program = Program::where('code', 'BSCE')->first();

        $event = Event::create([
            'organization_id' => $org->id,
            'academic_year_id' => $activeSem->id,
            'name' => 'Test Event',
            'date' => now()->addDays(7),
            'venue' => 'Test Venue',
            'time_type' => 'HALF_DAY',
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'status' => 'DRAFT',
            'created_by_user_id' => $chairperson->id,
        ]);

        $students = StudentEnrollment::where('academic_year_id', $activeSem->id)
            ->where('program_id', $program->id)
            ->with('student')
            ->limit(5)
            ->get();

        foreach ($students as $enrollment) {
            foreach (['MORNING_IN', 'MORNING_OUT'] as $slot) {
                EventAttendance::create([
                    'event_id' => $event->id,
                    'student_id' => $enrollment->student_id,
                    'slot' => $slot,
                    'is_present' => false,
                ]);
            }
        }
    }

    public function test_secretary_can_toggle_attendance_slot()
    {
        $secretary = User::where('role', 'SECRETARY')->first();
        $event = Event::where('status', 'DRAFT')->first();
        
        $this->assertNotNull($secretary, 'Secretary user not found');
        $this->assertNotNull($event, 'DRAFT event not found');

        $attendance = EventAttendance::where('event_id', $event->id)->first();
        $slot = $attendance->slot;

        $response = $this->actingAs($secretary)
            ->patchJson(route('org.attendance.toggle-slot', [
                'event' => $event->id,
                'student' => $attendance->student_id,
                'slot' => $slot,
            ]));

        $response->assertStatus(200);
        $response->assertJson(['is_present' => true]);

        $attendance->refresh();
        $this->assertTrue($attendance->is_present);
        $this->assertEquals($secretary->id, $attendance->recorded_by_user_id);
    }

    public function test_secretary_cannot_toggle_non_draft_event()
    {
        $secretary = User::where('role', 'SECRETARY')->first();
        $event = Event::where('status', 'DRAFT')->first();
        
        $event->update(['status' => 'APPROVED']);

        $attendance = EventAttendance::where('event_id', $event->id)->first();

        $response = $this->actingAs($secretary)
            ->patchJson(route('org.attendance.toggle-slot', [
                'event' => $event->id,
                'student' => $attendance->student_id,
                'slot' => $attendance->slot,
            ]));

        $response->assertStatus(403);
    }

    public function test_secretary_cannot_toggle_invalid_slot()
    {
        $secretary = User::where('role', 'SECRETARY')->first();
        $event = Event::where('status', 'DRAFT')->first();
        
        $attendance = EventAttendance::where('event_id', $event->id)->first();

        $response = $this->actingAs($secretary)
            ->patchJson(route('org.attendance.toggle-slot', [
                'event' => $event->id,
                'student' => $attendance->student_id,
                'slot' => 'INVALID_SLOT',
            ]));

        $response->assertStatus(422);
    }

    public function test_auditor_can_toggle_pending_approval_event()
    {
        $auditor = User::where('role', 'AUDITOR')->first();
        $event = Event::where('status', 'DRAFT')->first();
        
        $event->update(['status' => 'PENDING_APPROVAL']);

        $attendance = EventAttendance::where('event_id', $event->id)->first();

        $response = $this->actingAs($auditor)
            ->patchJson(route('org.attendance.toggle-slot', [
                'event' => $event->id,
                'student' => $attendance->student_id,
                'slot' => $attendance->slot,
            ]));

        $response->assertStatus(200);
    }

    public function test_attendance_sheet_shows_students_from_secretary_program()
    {
        $secretary = User::where('role', 'SECRETARY')->first();
        $event = Event::where('status', 'DRAFT')->first();

        $response = $this->actingAs($secretary)
            ->get(route('org.attendance.sheet', ['event' => $event->id]));

        $response->assertStatus(200);
        $response->assertSee('BS Civil Engineering');
    }

    public function test_attendance_sheet_shows_year_level_groups()
    {
        $secretary = User::where('role', 'SECRETARY')->first();
        $event = Event::where('status', 'DRAFT')->first();

        $response = $this->actingAs($secretary)
            ->get(route('org.attendance.sheet', ['event' => $event->id]));

        $response->assertStatus(200);
        $response->assertSee('1st Year');
    }

    public function test_attendance_sheet_search_filters_students()
    {
        $secretary = User::where('role', 'SECRETARY')->first();
        $event = Event::where('status', 'DRAFT')->first();

        $response = $this->actingAs($secretary)
            ->get(route('org.attendance.sheet', ['event' => $event->id, 'search' => 'Brigoli']));

        $response->assertStatus(200);
    }
}