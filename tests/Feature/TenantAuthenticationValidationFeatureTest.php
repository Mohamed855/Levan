<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
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

    public function test_shared_login_uses_tenant_scoped_validation_endpoint(): void
    {
        $this->get('/admin/login')->assertOk()
            ->assertSee('data-validation-url="'.url('/test/form-validation').'"', false);

        $this->postJson('/test/form-validation', [
            'target_path' => '/admin/login',
            'target_method' => 'POST',
            'fields' => ['email', 'password'],
            'email' => '',
            'password' => '',
        ])->assertUnprocessable()->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    public function test_two_factor_login_returns_to_tenant_path_and_binds_the_session(): void
    {
        $user = User::where('email', 'demo@levan.com')->firstOrFail();
        $connection = $user->getConnection();
        $connection->beginTransaction();

        try {
            $user->forceFill([
                'two_factor_secret' => encrypt('test-secret'),
                'two_factor_confirmed_at' => now(),
                'two_factor_recovery_codes' => encrypt(json_encode(['test-recovery-code'])),
            ])->save();

            $this->post('/admin/login', [
                'tenant_id' => 'test',
                'email' => 'demo@levan.com',
                'password' => '123456789',
            ])->assertRedirect(url('/test/two-factor-challenge'));

            $this->post('/test/two-factor-challenge', [
                'recovery_code' => 'test-recovery-code',
            ])->assertRedirect('/test/dashboard')->assertSessionHas('auth_tenant_id', 'test');

            $this->assertAuthenticatedAs($user, 'tenant');
        } finally {
            $connection->rollBack();
        }
    }
}
