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
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Tests\TestCase;

class TestTwoFactorTenancyBootstrapper implements TenancyBootstrapper
{
    public static array $databases = [];

    public function bootstrap(\Stancl\Tenancy\Contracts\Tenant $tenant): void
    {
        $db = static::$databases[$tenant->getTenantKey()] ?? ':memory:';
        config(['database.connections.tenant.database' => $db]);
        DB::purge('tenant');
    }

    public function revert(): void
    {
        DB::purge('tenant');
    }
}

final class TwoFactorAuthenticationBatchATest extends TestCase
{
    protected array $tempFiles = [];

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

        // Domains table (for domain tenants)
        Schema::connection('mysql')->create('domains', function (Blueprint $table): void {
            $table->id();
            $table->string('domain')->unique();
            $table->string('tenant_id');
            $table->timestamps();
        });

        $this->createTenantSchema('tenant');
    }

    private function createTenantSchema(string $connection = 'tenant'): void
    {
        Schema::connection($connection)->dropIfExists('users');
        Schema::connection($connection)->dropIfExists('settings');

        // Tenant users table
        Schema::connection($connection)->create('users', function (Blueprint $table): void {
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
        Schema::connection($connection)->create('settings', function (Blueprint $table): void {
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

        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        $this->tempFiles = [];
        TestTwoFactorTenancyBootstrapper::$databases = [];

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
        $engine = app(\PragmaRX\Google2FA\Google2FA::class);

        // 1. Create Admin user with ID=1 on mysql connection
        $adminSecret = $engine->generateSecretKey();
        $admin = new AdminUser([
            'name' => 'Admin User ID 1',
            'email' => 'admin-isolation@example.test',
            'password' => bcrypt('AdminPass123!'),
            'status' => 'active',
        ]);
        $admin->id = 1;
        $admin->two_factor_secret = encrypt($adminSecret);
        $admin->two_factor_confirmed_at = now();
        $admin->two_factor_recovery_codes = encrypt(json_encode(['admin-recovery-123']));
        $admin->save();

        self::assertSame(1, (int) $admin->id);
        self::assertSame('mysql', $admin->getConnectionName());

        // 2. Create Tenant user with ID=1 on tenant connection
        $tenant = Tenant::create(['id' => 'iso-tenant']);
        tenancy()->initialize($tenant);

        $tenantSecret = $engine->generateSecretKey();
        $tenantUser = new User([
            'name' => 'Tenant User ID 1',
            'email' => 'tenant-isolation@example.test',
            'password' => bcrypt('TenantPass123!'),
            'status' => 'active',
        ]);
        $tenantUser->id = 1;
        $tenantUser->two_factor_secret = encrypt($tenantSecret);
        $tenantUser->two_factor_confirmed_at = now();
        $tenantUser->two_factor_recovery_codes = encrypt(json_encode(['tenant-recovery-123']));
        $tenantUser->save();

        self::assertSame(1, (int) $tenantUser->id);
        self::assertSame('tenant', $tenantUser->getConnectionName());

        $adminOtp = $engine->getCurrentOtp($adminSecret);
        $tenantOtp = $engine->getCurrentOtp($tenantSecret);

        // 3. Admin pending session with valid Admin TOTP posting to Tenant challenge -> rejected fail-closed
        $responseTenant = $this->withSession(['login.id' => 1, 'login.guard' => 'admin'])
            ->post('/iso-tenant/two-factor-challenge', ['code' => $adminOtp]);

        $this->assertTrue(in_array($responseTenant->getStatusCode(), [403, 419, 302], true));
        $this->assertFalse(Auth::guard('tenant')->check());

        // 4. Tenant pending session with valid Tenant TOTP posting to Admin challenge -> rejected fail-closed
        $responseAdmin = $this->withSession([
            'login.id' => 1,
            'login.guard' => 'tenant',
            'login.tenant_id' => 'iso-tenant',
        ])->post('/superadmin/two-factor-challenge', ['code' => $tenantOtp]);

        $this->assertTrue(in_array($responseAdmin->getStatusCode(), [403, 419, 302], true));
        $this->assertFalse(Auth::guard('admin')->check());
    }

    public function test_cross_tenant_isolation_rejected(): void
    {
        $dbFileA = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tenant_a_' . uniqid() . '.sqlite';
        $dbFileB = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tenant_b_' . uniqid() . '.sqlite';
        touch($dbFileA);
        touch($dbFileB);

        $this->tempFiles[] = $dbFileA;
        $this->tempFiles[] = $dbFileB;

        TestTwoFactorTenancyBootstrapper::$databases = [
            'tenant-a' => $dbFileA,
            'tenant-b' => $dbFileB,
        ];
        config(['tenancy.bootstrappers' => [TestTwoFactorTenancyBootstrapper::class]]);

        $tenantA = Tenant::create(['id' => 'tenant-a']);
        $tenantB = Tenant::create(['id' => 'tenant-b']);

        // Setup Tenant A database
        tenancy()->initialize($tenantA);
        $pdoA = DB::connection('tenant')->getPdo();
        $this->createTenantSchema('tenant');

        $engine = app(\PragmaRX\Google2FA\Google2FA::class);
        $secretA = $engine->generateSecretKey();
        $userA = new User([
            'name' => 'User in Tenant A',
            'email' => 'user-a@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $userA->id = 1;
        $userA->two_factor_secret = encrypt($secretA);
        $userA->two_factor_confirmed_at = now();
        $userA->two_factor_recovery_codes = encrypt(json_encode(['recovery-a-only']));
        $userA->save();

        self::assertSame(1, (int) $userA->id);
        self::assertSame(1, User::count());
        $codeA = $engine->getCurrentOtp($secretA);

        // Setup Tenant B database
        tenancy()->initialize($tenantB);
        $pdoB = DB::connection('tenant')->getPdo();
        $this->createTenantSchema('tenant');

        $secretB = $engine->generateSecretKey();
        $userB = new User([
            'name' => 'User in Tenant B',
            'email' => 'user-b@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $userB->id = 1;
        $userB->two_factor_secret = encrypt($secretB);
        $userB->two_factor_confirmed_at = now();
        $userB->two_factor_recovery_codes = encrypt(json_encode(['recovery-b-only']));
        $userB->save();

        self::assertSame(1, (int) $userB->id);
        self::assertSame(1, User::count());

        // Verify PDOs are distinct database instances
        self::assertNotSame($pdoA, $pdoB);

        // Verify user counts in respective databases
        self::assertSame('User in Tenant B', User::find(1)->name);
        tenancy()->initialize($tenantA);
        self::assertSame('User in Tenant A', User::find(1)->name);
        self::assertSame(1, User::count());
        tenancy()->end();

        // 1. Session from Tenant A trying to challenge on Tenant B endpoint -> rejected fail-closed
        $response = $this->withSession([
            'login.id' => 1,
            'login.guard' => 'tenant',
            'login.tenant_id' => 'tenant-a',
        ])->post('/tenant-b/two-factor-challenge', ['code' => $codeA]);

        $this->assertTrue(in_array($response->getStatusCode(), [403, 419, 302], true));
        $this->assertFalse(Auth::guard('tenant')->check());

        // 2. Tampered session with tenant-b hint using User A's credentials on Tenant B -> rejected
        $responseTampered = $this->withSession([
            'login.id' => 1,
            'login.guard' => 'tenant',
            'login.tenant_id' => 'tenant-b',
        ])->post('/tenant-b/two-factor-challenge', ['code' => $codeA]);

        $this->assertTrue(in_array($responseTampered->getStatusCode(), [422, 403, 302], true));
        $this->assertFalse(Auth::guard('tenant')->check());

        // 3. User A recovery code used on Tenant B -> rejected
        $responseRecovery = $this->withSession([
            'login.id' => 1,
            'login.guard' => 'tenant',
            'login.tenant_id' => 'tenant-b',
        ])->post('/tenant-b/two-factor-challenge', ['recovery_code' => 'recovery-a-only']);

        $this->assertTrue(in_array($responseRecovery->getStatusCode(), [422, 403, 302], true));
        $this->assertFalse(Auth::guard('tenant')->check());

        // 4. Verify DBs are intact and no recovery codes consumed falsely
        tenancy()->initialize($tenantA);
        self::assertSame(['recovery-a-only'], json_decode(decrypt(User::find(1)->two_factor_recovery_codes), true));
        tenancy()->initialize($tenantB);
        self::assertSame(['recovery-b-only'], json_decode(decrypt(User::find(1)->two_factor_recovery_codes), true));
        tenancy()->end();
    }

    public function test_domain_tenant_challenge_renders_and_completes_lifecycle(): void
    {
        $tenant = Tenant::create(['id' => 'domain-tenant', 'access_type' => 'domain']);
        $tenant->domains()->create(['domain' => 'tenant-auth.example.test']);

        tenancy()->initialize($tenant);
        $engine = app(\PragmaRX\Google2FA\Google2FA::class);
        $secret = $engine->generateSecretKey();
        $user = new User([
            'name' => 'Domain Tenant User',
            'email' => 'domain-user@example.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $user->two_factor_secret = encrypt($secret);
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = encrypt(json_encode(['domain-recovery-code-1']));
        $user->save();
        tenancy()->end();

        $code = $engine->getCurrentOtp($secret);
        $pending = ['login.id' => $user->id, 'login.guard' => 'tenant', 'login.tenant_id' => 'domain-tenant'];

        // 1. GET challenge renders on domain with correct action and single OTP placeholder
        $this->withSession($pending)->get('http://tenant-auth.example.test/two-factor-challenge')
            ->assertOk()
            ->assertSee('action="http://tenant-auth.example.test/two-factor-challenge"', false)
            ->assertSee('placeholder="XXXXXX"', false);

        // 2. Form validation endpoint accepts on domain
        $this->postJson('http://tenant-auth.example.test/form-validation', [
            'target_path' => '/two-factor-challenge',
            'target_method' => 'POST',
            'fields' => ['code'],
            'code' => $code,
        ])->assertOk()->assertJsonPath('success', true);

        // 3. POST TOTP on domain completes challenge and redirects to domain dashboard
        $this->withSession($pending)
            ->post('http://tenant-auth.example.test/two-factor-challenge', ['code' => $code])
            ->assertRedirect('http://tenant-auth.example.test/dashboard');

        self::assertTrue(Auth::guard('tenant')->check());
        Auth::guard('tenant')->logout();

        // 4. POST Recovery code on domain completes challenge and consumes code
        $this->withSession($pending)
            ->post('http://tenant-auth.example.test/two-factor-challenge', ['recovery_code' => 'domain-recovery-code-1'])
            ->assertRedirect('http://tenant-auth.example.test/dashboard');

        self::assertTrue(Auth::guard('tenant')->check());
        Auth::guard('tenant')->logout();

        tenancy()->initialize($tenant);
        self::assertSame([], json_decode(decrypt($user->fresh()->two_factor_recovery_codes), true));
        tenancy()->end();

        // 5. Cancel on domain redirects to domain login and cleans session
        $this->withSession($pending)
            ->get('http://tenant-auth.example.test/two-factor-challenge/cancel')
            ->assertRedirect('http://tenant-auth.example.test/login')
            ->assertSessionMissing('login.id')
            ->assertSessionMissing('login.guard')
            ->assertSessionMissing('login.tenant_id');
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
