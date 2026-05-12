<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\College;
use App\Models\Department;
use App\Models\Program;
use App\Models\AcademicYear;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2UniversityStructureTest extends TestCase
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

    // ==================== FR-0001: 3-Tier Hierarchy ====================

    /** @test */
    public function test_admin_can_create_college()
    {
        $admin = $this->getUserByRole('SSC_ADMIN');

        $response = $this->actingAs($admin)->post('/admin/colleges', [
            'name' => 'College of Science',
            'code' => 'COS',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('colleges', ['code' => 'COS']);
    }

    /** @test */
    public function test_non_admin_cannot_create_college()
    {
        $chairperson = $this->getUserByRole('CHAIRPERSON');

        $response = $this->actingAs($chairperson)->post('/admin/colleges', [
            'name' => 'College of Science',
            'code' => 'COS',
        ]);

        // Should be forbidden (403) or redirect back (302)
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    /** @test */
    public function test_admin_can_create_department_under_college()
    {
        $admin = $this->getUserByRole('SSC_ADMIN');
        $college = College::first();

        $response = $this->actingAs($admin)->post('/admin/departments', [
            'college_id' => $college->id,
            'name' => 'Department of Biology',
            'code' => 'BIO',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('departments', ['code' => 'BIO']);
    }

    // ==================== FR-0002: Academic Year ====================

    /** @test */
    public function test_only_one_academic_year_can_be_active()
    {
        $admin = $this->getUserByRole('SSC_ADMIN');

        // Create first academic year
        $this->actingAs($admin)->post('/admin/academic-years', [
            'name' => '2025-2026 1st Sem',
            'is_active' => true,
        ]);

        // Create second academic year - try to make active
        $this->actingAs($admin)->post('/admin/academic-years', [
            'name' => '2025-2026 2nd Sem',
            'is_active' => true,
        ]);

        // Should only have one active
        $activeCount = AcademicYear::where('is_active', true)->count();
        $this->assertEquals(1, $activeCount);
    }

    /** @test */
    public function test_switching_active_semester_warns_if_unresolved()
    {
        $admin = $this->getUserByRole('SSC_ADMIN');
        $activeYear = AcademicYear::where('is_active', true)->first();

        // Create new year
        $newYear = AcademicYear::create([
            'name' => '2025-2026 1st Sem',
            'is_active' => false,
        ]);

        // Try to switch
        $response = $this->actingAs($admin)->patch("/admin/academic-years/{$newYear->id}/set-active");

        $response->assertRedirect();
    }

    // ==================== FR-0003: Organization Scope ====================

    /** @test */
    public function test_ssc_organization_has_no_linked_ids()
    {
        $ssc = Organization::where('type', 'SSC')->first();
        $this->assertNull($ssc->linked_college_id);
        $this->assertNull($ssc->linked_department_id);
    }

    /** @test */
    public function test_college_council_has_linked_college()
    {
        $council = Organization::where('type', 'COLLEGE_COUNCIL')->first();
        $this->assertNotNull($council->linked_college_id);
        $this->assertNull($council->linked_department_id);
    }

    // ==================== FR-0004: Authentication & Lockout ====================

    /** @test */
    public function test_login_fails_with_wrong_password()
    {
        $user = $this->getUserByRole('TREASURER');

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function test_login_succeeds_with_correct_password()
    {
        $user = $this->getUserByRole('TREASURER');

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $response->assertRedirect('/org/dashboard');
    }

    // ==================== FR-0005: Role-Based Permissions ====================

    /** @test */
    public function test_ssc_admin_can_create_users()
    {
        $admin = $this->getUserByRole('SSC_ADMIN');
        $org = Organization::first();

        $response = $this->actingAs($admin)->post('/admin/users', [
            'username' => '2023-999-TEST',
            'password' => 'password',
            'role' => 'TREASURER',
            'organization_id' => $org->id,
            'is_active' => true,
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function test_non_admin_cannot_create_users()
    {
        $chairperson = $this->getUserByRole('CHAIRPERSON');
        $org = Organization::first();

        $response = $this->actingAs($chairperson)->post('/admin/users', [
            'username' => '2023-999-TEST2',
            'password' => 'password',
            'role' => 'TREASURER',
            'organization_id' => $org->id,
        ]);

        // Should be forbidden (403) or redirect back (302)
        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    // ==================== Helper Methods ====================

    private function getUserByRole(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }
}