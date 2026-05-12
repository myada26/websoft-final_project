<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\FineCollectionWindow;
use App\Models\Student;
use App\Models\StudentFine;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinePaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedDatabase();
    }

    protected function seedDatabase(): void
    {
        $academicYear = \App\Models\AcademicYear::create([
            'name' => '2024-2025 2nd Sem',
            'is_active' => true,
        ]);

        $college = \App\Models\College::create([
            'name' => 'College of Engineering',
            'code' => 'COE',
            'is_active' => true,
        ]);

        $department = \App\Models\Department::create([
            'college_id' => $college->id,
            'name' => 'Civil Engineering',
            'code' => 'CE',
            'is_active' => true,
        ]);

        $program = \App\Models\Program::create([
            'department_id' => $department->id,
            'name' => 'BS Civil Engineering',
            'code' => 'BSCE',
            'is_active' => true,
        ]);

        $organization = \App\Models\Organization::create([
            'name' => 'COE Council',
            'type' => 'COLLEGE_COUNCIL',
            'linked_college_id' => $college->id,
            'is_active' => true,
        ]);

$student = Student::create([
            'student_number' => '2024-0001',
            'first_name' => 'Boon Jefferson',
            'last_name' => 'Brigoli',
            'middle_name' => 'S.',
            'email' => 'boonjefferson@cmu.edu.ph',
            'created_source' => 'MANUAL',
        ]);

        $treasurerStudent = Student::create([
            'student_number' => '2024-0002',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'middle_name' => 'A.',
            'email' => 'john.doe@cmu.edu.ph',
            'created_source' => 'MANUAL',
        ]);

        \App\Models\StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'program_id' => $program->id,
            'year_level' => 2,
            'is_regular' => true,
        ]);

        \App\Models\StudentEnrollment::create([
            'student_id' => $treasurerStudent->id,
            'academic_year_id' => $academicYear->id,
            'program_id' => $program->id,
            'year_level' => 3,
            'is_regular' => true,
        ]);

        $collector = User::create([
            'student_id' => $student->id,
            'organization_id' => $organization->id,
            'username' => '2024-0001-COE',
            'password_hash' => bcrypt('password'),
            'role' => 'CHAIRPERSON',
            'is_active' => true,
        ]);

        $treasurer = User::create([
            'student_id' => $treasurerStudent->id,
            'organization_id' => $organization->id,
            'username' => '2024-0002-COE',
            'password_hash' => bcrypt('password'),
            'role' => 'TREASURER',
            'is_active' => true,
        ]);

        $event = \App\Models\Event::create([
            'organization_id' => $organization->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'COE Night',
            'date' => now()->addDays(7)->format('Y-m-d'),
            'venue' => 'CMU Grandstand',
            'time_type' => 'FULL_DAY',
            'start_time' => '18:00:00',
            'end_time' => '23:00:00',
            'status' => 'APPROVED',
            'created_by_user_id' => $collector->id,
            'approved_by_user_id' => $collector->id,
            'approved_at' => now(),
        ]);

        $studentFine = StudentFine::create([
            'student_id' => $student->id,
            'organization_id' => $organization->id,
            'event_id' => $event->id,
            'academic_year_id' => $academicYear->id,
            'slots_missed' => 4,
            'fine_amount' => 40.00,
            'status' => 'UNPAID',
        ]);

        FineCollectionWindow::create([
            'organization_id' => $organization->id,
            'academic_year_id' => $academicYear->id,
            'opened_by_user_id' => $collector->id,
            'opened_at' => now(),
            'status' => 'OPEN',
        ]);
    }

    public function test_fine_payment_flow(): void
    {
        $student = Student::where('student_number', '2024-0001')->first();
        $this->assertNotNull($student, 'Student should exist');

        $unpaidFines = StudentFine::where('student_id', $student->id)
            ->where('status', 'UNPAID')
            ->get();

        $this->assertGreaterThan(0, $unpaidFines->count(), 'Student should have unpaid fines');

        $fine = $unpaidFines->first();
        $this->assertEquals(40.00, $fine->fine_amount, 'Fine amount should be ₱40.00');
        $this->assertEquals('UNPAID', $fine->status, 'Fine status should be UNPAID');

        $this->assertNotNull($student->latestEnrollment, 'Student should be enrolled');
        $programCode = $student->latestEnrollment->program->code ?? '';
        $this->assertEquals('BSCE', $programCode, 'Student should be in BSCE program');

        $user = User::where('role', 'TREASURER')->first();
        $this->actingAs($user);

        $response = $this->post('/org/transactions/fine', [
            'student_id' => $student->id,
            'payment_method' => 'CASH',
            'amount_paid' => 40.00,
            'student_fine_id' => $fine->id,
        ]);

        $response->assertSessionHas('success');

        $fine->refresh();
        $this->assertEquals('PAID', $fine->status, 'Fine status should be PAID after payment');
        $this->assertNotNull($fine->transaction_id, 'Fine should have a transaction linked');
    }

    public function test_student_fine_status_after_payment(): void
    {
        $student = Student::where('student_number', '2024-0001')->first();
        $fine = StudentFine::where('student_id', $student->id)
            ->where('status', 'UNPAID')
            ->first();

        $this->assertNotNull($fine, 'Unpaid fine should exist');

        $user = User::where('role', 'TREASURER')->first();
        $this->actingAs($user);

        $response = $this->post('/org/transactions/fine', [
            'student_id' => $student->id,
            'payment_method' => 'CASH',
            'amount_paid' => $fine->fine_amount,
            'student_fine_id' => $fine->id,
        ]);

        $response->assertSessionHas('success');

        $fine->refresh();
        $this->assertEquals('PAID', $fine->status);

        $transaction = Transaction::find($fine->transaction_id);
        $this->assertNotNull($transaction, 'Transaction should exist');
        $this->assertEquals('FINE', $transaction->transaction_type);
        $this->assertEquals($fine->fine_amount, $transaction->amount_paid);
    }

    public function test_partial_payment_not_allowed(): void
    {
        $student = Student::where('student_number', '2024-0001')->first();
        $fine = StudentFine::where('student_id', $student->id)
            ->where('status', 'UNPAID')
            ->first();

        $user = User::where('role', 'TREASURER')->first();
        $this->actingAs($user);

        $response = $this->post('/org/transactions/fine', [
            'student_id' => $student->id,
            'payment_method' => 'CASH',
            'amount_paid' => 20.00,
            'student_fine_id' => $fine->id,
        ]);

        $response->assertSessionHas('error');

        $fine->refresh();
        $this->assertEquals('UNPAID', $fine->status, 'Fine should remain UNPAID after failed payment');
    }
}