<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

final class CrossPanelLoginTest extends TestCase
{
    public function test_tenant_session_can_open_and_submit_superadmin_login(): void
    {
        $user = new User;
        $user->id = 7;
        $this->actingAs($user, 'tenant');
        $this->withSession(['auth_tenant_id' => 'test']);

        $this->get('http://localhost/superadmin/login')->assertOk();
        $this->post('http://localhost/superadmin/login', [])
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertSame(7, Auth::guard('tenant')->id());
        $this->assertFalse(Auth::guard('admin')->check());
        $this->assertFalse(tenancy()->initialized);
    }

    public function test_superadmin_session_can_open_and_submit_shared_tenant_login(): void
    {
        $admin = new AdminUser;
        $admin->id = 9;
        $this->actingAs($admin, 'admin');

        $this->get('http://localhost/test/login')->assertRedirect('/admin/login');
        $this->get('http://localhost/admin/login')->assertOk()
            ->assertSee('name="tenant_id" value="test"', false);
        $this->post('http://localhost/admin/login', ['tenant_id' => 'test'])
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertSame(9, Auth::guard('admin')->id());
        $this->assertFalse(Auth::guard('tenant')->check());

        $this->post('http://localhost/admin/login', [
            'tenant_id' => 'test',
            'email' => 'demo@levan.com',
            'password' => '123456789',
        ])->assertRedirect('/test/dashboard');

        $this->assertSame(9, Auth::guard('admin')->id());
        $this->assertAuthenticated('tenant');
    }

    public function test_persisted_tenant_session_does_not_initialize_tenancy_on_superadmin_login(): void
    {
        $this->withSession([
            'auth_tenant_id' => 'test',
            Auth::guard('tenant')->getName() => 7,
        ])->get('http://localhost/superadmin/login')->assertOk();

        $this->assertFalse(tenancy()->initialized);
        $this->assertNull(tenant());
        $this->assertFalse(Auth::guard('tenant')->hasUser());
    }

    public function test_a_tenant_hint_without_authentication_does_not_block_superadmin_login(): void
    {
        $this->withSession(['tenant_login_context' => 'old-tenant', 'auth_tenant_id' => 'old-tenant']);
        $this->get('http://localhost/superadmin/login')->assertOk();
        $this->assertFalse(tenancy()->initialized);
    }

    public function test_guest_can_access_superadmin_login(): void
    {
        $this->get('http://localhost/superadmin/login')->assertOk();
    }
}
