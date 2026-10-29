<?php

declare(strict_types=1);

namespace Tests\Feature;

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

    public function test_admin_path_redirects_to_superadmin(): void
    {
        $this->get('/admin')->assertRedirect('/superadmin');
        $this->get('/admin/login')->assertRedirect('/superadmin/login');
    }

    public function test_tenant_route_does_not_capture_superadmin(): void
    {
        // Visiting /superadmin/login must NOT initialize tenancy or fail with tenant not found
        $response = $this->get('http://localhost/superadmin/login');
        $response->assertOk();
        $this->assertNull(tenant());
    }
}
