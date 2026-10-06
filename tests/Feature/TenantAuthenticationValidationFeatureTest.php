<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\Tenants\Tenant;
use Tests\TestCase;

class TenantAuthenticationValidationFeatureTest extends TestCase
{
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::findOrFail('test');
        tenancy()->initialize($this->tenant);
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_public_auth_validation_uses_the_login_request_rules(): void
    {
        $response = $this->postJson('http://test.localhost/form-validation', [
            'target_path' => '/login',
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

    public function test_public_auth_validation_rejects_non_authentication_targets(): void
    {
        $this->postJson('http://test.localhost/form-validation', [
            'target_path' => '/users',
            'target_method' => 'POST',
            'fields' => ['name'],
            'name' => 'أي مستخدم',
        ])->assertNotFound();
    }
}
