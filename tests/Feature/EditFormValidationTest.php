<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EditFormValidationTest extends TestCase
{
    public function test_tenant_user_edit_form_validation_succeeds_without_500_error(): void
    {
        $tenant = \App\Models\Admin\Tenants\Tenant::first();
        if (! $tenant) {
            $this->markTestSkipped('No tenant found.');
        }

        tenancy()->initialize($tenant);

        $user = User::first();
        if (! $user) {
            $this->markTestSkipped('No tenant user found.');
        }

        $response = $this->actingAs($user, 'tenant')
            ->postJson(tenant_route_name('wizard-drafts.validate-step'), [
                'target_path' => "/users/{$user->id}",
                'target_method' => 'PUT',
                '_method' => 'PUT',
                'fields' => ['name', 'email'],
                'name' => $user->name,
                'email' => $user->email,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_tenant_user_edit_form_validation_returns_422_on_invalid_data(): void
    {
        $tenant = \App\Models\Admin\Tenants\Tenant::first();
        if (! $tenant) {
            $this->markTestSkipped('No tenant found.');
        }

        tenancy()->initialize($tenant);

        $user = User::first();
        if (! $user) {
            $this->markTestSkipped('No tenant user found.');
        }

        $response = $this->actingAs($user, 'tenant')
            ->postJson(tenant_route_name('wizard-drafts.validate-step'), [
                'target_path' => "/users/{$user->id}",
                'target_method' => 'PUT',
                '_method' => 'PUT',
                'fields' => ['name'],
                'name' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_tenant_role_edit_form_validation_succeeds(): void
    {
        $tenant = \App\Models\Admin\Tenants\Tenant::first();
        if (! $tenant) {
            $this->markTestSkipped('No tenant found.');
        }

        tenancy()->initialize($tenant);

        $user = User::first();
        $role = Role::where('guard_name', 'tenant')->first();
        if (! $user || ! $role) {
            $this->markTestSkipped('No tenant user or role found.');
        }

        $response = $this->actingAs($user, 'tenant')
            ->postJson(tenant_route_name('wizard-drafts.validate-step'), [
                'target_path' => "/roles/{$role->id}",
                'target_method' => 'PUT',
                '_method' => 'PUT',
                'fields' => ['name'],
                'name' => $role->name,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_admin_user_edit_form_validation_succeeds(): void
    {
        $admin = AdminUser::first();
        if (! $admin) {
            $this->markTestSkipped('No admin user found.');
        }

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.wizard-drafts.validate-step'), [
                'target_path' => "/superadmin/users/{$admin->id}",
                'target_method' => 'PUT',
                '_method' => 'PUT',
                'fields' => ['name'],
                'name' => $admin->name,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }
}
