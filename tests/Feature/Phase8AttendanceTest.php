<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Student;
use App\Models\StudentFine;
use App\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase8AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    // ==================== FR-0026: Event Management ====================

    public function test_chairperson_can_create_event()
    {
        $chairperson = $this->getUserByRole('CHAIRPERSON');
        $academicYear = AcademicYear::where('is_active', true)->first();

        $response = $this->actingAs($chairperson)->post('/org/events', [
            'name' => 'General Assembly',
            'date' => '2026-06-01',
            'venue' => 'Main Hall',
            'time_type' => 'HALF_DAY',
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('events', ['name' => 'General Assembly']);
    }

    public function test_event_scoped_to_organization()
    {
        $chairperson = $this->getUserByRole('CHAIRPERSON');
        $event = $this->createEvent($chairperson);

        $this->assertEquals($chairperson->organization_id, $event->organization_id);
    }

    // ==================== FR-0027: Attendance Sheet UI ====================

    public function test_secretary_can_view_attendance_sheet()
    {
        $secretary = $this->getUserByRole('SECRETARY');
        $chairperson = $this->getUserByRole('CHAIRPERSON');
        $event = $this->createEvent($chairperson);

        $response = $this->actingAs($secretary)->get("/org/events/{$event->id}/attendance");

        $response->assertStatus(200);
    }

    // ==================== FR-0028: Submission & Approval Workflow ====================

    public function test_secretary_can_submit_attendance()
    {
        $secretary = $this->getUserByRole('SECRETARY');
        $chairperson = $this->getUserByRole('CHAIRPERSON');
        $event = $this->createEvent($chairperson);

        $response = $this->actingAs($secretary)->post("/org/events/{$event->id}/attendance/submit");

        $response->assertRedirect();
        $event->refresh();
        $this->assertEquals('PENDING_APPROVAL', $event->status);
    }

    public function test_auditor_can_approve_attendance()
    {
        $auditor = $this->getUserByRole('AUDITOR');
        $chairperson = $this->getUserByRole('CHAIRPERSON');
        $event = $this->createEvent($chairperson, 'PENDING_APPROVAL');

        $response = $this->actingAs($auditor)->patch("/org/events/{$event->id}/attendance/auditor-approve");

        $response->assertRedirect();
        $event->refresh();
        $this->assertEquals('APPROVED', $event->status);
    }

    public function test_auditor_can_forward_to_chairperson()
    {
        $auditor = $this->getUserByRole('AUDITOR');
        $chairperson = $this->getUserByRole('CHAIRPERSON');
        $event = $this->createEvent($chairperson, 'PENDING_APPROVAL');

        $response = $this->actingAs($auditor)->patch("/org/events/{$event->id}/attendance/auditor-forward");

        $response->assertRedirect();
        $event->refresh();
        $this->assertEquals('PENDING_CHAIRPERSON', $event->status);
    }

    public function test_chairperson_can_final_approve()
    {
        $chairperson = $this->getUserByRole('CHAIRPERSON');
        $event = $this->createEvent($chairperson, 'PENDING_CHAIRPERSON');

        $response = $this->actingAs($chairperson)->patch("/org/events/{$event->id}/attendance/chairperson-confirm");

        $response->assertRedirect();
        $event->refresh();
        $this->assertEquals('APPROVED', $event->status);
    }

    // ==================== FR-0029: Automatic Fine Computation ====================

    public function test_approved_event_computes_fines()
    {
        $auditor = $this->getUserByRole('AUDITOR');
        $chairperson = $this->getUserByRole('CHAIRPERSON');
        $event = $this->createEvent($chairperson, 'PENDING_APPROVAL');

        // Create absent attendance
        $student = Student::first();
        EventAttendance::create([
            'event_id' => $event->id,
            'student_id' => $student->id,
            'slot' => 'MORNING_IN',
            'is_present' => false,
            'recorded_by_user_id' => $chairperson->id,
        ]);

        // Approve triggers fine computation
        $this->actingAs($auditor)->patch("/org/events/{$event->id}/attendance/auditor-approve");

        $fineCount = StudentFine::where('event_id', $event->id)->count();
        $this->assertGreaterThan(0, $fineCount);
    }

    // ==================== FR-0030: Student-Facing Accountability View ====================

    public function test_public_check_fees_endpoint_accessible()
    {
        $response = $this->get('/check-fees');
        $response->assertStatus(200);
    }

    public function test_check_fees_shows_student_info()
    {
        $student = Student::first();

        $response = $this->get('/check-fees?student_number=' . $student->student_number);

        $response->assertStatus(200);
    }

    // ==================== Helper Methods ====================

    private function getUserByRole(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    private function createEvent(User $user, string $status = 'DRAFT'): Event
    {
        $academicYear = AcademicYear::where('is_active', true)->first();

        return Event::create([
            'organization_id' => $user->organization_id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Test Event',
            'date' => '2026-06-01',
            'venue' => 'Test Venue',
            'time_type' => 'HALF_DAY',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'status' => $status,
            'created_by_user_id' => $user->id,
        ]);
    }
}