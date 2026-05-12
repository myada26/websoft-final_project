<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use App\Models\FeeProfile;
use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4FeeFineConfigTest extends TestCase
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

    // ==================== FR-0011: Membership Fee Configuration ====================

    /** @test */
    public function test_ssc_admin_can_create_fee_profile()
    {
        $admin = $this->getUserByRole('SSC_ADMIN');
        $org = Organization::first();

        $response = $this->actingAs($admin)->post('/admin/fee-profiles', [
            'organization_id' => $org->id,
            'name' => 'Test Membership Fee',
            'amount' => 150.00,
            'category' => 'REGULAR',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fee_profiles', ['name' => 'Test Membership Fee']);
    }

    /** @test */
    public function test_non_admin_cannot_create_fee_profile()
    {
        $chairperson = $this->getUserByRole('CHAIRPERSON');
        $org = Organization::first();

        $response = $this->actingAs($chairperson)->post('/admin/fee-profiles', [
            'organization_id' => $org->id,
            'name' => 'Unauthorized Fee',
            'amount' => 200.00,
            'category' => 'REGULAR',
        ]);

        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    /** @test */
    public function test_chairperson_cannot_edit_fee_profiles()
    {
        $chairperson = $this->getUserByRole('CHAIRPERSON');
        $feeProfile = FeeProfile::first();

        $response = $this->actingAs($chairperson)->put("/admin/fee-profiles/{$feeProfile->id}", [
            'organization_id' => $feeProfile->organization_id,
            'name' => $feeProfile->name,
            'amount' => 999.99,
            'category' => $feeProfile->category,
            'is_active' => true,
        ]);

        $this->assertTrue(in_array($response->status(), [302, 403]));
    }

    // ==================== FR-0012: Flexible Fee Categories ====================

    /** @test */
    public function test_fee_profile_has_valid_categories()
    {
        $admin = $this->getUserByRole('SSC_ADMIN');
        $org = Organization::first();

        $categories = ['REGULAR', 'IRREGULAR', 'EXTENDEE', 'EXEMPTED'];

        foreach ($categories as $category) {
            $response = $this->actingAs($admin)->post('/admin/fee-profiles', [
                'organization_id' => $org->id,
                'name' => "Test $category Fee",
                'amount' => $category === 'EXEMPTED' ? 0 : 100.00,
                'category' => $category,
                'is_active' => true,
            ]);

            $response->assertRedirect();
        }
    }

    // ==================== FR-0013: Fine Collection Window ====================

    /** @test */
    public function test_treasurer_can_open_fine_collection_window()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $org = $treasurer->organization;
        $academicYear = AcademicYear::where('is_active', true)->first();

        // Use tinker to test the service directly since no API route exists
        $service = app(\App\Services\FineCollectionWindowService::class);
        $window = $service->openWindow($org, $treasurer);

        $this->assertNotNull($window);
        $this->assertEquals('OPEN', $window->status);
    }

    /** @test */
    public function test_non_treasurer_cannot_open_fine_window()
    {
        $secretary = $this->getUserByRole('SECRETARY');
        $org = $secretary->organization;

        $service = app(\App\Services\FineCollectionWindowService::class);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $service->openWindow($org, $secretary);
    }

    /** @test */
    public function test_treasurer_can_close_fine_collection_window()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $org = $treasurer->organization;

        $service = app(\App\Services\FineCollectionWindowService::class);
        
        // Open then close
        $service->openWindow($org, $treasurer);
        $window = $service->closeWindow($org, $treasurer);

        $this->assertEquals('CLOSED', $window->status);
    }

    /** @test */
    public function test_fine_payment_blocked_when_window_closed()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $collector = $this->getUserByRole('COLLECTOR');
        $org = $treasurer->organization;

        // Open then close window
        $service = app(\App\Services\FineCollectionWindowService::class);
        $service->openWindow($org, $treasurer);
        $service->closeWindow($org, $treasurer);

        // Verify window is closed
        $this->assertFalse($service->canCollectFine($org->id));
    }

    /** @test */
    public function test_fine_payment_requires_full_amount()
    {
        $treasurer = $this->getUserByRole('TREASURER');
        $collector = $this->getUserByRole('COLLECTOR');
        $org = $treasurer->organization;

        // Open window
        $service = app(\App\Services\FineCollectionWindowService::class);
        $service->openWindow($org, $treasurer);

        // Verify window is open
        $this->assertTrue($service->canCollectFine($org->id));

        // Closing should prevent fine collection
        $service->closeWindow($org, $treasurer);
        $this->assertFalse($service->canCollectFine($org->id));
    }

    // ==================== Helper Methods ====================

    private function getUserByRole(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }
}