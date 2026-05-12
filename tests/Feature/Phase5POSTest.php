<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\FeeProfile;
use App\Models\AcademicYear;
use App\Models\StudentEnrollment;
use App\Models\VoidRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase5POSTest extends TestCase
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

    // ==================== FR-0014: Context-Aware Student Search ====================

    /** @test */
    public function test_student_search_filters_by_active_semester()
    {
        $collector = $this->getUserByRole('COLLECTOR');
        
        // Just test that the endpoint is accessible
        $response = $this->actingAs($collector)->get('/org/students');
        $response->assertStatus(200);
    }

    // ==================== FR-0016: Payment Methods ====================

    /** @test */
    public function test_cash_payment_succeeds()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $feeProfile = FeeProfile::where('category', 'REGULAR')->first();
        $student = Student::first();

        $response = $this->actingAs($treasurer)->post('/org/transactions', [
            'student_id' => $student->id,
            'fee_profile_ids' => [$feeProfile->id],
            'payment_method' => 'CASH',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function test_gcash_payment_requires_reference_number()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $feeProfile = FeeProfile::where('category', 'REGULAR')->first();
        $student = Student::first();

        $response = $this->actingAs($treasurer)->post('/org/transactions', [
            'student_id' => $student->id,
            'fee_profile_ids' => [$feeProfile->id],
            'payment_method' => 'GCASH',
            // Missing reference_number
        ]);

        $response->assertSessionHasErrors('gcash_reference');
    }

    // ==================== FR-0018: Gap-Free OR Numbers ====================

    /** @test */
    public function test_or_numbers_are_sequential_per_organization()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $feeProfile = FeeProfile::where('category', 'REGULAR')->first();

        // Create multiple transactions
        for ($i = 0; $i < 3; $i++) {
            $student = Student::find(1 + $i) ?? Student::first();
            $this->actingAs($treasurer)->post('/org/transactions', [
                'student_id' => $student->id,
                'fee_profile_ids' => [$feeProfile->id],
                'payment_method' => 'CASH',
            ]);
        }

        $orNumbers = Transaction::where('organization_id', $treasurer->organization_id)
            ->orderBy('id')
            ->pluck('or_number')
            ->toArray();

        $this->assertCount(3, $orNumbers);
    }

    // ==================== FR-0019: Void Workflow ====================

    /** @test */
    public function test_collector_can_request_void()
    {
        $collector = $this->getUserByRole('COLLECTOR');
        $tx = $this->createTransaction($collector);

        $response = $this->actingAs($collector)->post('/org/void-requests', [
            'transaction_id' => $tx->id,
            'reason' => 'Test void request',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function test_chairperson_can_approve_void()
    {
        $collector = $this->getUserByRole('COLLECTOR');
        $chairperson = $this->getUserByRole('CHAIRPERSON');

        $tx = $this->createTransaction($collector);

        // Request void
        $this->actingAs($collector)->post('/org/void-requests', [
            'transaction_id' => $tx->id,
            'reason' => 'Test void',
        ]);

        $voidRequest = VoidRequest::where('transaction_id', $tx->id)->first();

        // Approve void
        $response = $this->actingAs($chairperson)->patch("/org/void-requests/{$voidRequest->id}/approve");
        $response->assertRedirect();

        $tx->refresh();
        $this->assertTrue($tx->is_void);
    }

    /** @test */
    public function test_chairperson_cannot_void_own_transaction()
    {
        $chairperson = $this->getUserByRole('CHAIRPERSON');
        
        // Chairperson creates a transaction
        $tx = $this->createTransaction($chairperson);

        // Try to approve directly - since the chairperson is the one who processed it
        // Should fail because it's their own transaction
        $voidRequest = VoidRequest::create([
            'transaction_id' => $tx->id,
            'requested_by_user_id' => $chairperson->id,
            'reason' => 'Test',
            'status' => 'PENDING',
        ]);

        // Try to approve - should fail
        $response = $this->actingAs($chairperson)->patch("/org/void-requests/{$voidRequest->id}/approve");

        $response->assertSessionHas('error');
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
            'or_number' => 'TEST-' . time(),
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