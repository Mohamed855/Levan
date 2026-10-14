<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use Tests\TestCase;

class TenantUserManagementValidationFeatureTest extends TestCase
{
    private Tenant $tenant;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::findOrFail('test');
        tenancy()->initialize($this->tenant);
        $this->owner = User::where('email', 'demo@levan.com')->firstOrFail();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_user_status_requires_a_valid_explicit_status(): void
    {
        $this->actingAs($this->owner, 'tenant')
            ->patchJson($this->tenantUrl("users/{$this->owner->id}/toggle-status"), [
                'status' => 'unknown',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_user_without_management_permission_cannot_change_a_user_status(): void
    {
        $limitedUser = User::create([
            'name' => 'مستخدم محدود',
            'employee_number' => 'LIMITED' . random_int(1000, 9999),
            'email' => 'limited-' . random_int(1000, 9999) . '@levan.test',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
            'license_seat_id' => $this->owner->license_seat_id,
        ]);

        $this->actingAs($limitedUser, 'tenant')
            ->patchJson($this->tenantUrl("users/{$this->owner->id}/toggle-status"), [
                'status' => 'inactive',
            ])
            ->assertForbidden();

        $limitedUser->forceDelete();
    }

    public function test_team_requires_two_members_and_one_leader(): void
    {
        $this->actingAs($this->owner, 'tenant')
            ->postJson($this->tenantUrl('teams'), [
                'name' => 'فريق غير مكتمل',
                'members' => [],
                'leaders' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['members', 'leaders']);
    }

    public function test_role_identifier_must_use_english_database_key_format(): void
    {
        $this->actingAs($this->owner, 'tenant')
            ->postJson($this->tenantUrl('roles'), [
                'name' => 'دور_عربي',
                'permissions' => ['dashboard.view'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_guard_mismatched_role_assignment_is_rejected(): void
    {
        $nonTenantRole = \Spatie\Permission\Models\Role::create([
            'name' => 'admin_guard_role_' . random_int(1000, 9999),
            'guard_name' => 'admin',
        ]);

        $this->actingAs($this->owner, 'tenant')
            ->postJson($this->tenantUrl("roles/users/{$this->owner->id}/update-roles"), [
                'role' => $nonTenantRole->id,
                'status' => 'active',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $nonTenantRole->delete();
    }

    public function test_wrong_guard_cannot_access_tenant_user_management(): void
    {
        $this->patchJson($this->tenantUrl("users/{$this->owner->id}/toggle-status"), [
            'status' => 'inactive',
        ])->assertUnauthorized();
    }

    private function tenantUrl(string $path): string
    {
        return 'http://test.localhost/' . ltrim($path, '/');
    }
}
