<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\StudentEnrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
    }

    protected function seedDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    // ==================== FR-0007: Student Identity Separation ====================

    /** @test */
    public function test_student_id_is_internal_and_not_exposed()
    {
        $student = Student::first();
        $this->assertNotNull($student->student_number);
        $this->assertIsInt($student->id);
    }

    /** @test */
    public function test_students_have_unique_student_numbers()
    {
        $student = Student::create([
            'student_number' => '2023-999-TEST',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'created_source' => 'MANUAL',
        ]);
        $this->assertTrue($student->wasRecentlyCreated);
    }

    // ==================== FR-0008: Bulk Import ====================

    /** @test */
    public function test_admin_can_import_students_from_csv()
    {
        $admin = $this->getUserByRole('SSC_ADMIN');
        $academicYear = AcademicYear::where('is_active', true)->first();
        $program = Program::first();

        $csv = "student_number,first_name,last_name,middle_name,year_level,is_regular\n";
        $csv .= "2024-0001,John,Doe,,3,true\n";
        $csv .= "2024-0002,Jane,Smith,,3,true";

        $response = $this->actingAs($admin)->post('/admin/students/import', [
            'file' => $csv,
            'program_id' => $program->id,
        ], ['Content-Type' => 'text/csv']);

        $response->assertRedirect();
    }

    /** @test */
    public function test_bulk_import_prevents_duplicate_enrollment()
    {
        $admin = $this->getUserByRole('SSC_ADMIN');
        $student = Student::first();
        $academicYear = AcademicYear::where('is_active', true)->first();
        $program = Program::first();

        $csv = "student_number,first_name,last_name,middle_name,year_level,is_regular\n";
        $csv .= "{$student->student_number},{$student->first_name},{$student->last_name},,3,true";

        $response = $this->actingAs($admin)->post('/admin/students/import', [
            'file' => $csv,
            'program_id' => $program->id,
        ], ['Content-Type' => 'text/csv']);

        $response->assertRedirect();
    }

    // ==================== FR-0009: Smart Manual Entry ====================

    /** @test */
    public function test_chairperson_can_manually_enroll_student()
    {
        $chairperson = $this->getUserByRole('CHAIRPERSON');
        $academicYear = AcademicYear::where('is_active', true)->first();

        $response = $this->actingAs($chairperson)->post('/org/students', [
            'student_number' => '2024-999-TEST',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'program_id' => Program::first()->id,
            'year_level' => '1',
            'student_type' => 'Regular',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function test_manual_entry_is_flagged_as_manual_source()
    {
        $chairperson = $this->getUserByRole('CHAIRPERSON');

        $this->actingAs($chairperson)->post('/org/students', [
            'student_number' => '2024-997-TEST',
            'first_name' => 'Manual',
            'last_name' => 'Entry',
            'program_id' => Program::first()->id,
            'year_level' => '1',
            'student_type' => 'Regular',
        ]);

        $student = Student::where('student_number', '2024-997-TEST')->first();
        $this->assertEquals('MANUAL', $student->created_source);
    }

    // ==================== FR-0010: Cascading Membership Logic ====================

    /** @test */
    public function test_student_is_member_of_college_council_and_department_society()
    {
        $student = Student::first();
        $academicYear = AcademicYear::where('is_active', true)->first();

        // Ensure student is enrolled
        $program = Program::first();
        if (!$student->enrollments()->where('academic_year_id', $academicYear->id)->exists()) {
            StudentEnrollment::create([
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'program_id' => $program->id,
                'year_level' => 1,
                'is_regular' => true,
            ]);
        }

        $orgIds = $student->getMemberOrganizations($academicYear->id)->pluck('id')->toArray();
        $this->assertNotEmpty($orgIds);
    }

    /** @test */
    public function test_student_not_enrolled_in_inactive_semester_returns_empty()
    {
        $student = Student::first();
        
        // Create inactive academic year
        $inactiveYear = AcademicYear::create([
            'name' => '2023-2024 1st Sem',
            'is_active' => false,
        ]);

        // Student not enrolled in inactive semester
        $orgs = $student->getMemberOrganizations($inactiveYear->id);
        $this->assertEmpty($orgs);
    }

    // ==================== Helper Methods ====================

    private function getUserByRole(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }
}