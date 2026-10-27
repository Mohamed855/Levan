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
            'email' => 'limited-' . random_int(1000, 9999) . '@levan.test',
            'password' => bcrypt('Password123!'),
            'status' => 'active',
        ]);

        $this->actingAs($limitedUser, 'tenant')
            ->patchJson($this->tenantUrl("users/{$this->owner->id}/toggle-status"), [
                'status' => 'inactive',
            ])
            ->assertForbidden();

        $limitedUser->forceDelete();
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

    public function test_cannot_create_user_when_package_user_limit_is_reached(): void
    {
        $originalPlanUsers = null;
        $originalSubUsers = null;

        \Stancl\Tenancy\Facades\Tenancy::central(function () use (&$originalPlanUsers, &$originalSubUsers) {
            $subscription = \App\Models\Admin\Subscription\Subscription::where('tenant_id', 'test')->latest('id')->first();
            $plan = $subscription->plan;
            $originalPlanUsers = $plan->included_users;
            $originalSubUsers = $subscription->total_users;

            $plan->update(['limits_mode' => 'limited', 'included_users' => 1]);
            $subscription->update(['total_users' => 1]);
        });

        try {
            // Visiting create page should redirect with error
            $this->actingAs($this->owner, 'tenant')
                ->get($this->tenantUrl('users/create'))
                ->assertRedirect($this->tenantUrl('users'))
                ->assertSessionHas('error');

            // Posting to create should fail validation with user_limit error
            $this->actingAs($this->owner, 'tenant')
                ->post($this->tenantUrl('users'), [
                    'name' => 'مستخدم جديد',
                    'email' => 'newuser999@levan.test',
                    'phone' => '0512345678',
                    'password' => 'Password123!',
                    'password_confirmation' => 'Password123!',
                    'role_id' => 1,
                ])
                ->assertRedirect($this->tenantUrl('users/create'))
                ->assertSessionHasErrors('user_limit');
        } finally {
            \Stancl\Tenancy\Facades\Tenancy::central(function () use ($originalPlanUsers, $originalSubUsers) {
                $subscription = \App\Models\Admin\Subscription\Subscription::where('tenant_id', 'test')->latest('id')->first();
                $subscription->plan->update(['included_users' => $originalPlanUsers]);
                $subscription->update(['total_users' => $originalSubUsers]);
            });
        }
    }

    public function test_can_access_create_user_page_when_below_package_user_limit(): void
    {
        \Stancl\Tenancy\Facades\Tenancy::central(function () {
            $subscription = \App\Models\Admin\Subscription\Subscription::where('tenant_id', 'test')->latest('id')->first();
            $subscription->plan->update(['limits_mode' => 'limited', 'included_users' => 50]);
            $subscription->update(['total_users' => 50]);
        });

        $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('users/create'))
            ->assertOk();
    }

    private function tenantUrl(string $path): string
    {
        return 'http://test.localhost/' . ltrim($path, '/');
    }
}
