<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionTest extends TestCase
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

    /** @test */
    public function test_user_has_permission_returns_true_for_ssc_admin()
    {
        $user = $this->getUserByRole('SSC_ADMIN');

        $this->assertTrue($user->hasPermission('pos:create'));
        $this->assertTrue($user->hasPermission('users:manage'));
        $this->assertTrue($user->hasPermission('remit:create'));
        $this->assertTrue($user->hasPermission('void:approve'));
    }

    /** @test */
    public function test_user_has_permission_returns_true_for_treasurer()
    {
        $user = $this->getUserByRole('TREASURER');

        $this->assertTrue($user->hasPermission('pos:create'));
        $this->assertTrue($user->hasPermission('remit:create'));
        $this->assertTrue($user->hasPermission('students:view'));
    }

    /** @test */
    public function test_user_has_permission_returns_false_for_treasurer_admin_actions()
    {
        $user = $this->getUserByRole('TREASURER');

        $this->assertFalse($user->hasPermission('users:manage'));
        $this->assertFalse($user->hasPermission('void:approve'));
    }

    /** @test */
    public function test_user_has_permission_returns_true_for_collector()
    {
        $user = $this->getUserByRole('COLLECTOR');

        $this->assertTrue($user->hasPermission('pos:create'));
        $this->assertTrue($user->hasPermission('void:request'));
    }

    /** @test */
    public function test_user_has_permission_returns_false_for_collector_privileged_actions()
    {
        $user = $this->getUserByRole('COLLECTOR');

        $this->assertFalse($user->hasPermission('remit:create'));
        $this->assertFalse($user->hasPermission('void:approve'));
    }

    /** @test */
    public function test_user_has_permission_returns_true_for_auditor()
    {
        $user = $this->getUserByRole('AUDITOR');

        $this->assertTrue($user->hasPermission('remit:verify'));
        $this->assertTrue($user->hasPermission('void:approve'));
        $this->assertTrue($user->hasPermission('reports:view'));
    }

    /** @test */
    public function test_user_has_permission_returns_true_for_chairperson()
    {
        $user = $this->getUserByRole('CHAIRPERSON');

        $this->assertTrue($user->hasPermission('users:manage'));
        $this->assertTrue($user->hasPermission('void:approve'));
        $this->assertTrue($user->hasPermission('event:create'));
    }

    /** @test */
    public function test_user_has_permission_returns_true_for_secretary()
    {
        $user = $this->getUserByRole('SECRETARY');

        $this->assertTrue($user->hasPermission('attendance:record'));
        $this->assertTrue($user->hasPermission('attendance:view'));
    }

    /** @test */
    public function test_user_has_permission_returns_false_for_secretary_pos_actions()
    {
        $user = $this->getUserByRole('SECRETARY');

        $this->assertFalse($user->hasPermission('pos:create'));
        $this->assertFalse($user->hasPermission('remit:create'));
    }

    /** @test */
    public function test_has_role_returns_true_for_matching_role()
    {
        $user = $this->getUserByRole('TREASURER');

        $this->assertTrue($user->hasRole('TREASURER'));
        $this->assertTrue($user->hasRole(['TREASURER', 'COLLECTOR']));
    }

    /** @test */
    public function test_has_role_returns_false_for_non_matching_role()
    {
        $user = $this->getUserByRole('TREASURER');

        $this->assertFalse($user->hasRole('CHAIRPERSON'));
    }

    /** @test */
    public function test_role_permissions_table_has_correct_data()
    {
        $count = \DB::table('role_permissions')->count();
        $this->assertGreaterThan(0, $count);
    }

    /** @test */
    public function test_each_role_has_unique_permissions()
    {
        $roles = ['SSC_ADMIN', 'CHAIRPERSON', 'TREASURER', 'COLLECTOR', 'AUDITOR', 'SECRETARY'];

        foreach ($roles as $role) {
            $permissions = \DB::table('role_permissions')
                ->where('role', $role)
                ->pluck('permission_id')
                ->toArray();

            $this->assertEquals(count($permissions), count(array_unique($permissions)), "Role $role has duplicate permissions");
        }
    }

    private function getUserByRole(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }
}