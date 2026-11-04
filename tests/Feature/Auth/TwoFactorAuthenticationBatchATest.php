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

        // Company settings table (Central)
        Schema::connection('mysql')->create('company_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->nullable();
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
    // Fixture Helpers
    // --------------------------------------------------------------------------

    private function createAdminFixture(array $attributes = []): AdminUser
    {
        $admin = AdminUser::create(array_merge([
            'name' => 'Admin User',
            'email' => 'admin-' . uniqid() . '@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ], $attributes));

        $admin->forceFill([
            'two_factor_secret' => $attributes['two_factor_secret'] ?? encrypt('test-secret'),
            'two_factor_confirmed_at' => $attributes['two_factor_confirmed_at'] ?? now(),
            'two_factor_recovery_codes' => $attributes['two_factor_recovery_codes'] ?? encrypt(json_encode(['code1', 'code2'])),
        ])->save();

        return $admin;
    }

    private function createTenantUserFixture(string $tenantId = 'test-tenant', array $attributes = []): User
    {
        if (! tenancy()->initialized || tenant('id') !== $tenantId) {
            $tenant = Tenant::firstOrCreate(['id' => $tenantId]);
            tenancy()->initialize($tenant);
        }

        $user = User::create(array_merge([
            'name' => 'Tenant User',
            'email' => 'tenant-' . uniqid() . '@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ], $attributes));

        $user->forceFill([
            'two_factor_secret' => $attributes['two_factor_secret'] ?? encrypt('test-secret'),
            'two_factor_confirmed_at' => $attributes['two_factor_confirmed_at'] ?? now(),
            'two_factor_recovery_codes' => $attributes['two_factor_recovery_codes'] ?? encrypt(json_encode(['code1', 'code2'])),
        ])->save();

        return $user;
    }

    // --------------------------------------------------------------------------
    // 2FA-03, 2FA-05, 2FA-06: UI Structure & Contract
    // --------------------------------------------------------------------------

    public function test_admin_challenge_view_renders_single_otp_field_and_links_row(): void
    {
        $admin = $this->createAdminFixture();
        $response = $this->withSession(['login.id' => $admin->id, 'login.guard' => 'admin'])
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
        $user = $this->createTenantUserFixture('test-tenant');

        $response = $this->withSession([
            'login.id' => $user->id,
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
        // Admin Cancel
        $this->withSession([
            'login.id' => 10,
            'login.guard' => 'admin',
            'login.tenant_id' => 'leftover-tenant',
            'login.remember' => true,
        ])->get('/superadmin/two-factor-challenge/cancel')
            ->assertRedirect('/superadmin/login');

        $this->assertNull(session('login.id'));
        $this->assertNull(session('login.guard'));
        $this->assertNull(session('login.tenant_id'));
        $this->assertNull(session('login.remember'));

        // Tenant Cancel
        $tenant = Tenant::create(['id' => 'cancel-tenant']);
        tenancy()->initialize($tenant);

        $this->withSession([
            'login.id' => 20,
            'login.guard' => 'tenant',
            'login.tenant_id' => 'cancel-tenant',
            'login.remember' => true,
        ])->get('/cancel-tenant/two-factor-challenge/cancel')
            ->assertRedirect(tenant_route_name('login'));

        $this->assertNull(session('login.id'));
        $this->assertNull(session('login.guard'));
        $this->assertNull(session('login.tenant_id'));
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
    // 2FA-21, 2FA-22: Guard & Tenant Isolation with Identical Numeric IDs
    // --------------------------------------------------------------------------

    public function test_admin_and_tenant_session_isolation(): void
    {
        $tenant = Tenant::create(['id' => 'iso-tenant']);
        tenancy()->initialize($tenant);

        // Admin pending session attempting to post to tenant endpoint -> rejected fail-closed
        $response = $this->withSession(['login.id' => 1, 'login.guard' => 'admin'])
            ->post('/iso-tenant/two-factor-challenge', ['code' => '123456']);

        $this->assertTrue(in_array($response->getStatusCode(), [403, 419, 302], true));
        $this->assertFalse(Auth::guard('tenant')->check());

        // Tenant pending session attempting to post to admin endpoint -> rejected fail-closed
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

        // Create user in Tenant A with ID 1
        tenancy()->initialize($tenantA);
        $userA = User::create([
            'id' => 1,
            'name' => 'User in Tenant A',
            'email' => 'user-a@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $userA->forceFill([
            'two_factor_secret' => encrypt('secret-a'),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-a'])),
        ])->save();

        // Create user in Tenant B with ID 1
        tenancy()->initialize($tenantB);
        $userB = User::create([
            'id' => 1,
            'name' => 'User in Tenant B',
            'email' => 'user-b@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $userB->forceFill([
            'two_factor_secret' => encrypt('secret-b'),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-b'])),
        ])->save();

        // Pending session initialized under Tenant A cannot complete challenge on Tenant B
        $response = $this->withSession([
            'login.id' => 1,
            'login.guard' => 'tenant',
            'login.tenant_id' => 'tenant-a',
        ])->post('/tenant-b/two-factor-challenge', ['recovery_code' => 'recovery-a']);

        $this->assertTrue(in_array($response->getStatusCode(), [403, 419, 302], true));
        $this->assertFalse(Auth::guard('tenant')->check());
    }

    // --------------------------------------------------------------------------
    // 2FA-12: TOTP Time Window & Expiry Verification
    // --------------------------------------------------------------------------

    public function test_totp_window_rejects_expired_and_future_codes(): void
    {
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $secret = $google2fa->generateSecretKey();

        $admin = $this->createAdminFixture([
            'two_factor_secret' => encrypt($secret),
            'two_factor_confirmed_at' => now(),
        ]);

        $session = ['login.id' => $admin->id, 'login.guard' => 'admin'];

        // Expired OTP from 5 minutes ago (-300 seconds, 10 windows)
        $pastTimestamp = (int) floor((time() - 300) / 30);
        $expiredOtp = $google2fa->oathTotp($secret, $pastTimestamp);

        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $responseExpired = $this->withSession($session)
            ->post('/superadmin/two-factor-challenge', ['code' => $expiredOtp]);

        $this->assertFalse(Auth::guard('admin')->check());
        $responseExpired->assertSessionHasErrors('code');

        // Future OTP from 5 minutes ahead (+300 seconds)
        $futureTimestamp = (int) floor((time() + 300) / 30);
        $futureOtp = $google2fa->oathTotp($secret, $futureTimestamp);

        $responseFuture = $this->withSession($session)
            ->post('/superadmin/two-factor-challenge', ['code' => $futureOtp]);

        $this->assertFalse(Auth::guard('admin')->check());
        $responseFuture->assertSessionHasErrors('code');
    }

    // --------------------------------------------------------------------------
    // 2FA-13, 2FA-14: Setup, QR, and Confirmation Flow
    // --------------------------------------------------------------------------

    public function test_settings_enable_and_confirm_lifecycle_admin_and_tenant(): void
    {
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);

        // 1. Admin Lifecycle
        $admin = AdminUser::create([
            'name' => 'Unconfirmed Admin',
            'email' => 'unconf-admin@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        // Enable 2FA
        $this->actingAs($admin, 'admin')
            ->postJson('/superadmin/two-factor-authentication')
            ->assertOk();

        $admin->refresh();
        $this->assertNotNull($admin->two_factor_secret);
        $this->assertNull($admin->two_factor_confirmed_at);

        // Confirm with wrong code -> fails, confirmed_at remains null
        $this->actingAs($admin, 'admin')
            ->postJson('/superadmin/confirmed-two-factor-authentication', ['code' => '000000'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        $admin->refresh();
        $this->assertNull($admin->two_factor_confirmed_at);

        // Confirm with valid code -> succeeds, confirmed_at is set
        $secret = decrypt($admin->two_factor_secret);
        $validCode = $google2fa->getCurrentOtp($secret);

        $this->actingAs($admin, 'admin')
            ->postJson('/superadmin/confirmed-two-factor-authentication', ['code' => $validCode])
            ->assertOk()
            ->assertJson(['status' => 'confirmed']);

        $admin->refresh();
        $this->assertNotNull($admin->two_factor_confirmed_at);

        // 2. Tenant Lifecycle
        $tenant = Tenant::create(['id' => 'lifecycle-tenant']);
        tenancy()->initialize($tenant);

        $user = User::create([
            'name' => 'Unconfirmed Tenant User',
            'email' => 'unconf-tenant@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $this->actingAs($user, 'tenant')
            ->postJson('/lifecycle-tenant/two-factor-authentication')
            ->assertOk();

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);

        // Confirm with wrong code
        $this->actingAs($user, 'tenant')
            ->postJson('/lifecycle-tenant/confirmed-two-factor-authentication', ['code' => '000000'])
            ->assertStatus(422);

        $user->refresh();
        $this->assertNull($user->two_factor_confirmed_at);

        // Confirm with valid code
        $tenantSecret = decrypt($user->two_factor_secret);
        $validTenantCode = $google2fa->getCurrentOtp($tenantSecret);

        $this->actingAs($user, 'tenant')
            ->postJson('/lifecycle-tenant/confirmed-two-factor-authentication', ['code' => $validTenantCode])
            ->assertOk();

        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);
    }

    // --------------------------------------------------------------------------
    // 2FA-15: Guest and Cross-Guard Authorization
    // --------------------------------------------------------------------------

    public function test_settings_endpoints_require_authentication_and_guard_boundaries(): void
    {
        // Guests cannot access settings
        $this->get('/superadmin/two-factor-authentication')->assertRedirect('/superadmin/login');
        $this->postJson('/superadmin/two-factor-authentication')->assertStatus(401);
        $this->deleteJson('/superadmin/two-factor-authentication')->assertStatus(401);
        $this->getJson('/superadmin/two-factor-qr-code')->assertStatus(401);
        $this->postJson('/superadmin/two-factor-recovery-codes')->assertStatus(401);

        // Tenant user cannot access SuperAdmin settings
        $tenant = Tenant::create(['id' => 'auth-b-tenant']);
        tenancy()->initialize($tenant);
        $user = $this->createTenantUserFixture('auth-b-tenant');

        $this->actingAs($user, 'tenant')
            ->postJson('/superadmin/two-factor-authentication')
            ->assertStatus(401);
    }

    // --------------------------------------------------------------------------
    // 2FA-16: Recovery Code Regeneration Uniqueness & Invalidation
    // --------------------------------------------------------------------------

    public function test_recovery_codes_regeneration_invalidates_old_codes(): void
    {
        $admin = $this->createAdminFixture();

        // 1. Initial generation
        $response1 = $this->actingAs($admin, 'admin')
            ->postJson('/superadmin/two-factor-recovery-codes')
            ->assertOk();

        $codes1 = $response1->json();
        $this->assertCount(8, $codes1);
        $this->assertCount(8, array_unique($codes1), 'All 8 generated recovery codes must be unique.');

        $oldCode = $codes1[0];

        // 2. Regenerate codes
        $response2 = $this->actingAs($admin, 'admin')
            ->postJson('/superadmin/two-factor-recovery-codes')
            ->assertOk();

        $codes2 = $response2->json();
        $this->assertCount(8, $codes2);
        $this->assertNotContains($oldCode, $codes2);

        $newCode = $codes2[0];

        // 3. Attempt challenge with old code -> rejected
        Auth::guard('admin')->logout();
        $session = ['login.id' => $admin->id, 'login.guard' => 'admin'];

        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $this->withSession($session)
            ->post('/superadmin/two-factor-challenge', ['recovery_code' => $oldCode]);

        $this->assertFalse(Auth::guard('admin')->check(), 'Old recovery code must be invalidated.');

        // 4. Attempt challenge with new code -> succeeds
        $this->withSession($session)
            ->post('/superadmin/two-factor-challenge', ['recovery_code' => $newCode])
            ->assertRedirect('/superadmin');

        $this->assertTrue(Auth::guard('admin')->check(), 'New recovery code must authenticate successfully.');
    }

    // --------------------------------------------------------------------------
    // 2FA-17, 2FA-18: Disable Flow & Policy Enforcement
    // --------------------------------------------------------------------------

    public function test_disable_2fa_flow_and_policy_enforcement(): void
    {
        // When not enforced: Admin disable succeeds
        $admin = $this->createAdminFixture();

        $this->actingAs($admin, 'admin')
            ->deleteJson('/superadmin/two-factor-authentication')
            ->assertOk()
            ->assertJson(['status' => 'disabled']);

        $admin->refresh();
        $this->assertNull($admin->two_factor_secret);
        $this->assertNull($admin->two_factor_confirmed_at);

        // When enforced by AdminSettings: Admin disable fails
        AdminSettings::create(['force_two_factor_auth' => true]);
        $enforcedAdmin = $this->createAdminFixture();

        $this->actingAs($enforcedAdmin, 'admin')
            ->deleteJson('/superadmin/two-factor-authentication')
            ->assertStatus(422)
            ->assertJsonPath('message', 'المصادقة الثنائية مفروضة بموجب سياسة النظام ولا يمكن تعطيلها.');

        // When enforced by TenantSettings: Tenant disable fails
        $tenant = Tenant::create(['id' => 'enforced-t']);
        tenancy()->initialize($tenant);
        TenantSettings::create(['force_two_factor_auth' => true]);
        $enforcedTenantUser = $this->createTenantUserFixture('enforced-t');

        $this->actingAs($enforcedTenantUser, 'tenant')
            ->deleteJson('/enforced-t/two-factor-authentication')
            ->assertStatus(422);
    }

    // --------------------------------------------------------------------------
    // 2FA-23: Deactivated User Rejection
    // --------------------------------------------------------------------------

    public function test_deactivated_admin_and_tenant_user_rejected_during_2fa(): void
    {
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);

        // Inactive Admin
        $secretAdmin = $google2fa->generateSecretKey();
        $admin = $this->createAdminFixture([
            'status' => 'inactive',
            'two_factor_secret' => encrypt($secretAdmin),
            'two_factor_confirmed_at' => now(),
        ]);
        $validAdminCode = $google2fa->getCurrentOtp($secretAdmin);

        $this->withSession(['login.id' => $admin->id, 'login.guard' => 'admin'])
            ->post('/superadmin/two-factor-challenge', ['code' => $validAdminCode]);

        $this->assertFalse(Auth::guard('admin')->check(), 'Deactivated admin must be rejected.');

        // Inactive Tenant User
        $tenant = Tenant::create(['id' => 'deact-t']);
        tenancy()->initialize($tenant);
        $secretTenant = $google2fa->generateSecretKey();
        $tenantUser = $this->createTenantUserFixture('deact-t', [
            'status' => 'inactive',
            'two_factor_secret' => encrypt($secretTenant),
            'two_factor_confirmed_at' => now(),
        ]);
        $validTenantCode = $google2fa->getCurrentOtp($secretTenant);

        $this->withSession([
            'login.id' => $tenantUser->id,
            'login.guard' => 'tenant',
            'login.tenant_id' => 'deact-t',
        ])->post('/deact-t/two-factor-challenge', ['code' => $validTenantCode]);

        $this->assertFalse(Auth::guard('tenant')->check(), 'Deactivated tenant user must be rejected.');
    }

    // --------------------------------------------------------------------------
    // 2FA-24: Throttling on Challenge Store
    // --------------------------------------------------------------------------

    public function test_throttle_is_enforced_on_challenge_store_admin_and_tenant(): void
    {
        // Admin throttle
        $admin = $this->createAdminFixture();
        $adminSession = ['login.id' => $admin->id, 'login.guard' => 'admin'];

        for ($i = 0; $i < 5; $i++) {
            $this->withSession($adminSession)
                ->post('/superadmin/two-factor-challenge', ['code' => '000000']);
        }
        $this->withSession($adminSession)
            ->post('/superadmin/two-factor-challenge', ['code' => '000000'])
            ->assertStatus(429);

        // Tenant throttle
        $tenant = Tenant::create(['id' => 'throttle-t']);
        tenancy()->initialize($tenant);
        $tenantUser = $this->createTenantUserFixture('throttle-t');
        $tenantSession = [
            'login.id' => $tenantUser->id,
            'login.guard' => 'tenant',
            'login.tenant_id' => 'throttle-t',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->withSession($tenantSession)
                ->post('/throttle-t/two-factor-challenge', ['code' => '000000']);
        }
        $this->withSession($tenantSession)
            ->post('/throttle-t/two-factor-challenge', ['code' => '000000'])
            ->assertStatus(429);
    }

    public function test_form_validation_endpoint_accepts_two_factor_challenge(): void
    {
        $admin = $this->createAdminFixture();

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
        $user = $this->createTenantUserFixture('fv-tenant');

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
