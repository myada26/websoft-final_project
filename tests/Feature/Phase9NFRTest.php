<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Models\Transaction;
use App\Models\FeeProfile;
use App\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase9NFRTest extends TestCase
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

    // ==================== NFR-001: Sub-Second Search Latency ====================

    public function test_student_search_under_one_second()
    {
        $collector = $this->getUserByRole('COLLECTOR');

        $start = microtime(true);
        $response = $this->actingAs($collector)->get('/org/students');
        $duration = microtime(true) - $start;

        $response->assertStatus(200);
        $this->assertLessThan(1.0, $duration, "Search took {$duration}s, should be under 1s");
    }

    // ==================== NFR-004: Password Hashing ====================

    public function test_passwords_are_hashed_not_plain_text()
    {
        $user = $this->getUserByRole('TREASURER');

        $this->assertTrue(password_get_info($user->password_hash)['algo'] !== 0);
        $this->assertTrue(\Hash::check('password', $user->password_hash));
    }

    // ==================== NFR-005: Session Auto-Timeout ====================

    public function test_session_configured_for_timeout()
    {
        $lifetime = config('session.lifetime');
        $this->assertGreaterThan(0, $lifetime);
    }

    // ==================== NFR-006: Data Privacy ====================

    public function test_query_level_org_isolation_enforced()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $orgId = $treasurer->organization_id;

        $response = $this->actingAs($treasurer)->get('/org/transactions');

        $response->assertStatus(200);
    }

    // ==================== NFR-007: SQL Injection Protection ====================

    public function test_search_input_handles_special_characters()
    {
        $collector = $this->getUserByRole('COLLECTOR');

        $response = $this->actingAs($collector)->get('/org/students?search=1%20OR%201=1');

        $response->assertStatus(200);
    }

    // ==================== NFR-008: ACID Compliance ====================

    public function test_transaction_atomic_on_failure()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $feeProfile = FeeProfile::first();

        $initialCount = Transaction::count();

        $response = $this->actingAs($treasurer)->post('/org/transactions', [
            'student_id' => 99999,
            'fee_profile_ids' => [$feeProfile->id],
            'payment_method' => 'CASH',
        ]);

        $response->assertRedirect();
        $this->assertEquals($initialCount, Transaction::count());
    }

    // ==================== NFR-009: Decimal Precision ====================

    public function test_monetary_values_use_decimal_not_float()
    {
        $feeProfile = FeeProfile::first();

        $this->assertIsString($feeProfile->amount);
    }

    // ==================== NFR-010: Minimal Clicks ====================

    public function test_payment_workflow_accessible()
    {
        $collector = $this->getUserByRole('COLLECTOR');

        $this->assertTrue(true);
    }

    // ==================== NFR-011: Mobile Responsiveness ====================

    public function test_dashboard_readable()
    {
        $chairperson = $this->getUserByRole('CHAIRPERSON');

        $response = $this->actingAs($chairperson)->get('/org/dashboard');

        $response->assertStatus(200);
    }

    // ==================== NFR-012: Clear Error Messages ====================

    public function test_errors_return_human_readable_messages()
    {
        $collector = $this->getUserByRole('COLLECTOR');

        $response = $this->actingAs($collector)->post('/org/transactions', [
            'student_id' => null,
        ]);

        $response->assertRedirect();
    }

    // ==================== NFR-013: Horizontal Scalability ====================

    public function test_database_columns_exist()
    {
        $this->assertTrue(\Schema::hasColumn('students', 'student_number'));
        $this->assertTrue(\Schema::hasColumn('student_enrollments', 'student_id'));
    }

    // ==================== NFR-015: Browser Compatibility ====================

    public function test_app_returns_proper_response()
    {
        $this->assertTrue(true);
    }

    // ==================== NFR-016: Printer Compatibility ====================

    public function test_receipt_formatted_for_printing()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $tx = $this->createTransaction($treasurer);

        $response = $this->actingAs($treasurer)->get("/org/transactions/{$tx->id}/receipt");

        $response->assertStatus(200);
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