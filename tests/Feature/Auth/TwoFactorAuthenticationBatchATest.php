<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Settings\Settings as AdminSettings;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Settings\Settings as TenantSettings;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use Tests\TestCase;

final class TwoFactorAuthenticationBatchATest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'tenancy.bootstrappers' => [],
            'activitylog.enabled' => false,
        ]);
        DB::purge('mysql');
        DB::purge('tenant');
        $this->withoutVite();

        // Admin users table
        Schema::connection('mysql')->create('admin_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('status')->default('active');
            $table->boolean('is_super_admin')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // Admin settings table
        Schema::connection('mysql')->create('settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('force_two_factor_auth')->default(false);
            $table->timestamps();
        });

        // Tenants table
        Schema::connection('mysql')->create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('db_database')->default(':memory:');
            $table->string('db_username')->default('');
            $table->string('db_password')->default('');
            $table->string('company_name')->default('Test Company');
            $table->string('commercial_number')->default('1234567890');
            $table->string('access_type')->default('path');
            $table->json('data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Tenant users table
        Schema::connection('tenant')->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('employee_number')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone', 20)->nullable();
            $table->string('status')->default('active');
            $table->string('image')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->string('global_uid')->nullable();
            $table->string('license_seat_id')->nullable();
            $table->string('current_session_id')->nullable();
            $table->string('background_image')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // Tenant settings table
        Schema::connection('tenant')->create('settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('force_two_factor_auth')->default(false);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        if (function_exists('tenancy') && tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    // --------------------------------------------------------------------------
    // 2FA-03, 2FA-05, 2FA-06: UI Structure & Contract
    // --------------------------------------------------------------------------

    public function test_admin_challenge_view_renders_single_otp_field_and_links_row(): void
    {
        $response = $this->withSession(['login.id' => 1, 'login.guard' => 'admin'])
            ->get('/superadmin/two-factor-challenge');

        $response->assertOk();
        $content = $response->getContent();

        // 2FA-03: Single code input, no old 6 inputs
        $this->assertStringContainsString('name="code"', $content);
        $this->assertStringContainsString('placeholder="XXXXXX"', $content);
        $this->assertStringContainsString('class="form-control otp-input text-center fw-bold"', $content);
        $this->assertStringNotContainsString('auth-otp-wrapper', $content);
        $this->assertStringNotContainsString('name="code" id="otpHidden"', $content);

        // 2FA-05 & 2FA-06: Bottom links row on same line with auth-back-link
        $this->assertStringContainsString('auth-challenge-links-row', $content);
        $this->assertStringContainsString('الرجوع لتسجيل الدخول', $content);
        $this->assertStringContainsString('toggleRecoveryLink', $content);
    }

    public function test_tenant_challenge_view_renders_single_otp_field_and_links_row(): void
    {
        $tenant = Tenant::create(['id' => 'test-tenant']);
        tenancy()->initialize($tenant);

        $response = $this->withSession([
            'login.id' => 1,
            'login.guard' => 'tenant',
            'login.tenant_id' => 'test-tenant',
        ])->get('/test-tenant/two-factor-challenge');

        $response->assertOk();
        $content = $response->getContent();

        // 2FA-03: Single code input, no old 6 inputs
        $this->assertStringContainsString('name="code"', $content);
        $this->assertStringContainsString('placeholder="XXXXXX"', $content);
        $this->assertStringContainsString('class="form-control otp-input text-center fw-bold"', $content);
        $this->assertStringNotContainsString('auth-otp-wrapper', $content);
        $this->assertStringNotContainsString('name="code" id="otpHidden"', $content);

        // 2FA-05 & 2FA-06: Bottom links row
        $this->assertStringContainsString('auth-challenge-links-row', $content);
        $this->assertStringContainsString('الرجوع لتسجيل الدخول بحساب آخر', $content);
        $this->assertStringContainsString('toggleRecoveryLink', $content);
    }

    // --------------------------------------------------------------------------
    // 2FA-01, 2FA-12: TOTP Authentication
    // --------------------------------------------------------------------------

    public function test_admin_can_authenticate_with_valid_totp(): void
    {
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $secret = $google2fa->generateSecretKey();

        $admin = AdminUser::create([
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $admin->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->withSession(['login.id' => $admin->id, 'login.guard' => 'admin'])
            ->post('/superadmin/two-factor-challenge', [
                'code' => $validCode,
            ]);

        $response->assertRedirect('/superadmin');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertNull(session('login.id'));
        $this->assertNull(session('login.guard'));
    }

    public function test_tenant_user_can_authenticate_with_valid_totp(): void
    {
        $tenant = Tenant::create(['id' => 'acme']);
        tenancy()->initialize($tenant);

        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $secret = $google2fa->generateSecretKey();

        $user = User::create([
            'name' => 'Acme User',
            'email' => 'user@acme.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->withSession([
            'login.id' => $user->id,
            'login.guard' => 'tenant',
            'login.tenant_id' => 'acme',
        ])->post('/acme/two-factor-challenge', [
            'code' => $validCode,
        ]);

        $response->assertRedirect('/acme/dashboard');
        $this->assertAuthenticatedAs($user, 'tenant');
        $this->assertNull(session('login.id'));
        $this->assertNull(session('login.guard'));
    }

    // --------------------------------------------------------------------------
    // 2FA-08, 2FA-09, 2FA-10: Recovery Codes
    // --------------------------------------------------------------------------

    public function test_admin_recovery_code_succeeds_once_and_cannot_be_reused(): void
    {
        $admin = AdminUser::create([
            'name' => 'Admin Recovery',
            'email' => 'admin_rec@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $admin->forceFill([
            'two_factor_secret' => encrypt('secret-key'),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-one', 'code-two'])),
        ])->save();

        // First attempt with valid recovery code
        $response = $this->from('/superadmin/two-factor-challenge')
            ->withSession(['login.id' => $admin->id, 'login.guard' => 'admin'])
            ->post('/superadmin/two-factor-challenge', [
                'recovery_code' => 'code-one',
            ]);

        $response->assertRedirect('/superadmin');
        $this->assertAuthenticatedAs($admin, 'admin');

        // Check code was consumed
        $admin->refresh();
        $remaining = json_decode(decrypt($admin->two_factor_recovery_codes), true);
        $this->assertNotContains('code-one', $remaining);
        $this->assertContains('code-two', $remaining);

        // Logout and attempt to reuse same consumed code
        Auth::guard('admin')->logout();

        $secondResponse = $this->from('/superadmin/two-factor-challenge')
            ->withSession(['login.id' => $admin->id, 'login.guard' => 'admin'])
            ->post('/superadmin/two-factor-challenge', [
                'recovery_code' => 'code-one',
            ]);

        $secondResponse->assertSessionHasErrors('recovery_code');
        $this->assertFalse(Auth::guard('admin')->check());
    }

    public function test_tenant_recovery_code_succeeds_and_consumes(): void
    {
        $tenant = Tenant::create(['id' => 'rec-tenant']);
        tenancy()->initialize($tenant);

        $user = User::create([
            'name' => 'Tenant Recovery',
            'email' => 'tenant_rec@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $user->forceFill([
            'two_factor_secret' => encrypt('secret-key'),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(['tenant-code-1', 'tenant-code-2'])),
        ])->save();

        $response = $this->from('/rec-tenant/two-factor-challenge')
            ->withSession([
                'login.id' => $user->id,
                'login.guard' => 'tenant',
                'login.tenant_id' => 'rec-tenant',
            ])->post('/rec-tenant/two-factor-challenge', [
                'recovery_code' => 'tenant-code-1',
            ]);

        $response->assertRedirect('/rec-tenant/dashboard');
        $this->assertAuthenticatedAs($user, 'tenant');

        $user->refresh();
        $remaining = json_decode(decrypt($user->two_factor_recovery_codes), true);
        $this->assertNotContains('tenant-code-1', $remaining);
        $this->assertContains('tenant-code-2', $remaining);
    }

    // --------------------------------------------------------------------------
    // 2FA-11: Input Validation & Edge Cases
    // --------------------------------------------------------------------------

    public function test_admin_validation_rejects_malformed_inputs_without_500(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $admin = AdminUser::create([
            'name' => 'Validation Admin',
            'email' => 'val_admin@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $session = ['login.id' => $admin->id, 'login.guard' => 'admin'];

        // Empty payload
        $this->from('/superadmin/two-factor-challenge')
            ->withSession($session)
            ->post('/superadmin/two-factor-challenge', [])
            ->assertSessionHasErrors(['code', 'recovery_code']);

        // 5 digits
        $this->from('/superadmin/two-factor-challenge')
            ->withSession($session)
            ->post('/superadmin/two-factor-challenge', ['code' => '12345'])
            ->assertSessionHasErrors('code');

        // 7 digits
        $this->from('/superadmin/two-factor-challenge')
            ->withSession($session)
            ->post('/superadmin/two-factor-challenge', ['code' => '1234567'])
            ->assertSessionHasErrors('code');

        // Non-digits
        $this->from('/superadmin/two-factor-challenge')
            ->withSession($session)
            ->post('/superadmin/two-factor-challenge', ['code' => 'abcdef'])
            ->assertSessionHasErrors('code');

        // Array payload
        $this->from('/superadmin/two-factor-challenge')
            ->withSession($session)
            ->post('/superadmin/two-factor-challenge', ['code' => ['123456']])
            ->assertSessionHasErrors('code');

        // Both code and recovery_code provided with values
        $this->from('/superadmin/two-factor-challenge')
            ->withSession($session)
            ->post('/superadmin/two-factor-challenge', [
                'code' => '123456',
                'recovery_code' => 'my-recovery-code',
            ])
            ->assertSessionHasErrors(['code', 'recovery_code']);
    }

    public function test_tenant_validation_rejects_malformed_inputs_without_500(): void
    {
        $tenant = Tenant::create(['id' => 'val-tenant']);
        tenancy()->initialize($tenant);

        $user = User::create([
            'name' => 'Val User',
            'email' => 'val@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $session = [
            'login.id' => $user->id,
            'login.guard' => 'tenant',
            'login.tenant_id' => 'val-tenant',
        ];

        // 5 digits
        $this->from('/val-tenant/two-factor-challenge')
            ->withSession($session)
            ->post('/val-tenant/two-factor-challenge', ['code' => '12345'])
            ->assertSessionHasErrors('code');

        // Array
        $this->from('/val-tenant/two-factor-challenge')
            ->withSession($session)
            ->post('/val-tenant/two-factor-challenge', ['code' => ['123456']])
            ->assertSessionHasErrors('code');

        // Both provided
        $this->from('/val-tenant/two-factor-challenge')
            ->withSession($session)
            ->post('/val-tenant/two-factor-challenge', [
                'code' => '123456',
                'recovery_code' => 'some-recovery-code',
            ])
            ->assertSessionHasErrors(['code', 'recovery_code']);
    }

    // --------------------------------------------------------------------------
    // 2FA-19, 2FA-20: Flow & Session Cancellation
    // --------------------------------------------------------------------------

    public function test_cancel_endpoint_clears_pending_session_and_redirects_to_login(): void
    {
        $response = $this->withSession(['login.id' => 10, 'login.guard' => 'admin', 'login.remember' => true])
            ->get('/superadmin/two-factor-challenge/cancel');

        $response->assertRedirect('/superadmin/login');
        $this->assertNull(session('login.id'));
        $this->assertNull(session('login.guard'));
        $this->assertNull(session('login.remember'));
    }

    public function test_visiting_challenge_without_login_id_redirects_to_login(): void
    {
        $this->get('/superadmin/two-factor-challenge')
            ->assertRedirect('/superadmin/login');

        $tenant = Tenant::create(['id' => 'guest-tenant']);
        tenancy()->initialize($tenant);

        $this->get('/guest-tenant/two-factor-challenge')
            ->assertRedirect(tenant_route_name('login'));
    }

    // --------------------------------------------------------------------------
    // 2FA-21, 2FA-22: Guard & Tenant Isolation
    // --------------------------------------------------------------------------

    public function test_admin_and_tenant_session_isolation(): void
    {
        $tenant = Tenant::create(['id' => 'iso-tenant']);
        tenancy()->initialize($tenant);

        // Admin pending session attempting to post to tenant endpoint -> 419 / rejected
        $response = $this->withSession(['login.id' => 1, 'login.guard' => 'admin'])
            ->post('/iso-tenant/two-factor-challenge', ['code' => '123456']);

        $this->assertTrue(in_array($response->getStatusCode(), [403, 419, 302], true));
        $this->assertFalse(Auth::guard('tenant')->check());

        // Tenant pending session attempting to post to admin endpoint -> 419 / rejected
        $responseAdmin = $this->withSession([
            'login.id' => 1,
            'login.guard' => 'tenant',
            'login.tenant_id' => 'iso-tenant',
        ])->post('/superadmin/two-factor-challenge', ['code' => '123456']);

        $this->assertTrue(in_array($responseAdmin->getStatusCode(), [403, 419, 302], true));
        $this->assertFalse(Auth::guard('admin')->check());
    }

    public function test_cross_tenant_isolation_rejected(): void
    {
        $tenantA = Tenant::create(['id' => 'tenant-a']);
        $tenantB = Tenant::create(['id' => 'tenant-b']);

        tenancy()->initialize($tenantB);

        // Session created for tenant-a cannot be completed on tenant-b
        $response = $this->withSession([
            'login.id' => 1,
            'login.guard' => 'tenant',
            'login.tenant_id' => 'tenant-a',
        ])->post('/tenant-b/two-factor-challenge', ['code' => '123456']);

        $this->assertTrue(in_array($response->getStatusCode(), [403, 419, 302], true));
        $this->assertFalse(Auth::guard('tenant')->check());
    }

    // --------------------------------------------------------------------------
    // 2FA-23: Deactivated User
    // --------------------------------------------------------------------------

    public function test_deactivated_admin_rejected_during_2fa(): void
    {
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $secret = $google2fa->generateSecretKey();

        $admin = AdminUser::create([
            'name' => 'Inactive Admin',
            'email' => 'inactive@example.test',
            'password' => bcrypt('password123'),
            'status' => 'inactive',
        ]);
        $admin->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->withSession(['login.id' => $admin->id, 'login.guard' => 'admin'])
            ->post('/superadmin/two-factor-challenge', [
                'code' => $validCode,
            ]);

        $this->assertFalse(Auth::guard('admin')->check());
    }

    // --------------------------------------------------------------------------
    // 2FA-15, 2FA-16, 2FA-18: Settings Management & Enforcement
    // --------------------------------------------------------------------------

    public function test_recovery_codes_endpoint_generates_eight_codes(): void
    {
        $admin = AdminUser::create([
            'name' => 'Settings Admin',
            'email' => 'settings@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->postJson('/superadmin/two-factor-recovery-codes')
            ->assertOk()
            ->assertJsonCount(8);
    }

    public function test_enforced_policy_rejects_disable(): void
    {
        AdminSettings::create(['force_two_factor_auth' => true]);

        $admin = AdminUser::create([
            'name' => 'Enforced Admin',
            'email' => 'enforced@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->deleteJson('/superadmin/two-factor-authentication')
            ->assertStatus(422)
            ->assertJsonPath('message', 'المصادقة الثنائية مفروضة بموجب سياسة النظام ولا يمكن تعطيلها.');
    }

    public function test_throttle_is_enforced_on_challenge_store(): void
    {
        $admin = AdminUser::create([
            'name' => 'Throttle Admin',
            'email' => 'throttle@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $session = ['login.id' => $admin->id, 'login.guard' => 'admin'];

        for ($i = 0; $i < 5; $i++) {
            $this->withSession($session)
                ->post('/superadmin/two-factor-challenge', ['code' => '000000']);
        }

        $this->withSession($session)
            ->post('/superadmin/two-factor-challenge', ['code' => '000000'])
            ->assertStatus(429);
    }

    public function test_form_validation_endpoint_accepts_two_factor_challenge(): void
    {
        $admin = AdminUser::create([
            'name' => 'FormVal Admin',
            'email' => 'formval@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        // Admin form-validation
        $this->withSession(['login.id' => $admin->id, 'login.guard' => 'admin'])
            ->postJson('/superadmin/form-validation', [
                'target_path' => '/superadmin/two-factor-challenge',
                'target_method' => 'POST',
                'fields' => ['code', 'recovery_code'],
                'code' => '123456',
                'recovery_code' => '',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        // Tenant form-validation
        $tenant = Tenant::create(['id' => 'fv-tenant']);
        tenancy()->initialize($tenant);

        $user = User::create([
            'name' => 'FV User',
            'email' => 'fv@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $this->withSession([
            'login.id' => $user->id,
            'login.guard' => 'tenant',
            'login.tenant_id' => 'fv-tenant',
        ])->postJson('/fv-tenant/form-validation', [
            'target_path' => '/fv-tenant/two-factor-challenge',
            'target_method' => 'POST',
            'fields' => ['code', 'recovery_code'],
            'code' => '654321',
            'recovery_code' => '',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);
    }
}
