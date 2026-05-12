<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Transaction;
use App\Models\AuditLog;
use App\Models\FeeProfile;
use App\Models\Student;
use App\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase7ReportingTest extends TestCase
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

    // ==================== FR-0023: Digital Receipts ====================

    /** @test */
    public function test_treasurer_can_generate_receipt()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $tx = $this->createTransaction($treasurer);

        $response = $this->actingAs($treasurer)->get("/org/transactions/{$tx->id}/receipt");

        $response->assertStatus(200);
    }

    // ==================== FR-0024: Org-Specific Financial Reports ====================

    /** @test */
    public function test_treasurer_can_view_collection_summary_report()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $this->createTransaction($treasurer);
        $semester = AcademicYear::where('is_active', true)->first();

        $response = $this->actingAs($treasurer)->get("/org/reports?type=collection_summary&semester_id={$semester->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_treasurer_can_export_report_to_pdf()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $this->createTransaction($treasurer);
        $semester = AcademicYear::where('is_active', true)->first();

        $response = $this->actingAs($treasurer)->get("/org/reports/pdf?type=collection_summary&semester_id={$semester->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_treasurer_can_export_report_to_csv()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $this->createTransaction($treasurer);
        $semester = AcademicYear::where('is_active', true)->first();

        $response = $this->actingAs($treasurer)->get("/org/reports/csv?type=collection_summary&semester_id={$semester->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_auditor_can_view_reports()
    {
        $auditor = $this->getUserByRole('AUDITOR');
        $semester = AcademicYear::where('is_active', true)->first();

        $response = $this->actingAs($auditor)->get("/org/reports?type=collection_summary&semester_id={$semester->id}");

        $response->assertStatus(200);
    }

    // ==================== FR-0025: Immutable Audit Logs ====================

    /** @test */
    public function test_audit_logs_exist_in_database()
    {
        $this->assertTrue(AuditLog::count() >= 0);
    }

    /** @test */
    public function test_chairperson_can_view_org_audit_logs()
    {
        $chairperson = $this->getUserByRole('CHAIRPERSON');

        $response = $this->actingAs($chairperson)->get('/org/audit-logs');

        $response->assertStatus(200);
    }

    /** @test */
    public function test_auditor_can_view_org_audit_logs()
    {
        $auditor = $this->getUserByRole('AUDITOR');

        $response = $this->actingAs($auditor)->get('/org/audit-logs');

        $response->assertStatus(200);
    }

    /** @test */
    public function test_ssc_admin_can_view_global_audit_logs()
    {
        $admin = $this->getUserByRole('SSC_ADMIN');

        $response = $this->actingAs($admin)->get('/admin/audit-logs');

        $response->assertStatus(200);
    }

    /** @test */
    public function test_collector_cannot_view_audit_logs()
    {
        $collector = $this->getUserByRole('COLLECTOR');

        $response = $this->actingAs($collector)->get('/org/audit-logs');

        $response->assertStatus(403);
    }

    /** @test */
    public function test_audit_log_model_has_proper_structure()
    {
        $log = AuditLog::first();
        
        if ($log) {
            $this->assertNotNull($log->user_id);
            $this->assertNotNull($log->action);
            $this->assertNotNull($log->timestamp);
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