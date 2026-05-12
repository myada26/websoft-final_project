<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Remittance;
use App\Models\FeeProfile;
use App\Models\Student;
use App\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase6RemittanceTest extends TestCase
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

    // ==================== FR-0020: Smart Remittance Creation ====================

    /** @test */
    public function test_treasurer_can_create_remittance()
    {
        $treasurer = $this->getUserByRole('TREASURER');

        // Create unremitted transactions
        $this->createUnremittedTransactions($treasurer, 3);

        $response = $this->actingAs($treasurer)->post('/org/remittances');

        $response->assertRedirect();
    }

    // ==================== FR-0021: Three-Stage Verification ====================

    /** @test */
    public function test_auditor_can_verify_remittance()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $auditor = $this->getUserByRole('AUDITOR');

        // Create remittance
        $this->createUnremittedTransactions($treasurer, 2);
        $remittance = $this->createRemittance($treasurer);

        // Verify
        $response = $this->actingAs($auditor)->patch("/org/remittances/{$remittance->id}/verify");

        $response->assertRedirect();
        $remittance->refresh();
        $this->assertEquals('VERIFIED', $remittance->status);
    }

    /** @test */
    public function test_chairperson_can_accept_verified_remittance()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $auditor = $this->getUserByRole('AUDITOR');
        $chairperson = $this->getUserByRole('CHAIRPERSON');

        // Create and verify remittance
        $this->createUnremittedTransactions($treasurer, 2);
        $remittance = $this->createRemittance($treasurer);

        $this->actingAs($auditor)->patch("/org/remittances/{$remittance->id}/verify");

        // Accept
        $response = $this->actingAs($chairperson)->patch("/org/remittances/{$remittance->id}/accept");

        $response->assertRedirect();
        $remittance->refresh();
        $this->assertEquals('ACCEPTED', $remittance->status);
    }

    /** @test */
    public function test_cannot_accept_before_verify()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $chairperson = $this->getUserByRole('CHAIRPERSON');

        $this->createUnremittedTransactions($treasurer);
        $remittance = $this->createRemittance($treasurer);

        // Try to accept without verify
        $response = $this->actingAs($chairperson)->patch("/org/remittances/{$remittance->id}/accept");

        $response->assertSessionHas('error');
    }

    // ==================== FR-0022: Semester-Scoped Financials ====================

    /** @test */
    public function test_transactions_tagged_with_academic_year()
    {
        $tx = $this->createUnremittedTransactions($this->getUserByRole('TREASURER'));
        $this->assertNotNull($tx->academic_year_id);
    }

    /** @test */
    public function test_remittances_tagged_with_academic_year()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $this->createUnremittedTransactions($treasurer);
        $remittance = $this->createRemittance($treasurer);

        $this->assertNotNull($remittance->academic_year_id);
    }

    // ==================== Helper Methods ====================

    private function getUserByRole(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    private function createUnremittedTransactions(User $user, int $count = 1): ?Transaction
    {
        $feeProfile = FeeProfile::where('category', 'REGULAR')->first();
        $academicYear = AcademicYear::where('is_active', true)->first();

        $lastTx = null;
        for ($i = 0; $i < $count; $i++) {
            $student = Student::find($i + 1) ?? Student::first();

            $lastTx = Transaction::create([
                'or_number' => 'TEST-' . time() . "-" . $i,
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

        return $lastTx;
    }

    private function createRemittance(User $user): Remittance
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        
        $total = Transaction::where('processed_by_user_id', $user->id)
            ->whereNull('remittance_id')
            ->sum('amount_paid');

        return Remittance::create([
            'control_number' => 'REM-' . time(),
            'organization_id' => $user->organization_id,
            'academic_year_id' => $activeYear->id,
            'total_amount' => $total,
            'created_by_user_id' => $user->id,
            'status' => 'PENDING',
        ]);
    }
}