<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\College;
use App\Models\Department;
use App\Models\FeeProfile;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummaryOfReceiptsReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
    }

    protected function seedDatabase(): void
    {
        $academicYear = AcademicYear::create([
            'name' => '2024-2025 2nd Sem',
            'is_active' => true,
        ]);

        $college = College::create([
            'name' => 'College of Engineering',
            'code' => 'COE',
            'is_active' => true,
        ]);

        $department = Department::create([
            'college_id' => $college->id,
            'name' => 'Civil Engineering',
            'code' => 'CE',
            'is_active' => true,
        ]);

        $program = Program::create([
            'department_id' => $department->id,
            'name' => 'BS Civil Engineering',
            'code' => 'BSCE',
            'is_active' => true,
        ]);

        $organization = Organization::create([
            'name' => 'COE Council',
            'type' => 'COLLEGE_COUNCIL',
            'linked_college_id' => $college->id,
            'is_active' => true,
        ]);

        FeeProfile::create([
            'organization_id' => $organization->id,
            'name' => 'COE Membership Fee',
            'amount' => 150.00,
            'category' => 'REGULAR',
            'is_active' => true,
        ]);

        $chairperson = User::create([
            'username' => '2023-002-COE',
            'password_hash' => bcrypt('password'),
            'role' => 'CHAIRPERSON',
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 50; $i++) {
            $student = Student::create([
                'student_number' => sprintf('2024-%04d', $i),
                'first_name' => "Student{$i}",
                'last_name' => 'Test',
                'middle_name' => 'A',
                'email' => "student{$i}@cmu.edu.ph",
                'created_source' => 'MANUAL',
            ]);

            StudentEnrollment::create([
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'program_id' => $program->id,
                'year_level' => rand(1, 4),
                'is_regular' => true,
            ]);
        }

        $orSequence = \App\Models\OrSequence::create([
            'organization_id' => $organization->id,
            'last_or_number' => 0,
        ]);

        for ($i = 1; $i <= 50; $i++) {
            $orSequence->increment('last_or_number');
            Transaction::create([
                'or_number' => sprintf('OR-2026-%05d', $orSequence->last_or_number),
                'organization_id' => $organization->id,
                'academic_year_id' => $academicYear->id,
                'student_id' => rand(1, 50),
                'processed_by_user_id' => $chairperson->id,
                'amount_paid' => 150.00,
                'payment_method' => 'CASH',
                'fee_profile_id' => 1,
                'transaction_type' => 'FEE',
                'is_void' => false,
            ]);
        }
    }

    public function test_sor_report_displays_correct_statistics(): void
    {
        $user = User::where('role', 'CHAIRPERSON')->first();
        $this->actingAs($user);

        $response = $this->get('/org/reports/sor');

        $response->assertStatus(200);
        $response->assertSee('Summary of Receipts');
        $response->assertSee('COE Council');
        $response->assertSee('50');
    }

    public function test_sor_report_calculates_total_collected(): void
    {
        $user = User::where('role', 'CHAIRPERSON')->first();
        $this->actingAs($user);

        $response = $this->get('/org/reports/sor');

        $response->assertStatus(200);
        $response->assertSee('₱7,500');
    }

    public function test_sor_report_calculates_receipt_count(): void
    {
        $user = User::where('role', 'CHAIRPERSON')->first();
        $this->actingAs($user);

        $response = $this->get('/org/reports/sor');

        $response->assertStatus(200);
        $response->assertSee('Receipts issued');
        $response->assertSee('50');
    }

    public function test_sor_report_shows_batch_summary(): void
    {
        $user = User::where('role', 'CHAIRPERSON')->first();
        $this->actingAs($user);

        $response = $this->get('/org/reports/sor');

        $response->assertStatus(200);
        $response->assertSee('OR range');
        $response->assertSee('Receipt batch summary');
    }

    public function test_sor_report_calculates_or_min_max(): void
    {
        $user = User::where('role', 'CHAIRPERSON')->first();
        $this->actingAs($user);

        $response = $this->get('/org/reports/sor');

        $response->assertStatus(200);
        $response->assertSee('OR-2026-00001');
        $response->assertSee('OR-2026-00050');
    }

    public function test_sor_report_excludes_void_transactions(): void
    {
        $user = User::where('role', 'CHAIRPERSON')->first();
        $this->actingAs($user);

        $organization = $user->organization;
        $academicYear = AcademicYear::where('is_active', true)->first();

        Transaction::create([
            'or_number' => 'OR-2026-00099',
            'organization_id' => $organization->id,
            'academic_year_id' => $academicYear->id,
            'student_id' => 1,
            'processed_by_user_id' => $user->id,
            'amount_paid' => 150.00,
            'payment_method' => 'CASH',
            'fee_profile_id' => 1,
            'transaction_type' => 'FEE',
            'is_void' => true,
        ]);

        $response = $this->get('/org/reports/sor');

        $response->assertStatus(200);
        $response->assertDontSee('OR-2026-00099');
    }

    public function test_sor_report_fine_transactions_included(): void
    {
        $user = User::where('role', 'CHAIRPERSON')->first();
        $this->actingAs($user);
        
        $organization = $user->organization;
        $academicYear = AcademicYear::where('is_active', true)->first();

        $event = \App\Models\Event::create([
            'organization_id' => $organization->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'Test Event',
            'date' => now()->format('Y-m-d'),
            'venue' => 'Test Venue',
            'time_type' => 'HALF_DAY',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status' => 'APPROVED',
            'created_by_user_id' => $user->id,
            'approved_by_user_id' => $user->id,
            'approved_at' => now(),
        ]);

        $fine = \App\Models\StudentFine::create([
            'student_id' => 1,
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'academic_year_id' => $academicYear->id,
            'slots_missed' => 4,
            'fine_amount' => 40.00,
            'status' => 'PAID',
        ]);

        $orSequence = \App\Models\OrSequence::where('organization_id', $organization->id)->first();
        $orSequence->increment('last_or_number');
        
        Transaction::create([
            'or_number' => sprintf('OR-2026-%05d', $orSequence->last_or_number),
            'organization_id' => $organization->id,
            'academic_year_id' => $academicYear->id,
            'student_id' => 1,
            'processed_by_user_id' => $user->id,
            'amount_paid' => 40.00,
            'payment_method' => 'CASH',
            'transaction_type' => 'FINE',
            'student_fine_id' => $fine->id,
            'is_void' => false,
        ]);

        $response = $this->get('/org/reports/sor');

        $response->assertStatus(200);
        $response->assertSee('Fines collected');
    }

    public function test_sor_report_only_accessible_by_chairperson(): void
    {
        $treasurer = User::create([
            'username' => '2023-003-COE',
            'password_hash' => bcrypt('password'),
            'role' => 'TREASURER',
            'organization_id' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($treasurer);
        $response = $this->get('/org/reports/sor');
        $response->assertStatus(403);
    }

    public function test_sor_report_shows_semester_and_academic_year(): void
    {
        $user = User::where('role', 'CHAIRPERSON')->first();
        $this->actingAs($user);

        $response = $this->get('/org/reports/sor');

        $response->assertStatus(200);
        $response->assertSee('2024-2025 2nd Sem');
    }
}