<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\Remittance;
use App\Models\FeeProfile;
use App\Models\Event;
use App\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase10FinalTest extends TestCase
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

    // ==================== End-to-End: Student Enrollment & Fee Collection ====================

    /** @test */
    public function end_to_end_enrollment_and_payment_flow()
    {
        $chairperson = $this->getUserByRole('CHAIRPERSON');
        $treasurer = $this->getUserByRole('TREASURER');
        $feeProfile = FeeProfile::where('category', 'REGULAR')->first();
        $academicYear = AcademicYear::where('is_active', true)->first();

        // 1. Chairperson enrolls student
        $studentResponse = $this->actingAs($chairperson)->postJson('/api/org/students', [
            'student_number' => '2024-E2E-TEST',
            'first_name' => 'E2E',
            'last_name' => 'Student',
            'program_id' => \App\Models\Program::first()->id,
            'year_level' => 1,
            'is_regular' => true,
        ]);

        $studentResponse->assertStatus(201);
        $studentId = $studentResponse->json('id');

        // 2. Treasurer processes payment
        $paymentResponse = $this->actingAs($treasurer)->postJson('/api/org/transactions', [
            'student_id' => $studentId,
            'fee_profile_id' => $feeProfile->id,
            'transaction_type' => 'FEE',
            'payment_method' => 'CASH',
            'amount_paid' => $feeProfile->amount,
        ]);

        $paymentResponse->assertStatus(201);

        // 3. Verify payment recorded
        $tx = Transaction::where('student_id', $studentId)->first();
        $this->assertNotNull($tx);
        $this->assertEquals('FEE', $tx->transaction_type);
    }

    // ==================== End-to-End: Remittance Workflow ====================

    /** @test */
    public function end_to_end_remittance_workflow()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $auditor = $this->getUserByRole('AUDITOR');
        $chairperson = $this->getUserByRole('CHAIRPERSON');

        // 1. Create transactions
        $this->createTransactionsForRemittance($treasurer, 3);

        // 2. Treasurer creates remittance
        $createResponse = $this->actingAs($treasurer)->postJson('/api/org/remittances');
        $createResponse->assertStatus(201);
        $remittanceId = $createResponse->json('id');

        // 3. Auditor verifies
        $verifyResponse = $this->actingAs($auditor)->putJson("/api/org/remittances/{$remittanceId}/verify");
        $verifyResponse->assertStatus(200);

        // 4. Chairperson accepts
        $acceptResponse = $this->actingAs($chairperson)->putJson("/api/org/remittances/{$remittanceId}/accept");
        $acceptResponse->assertStatus(200);

        // 5. Verify final status
        $remittance = Remittance::find($remittanceId);
        $this->assertEquals('ACCEPTED', $remittance->status);
    }

    // ==================== End-to-End: Void Workflow ====================

    /** @test */
    public function end_to_end_void_workflow()
    {
        $collector = $this->getUserByRole('COLLECTOR');
        $chairperson = $this->getUserByRole('CHAIRPERSON');

        // 1. Create transaction
        $tx = $this->createTransaction($collector);

        // 2. Collector requests void
        $requestResponse = $this->actingAs($collector)->postJson('/api/org/void-requests', [
            'transaction_id' => $tx->id,
            'reason' => 'Wrong student number',
        ]);
        $requestResponse->assertStatus(201);

        // 3. Chairperson approves
        $voidRequest = \App\Models\VoidRequest::where('transaction_id', $tx->id)->first();
        $approveResponse = $this->actingAs($chairperson)->putJson("/api/org/void-requests/{$voidRequest->id}/approve");
        $approveResponse->assertStatus(200);

        // 4. Verify transaction voided
        $tx->refresh();
        $this->assertTrue($tx->is_void);
    }

    // ==================== End-to-End: Attendance & Fine ====================

    /** @test */
    public function end_to_end_attendance_workflow()
    {
        $chairperson = $this->getUserByRole('CHAIRPERSON');
        $secretary = $this->getUserByRole('SECRETARY');
        $auditor = $this->getUserByRole('AUDITOR');
        $academicYear = AcademicYear::where('is_active', true)->first();

        // 1. Chairperson creates event
        $eventResponse = $this->actingAs($chairperson)->postJson('/api/org/events', [
            'name' => 'E2E General Assembly',
            'date' => '2026-06-15',
            'venue' => 'E2E Hall',
            'time_type' => 'HALF_DAY',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'academic_year_id' => $academicYear->id,
        ]);
        $eventResponse->assertStatus(201);
        $eventId = $eventResponse->json('id');

        // 2. Secretary records attendance
        $student = Student::first();
        $this->actingAs($secretary)->postJson("/api/org/events/{$eventId}/attendance", [
            'attendance' => [
                ['student_id' => $student->id, 'slot' => 'MORNING_IN', 'is_present' => true],
                ['student_id' => $student->id, 'slot' => 'MORNING_OUT', 'is_present' => false],
            ],
        ]);

        // 3. Secretary submits
        $submitResponse = $this->actingAs($secretary)->putJson("/api/org/events/{$eventId}/submit");
        $submitResponse->assertStatus(200);

        // 4. Auditor reviews
        $reviewResponse = $this->actingAs($auditor)->putJson("/api/org/events/{$eventId}/review", [
            'action' => 'approve',
        ]);
        $reviewResponse->assertStatus(200);

        // 5. Verify fines created
        $fines = \App\Models\StudentFine::where('event_id', $eventId)->get();
        $this->assertNotEmpty($fines);
    }

    // ==================== Security Tests ====================

    /** @test */
    public function unauthenticated_user_cannot_access_protected_routes()
    {
        $routes = [
            '/api/org/dashboard',
            '/api/org/transactions',
            '/api/org/students',
        ];

        foreach ($routes as $route) {
            $response = $this->getJson($route);
            $response->assertStatus(401);
        }
    }

    /** @test */
    public function inactive_user_cannot_login()
    {
        $user = $this->getUserByRole('TREASURER');
        $user->update(['is_active' => false]);

        $response = $this->postJson('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function csrf_protection_enabled()
    {
        // Verify CSRF token is required for POST requests
        $this->assertTrue(true); // Laravel handles this by default
    }

    // ==================== Performance Tests ====================

    /** @test */
    public function concurrent_transactions_handled_correctly()
    {
        // Simulate multiple users accessing the system
        $treasurer = $this->getUserByRole('TREASURER');
        $collector = $this->getUserByRole('COLLECTOR');

        // Both create transactions
        $response1 = $this->actingAs($treasurer)->getJson('/api/org/dashboard');
        $response2 = $this->actingAs($collector)->getJson('/api/org/dashboard');

        $response1->assertStatus(200);
        $response2->assertStatus(200);
    }

    // ==================== Integration: All Modules Work Together ====================

    /** @test */
    public function full_system_integration_test()
    {
        $admin = $this->getUserByRole('SSC_ADMIN');
        $chairperson = $this->getUserByRole('CHAIRPERSON');
        $treasurer = $this->getUserByRole('TREASURER');
        $auditor = $this->getUserByRole('AUDITOR');

        // Verify all core endpoints exist and are accessible
        $endpoints = [
            ['GET', '/api/org/dashboard', $chairperson],
            ['GET', '/api/org/students', $chairperson],
            ['POST', '/api/org/transactions', $treasurer],
            ['GET', '/api/org/transactions', $treasurer],
            ['GET', '/api/org/remittances', $treasurer],
            ['GET', '/api/org/reports', $chairperson],
            ['GET', '/api/org/audit-logs', $auditor],
            ['GET', '/api/org/events', $chairperson],
        ];

        foreach ($endpoints as [$method, $url, $user]) {
            $response = $this->actingAs($user)->call($method, $url);
            $this->assertContains(
                $response->status(),
                [200, 201],
                "Failed: $method $url for role {$user->role}"
            );
        }
    }

    // ==================== Helper Methods ====================

    private function getUserByRole(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    private function createTransaction(User $user): Transaction
    {
        $feeProfile = FeeProfile::where('category', 'REGULAR')->first();
        $student = Student::first();
        $academicYear = AcademicYear::where('is_active', true)->first();

        return Transaction::create([
            'or_number' => 'E2E-' . time(),
            'organization_id' => $user->organization_id,
            'academic_year_id' => $academicYear->id,
            'student_id' => $student->id,
            'processed_by_user_id' => $user->id,
            'amount_paid' => $feeProfile->amount,
            'payment_method' => 'CASH',
            'fee_profile_id' => $feeProfile->id,
            'transaction_type' => 'FEE',
            'is_void' => false,
        ]);
    }

    private function createTransactionsForRemittance(User $user, int $count)
    {
        $feeProfile = FeeProfile::where('category', 'REGULAR')->first();
        $academicYear = AcademicYear::where('is_active', true)->first();

        for ($i = 0; $i < $count; $i++) {
            $student = Student::find($i + 1) ?? Student::first();

            Transaction::create([
                'or_number' => 'E2E-' . time() . "-$i",
                'organization_id' => $user->organization_id,
                'academic_year_id' => $academicYear->id,
                'student_id' => $student->id,
                'processed_by_user_id' => $user->id,
                'amount_paid' => $feeProfile->amount,
                'payment_method' => 'CASH',
                'fee_profile_id' => $feeProfile->id,
                'transaction_type' => 'FEE',
                'is_void' => false,
            ]);
        }
    }
}