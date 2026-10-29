<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminAuthenticationValidationFeatureTest extends TestCase
{
    public function test_admin_login_validation_uses_its_form_request_rules(): void
    {
        $response = $this->postJson(route('admin.form-validation'), [
            'target_path' => '/superadmin/login',
            'target_method' => 'POST',
            'fields' => ['email', 'password'],
            'email' => '',
            'password' => '',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    public function test_admin_login_validation_rejects_non_authentication_targets(): void
    {
        $this->postJson(route('admin.form-validation'), [
            'target_path' => '/superadmin/users',
            'target_method' => 'POST',
            'fields' => ['name'],
            'name' => 'مستخدم غير مصرح',
        ])->assertNotFound();
    }

    public function test_superadmin_login_page_renders_successfully(): void
    {
        $this->get('/superadmin/login')->assertOk();
    }

    public function test_admin_path_without_tenant_context_returns_404(): void
    {
        $this->get('/admin')->assertNotFound();
        $this->get('/admin/login')->assertNotFound();
    }

    public function test_legacy_tenant_login_redirects_to_admin_login_and_renders_hidden_tenant_input(): void
    {
        $response = $this->get('/test/login');
        $response->assertRedirect('/admin/login');

        $followed = $this->followRedirects($response);
        $followed->assertOk();
        $followed->assertSee('name="tenant_id" value="test"', false);
    }

    public function test_unknown_tenant_id_in_legacy_route_returns_404(): void
    {
        $this->get('/non_existent_tenant_xyz_404/login')->assertNotFound();
    }

    public function test_post_admin_login_without_or_invalid_tenant_id_returns_404(): void
    {
        $this->post('/admin/login', [
            'email' => 'demo@levan.com',
            'password' => 'password',
        ])->assertNotFound();

        $this->post('/admin/login', [
            'tenant_id' => 'non_existent_tenant_99',
            'email' => 'demo@levan.com',
            'password' => 'password',
        ])->assertNotFound();
    }

    public function test_successful_login_via_admin_login_with_hidden_tenant_id_redirects_to_clean_dashboard(): void
    {
        $response = $this->post('/admin/login', [
            'tenant_id' => 'test',
            'email' => 'demo@levan.com',
            'password' => '123456789',
        ]);

        $response->assertRedirect('/admin/dashboard');

        // Follow redirect to /admin/dashboard in the same session
        $dashboardResponse = $this->followRedirects($response);
        $dashboardResponse->assertOk();

        // Ensure subsequent internal tenant requests maintain the authenticated session
        $propertiesResponse = $this->get('/admin/properties');
        $propertiesResponse->assertOk();

        $this->assertTrue(Auth::guard('tenant')->check());
        $this->assertSame('demo@levan.com', Auth::guard('tenant')->user()->email);

        Auth::guard('tenant')->logout();
    }

    public function test_guest_visiting_legacy_dashboard_redirects_to_admin_login(): void
    {
        $response = $this->get('/test/dashboard');
        $response->assertRedirect('/admin/login');
        $this->assertSame(url('/admin/dashboard'), session('url.intended'));
    }

    public function test_authenticated_tenant_user_visiting_legacy_dashboard_redirects_to_clean_dashboard(): void
    {
        $tenant = Tenant::findOrFail('test');
        tenancy()->initialize($tenant);
        $user = User::where('email', 'demo@levan.com')->firstOrFail();

        $response = $this->actingAs($user, 'tenant')->get('/test/dashboard');
        $response->assertRedirect('/admin/dashboard');

        tenancy()->end();
    }

    public function test_tenant_logout_redirects_to_admin_login_preserving_tenant_context(): void
    {
        $tenant = Tenant::findOrFail('test');
        tenancy()->initialize($tenant);
        $user = User::where('email', 'demo@levan.com')->firstOrFail();

        $response = $this->actingAs($user, 'tenant')->post('/admin/logout');
        $response->assertRedirect('/admin/login');

        // Accessing /admin/login now should render with the preserved tenant context
        $loginPage = $this->get('/admin/login');
        $loginPage->assertOk();
        $loginPage->assertSee('name="tenant_id" value="test"', false);

        tenancy()->end();
    }

    public function test_tenant_route_does_not_capture_superadmin(): void
    {
        // Visiting /superadmin/login must NOT initialize tenancy or fail with tenant not found
        $response = $this->get('http://localhost/superadmin/login');
        $response->assertOk();
        $this->assertNull(tenant());
    }
}
