<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Middleware\Tenancy\CheckSubscription;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\TwoFactorClosureTenancyBootstrapper;
use Tests\TestCase;

/** Independent review probes; intentionally outside the regular suite. */
final class TwoFactorAuthenticationClosureTest extends TestCase
{
    private array $temporaryDatabases = [];

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

        foreach (['mysql' => 'admin_users', 'tenant' => 'users'] as $connection => $tableName) {
            Schema::connection($connection)->create($tableName, function (Blueprint $table): void {
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
                $table->string('current_session_id')->nullable();
                $table->string('global_uid')->nullable();
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        }
        Schema::connection('mysql')->create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('db_database')->default(':memory:');
            $table->string('db_username')->default('');
            $table->string('db_password')->default('');
            $table->string('company_name')->default('Review fixture');
            $table->string('commercial_number')->default('1234567890');
            $table->string('access_type')->default('path');
            $table->json('data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        DB::purge('tenant');
        foreach ($this->temporaryDatabases as $path) {
            unlink($path);
        }
        TwoFactorClosureTenancyBootstrapper::$databases = [];
        parent::tearDown();
    }

    private int $sequence = 0;

    private function fixture(string $mode): array
    {
        Auth::guard('admin')->logout();
        Auth::guard('tenant')->logout();
        Auth::forgetGuards();
        $this->flushSession();
        app('cookie')->flushQueuedCookies();
        $this->defaultCookies = [];
        $this->unencryptedCookies = [];
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        foreach (['mysql', 'tenant'] as $connection) {
            if (! Schema::connection($connection)->hasTable('settings')) {
                Schema::connection($connection)->create('settings', function (Blueprint $table): void {
                    $table->id();
                    $table->boolean('maintenance_mode')->default(false);
                });
            }
        }
        if (! Schema::connection('mysql')->hasTable('domains')) {
            Schema::connection('mysql')->create('domains', function (Blueprint $table): void {
                $table->id();
                $table->string('domain')->unique();
                $table->string('tenant_id');
                $table->timestamps();
            });
        }
        $n = ++$this->sequence;
        // Each synthetic client has an independent HTTP throttle budget.
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.($n % 250 + 1)]);
        $guard = $mode === 'admin' ? 'admin' : 'tenant';
        DB::setDefaultConnection($guard === 'admin' ? 'mysql' : 'tenant');
        $tenantId = null;
        $root = '/superadmin';
        $login = '/superadmin/login';
        if ($guard === 'tenant') {
            $tenantId = "accept-$mode-$n";
            $tenant = Tenant::create(['id' => $tenantId, 'access_type' => $mode === 'domain' ? 'domain' : 'path']);
            if ($mode === 'domain') {
                $host = "accept-$n.example.test";
                $tenant->domains()->create(['domain' => $host]);
                $root = "http://$host";
                $login = "$root/login";
            } else {
                $root = "/$tenantId";
                $login = $root.'/login';
            }
            tenancy()->initialize($tenant);
        }
        $engine = app(Google2FA::class);
        $secret = $engine->generateSecretKey();
        $class = $guard === 'admin' ? AdminUser::class : User::class;
        $user = $class::create([
            'name' => "Synthetic acceptance $n", 'email' => "$mode-$n@example.test",
            'password' => bcrypt('SyntheticReview123!'), 'status' => 'active',
        ]);
        $codes = ["accept-$mode-$n-one", "accept-$mode-$n-two"];
        $user->forceFill([
            'two_factor_secret' => encrypt($secret), 'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode($codes)),
        ])->save();
        $pending = ['login.id' => $user->id, 'login.guard' => $guard,
            'login.tenant_id' => $tenantId, 'login.remember' => false];
        if ($mode === 'domain') {
            tenancy()->end();
        }

        return compact('user', 'guard', 'root', 'login', 'pending', 'secret', 'codes');
    }

    public function test_every_settings_endpoint_rejects_guests_in_every_scope(): void
    {
        foreach (['admin', 'path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            $this->get($f['root'].'/two-factor-authentication')->assertRedirect($f['login']);
            foreach ([
                ['postJson', '/two-factor-authentication'],
                ['deleteJson', '/two-factor-authentication'],
                ['postJson', '/confirmed-two-factor-authentication'],
                ['getJson', '/two-factor-qr-code'],
                ['postJson', '/two-factor-recovery-codes'],
            ] as [$method, $endpoint]) {
                $this->{$method}($f['root'].$endpoint, ['code' => '123456', 'user_id' => $f['user']->id])->assertStatus(401);
            }
            self::assertSame($f['codes'], $f['user']->fresh()->recoveryCodes());
        }
    }

    public static function loginScopes(): array
    {
        return [['admin'], ['path'], ['domain']];
    }

    #[DataProvider('loginScopes')]
    public function test_password_step_remember_and_session_lifecycle_in_every_scope(string $mode): void
    {
        $f = $this->fixture($mode);
        $this->app['session']->start();
        $before = session()->getId();
        $passwordLogin = $mode === 'admin' ? '/superadmin/login' : $f['root'].'/login';
        $response = $this->post($passwordLogin, [
            'email' => $f['user']->email, 'password' => 'SyntheticReview123!', 'remember' => '1',
        ]);
        $response->assertRedirect($f['root'].'/two-factor-challenge')
            ->assertSessionHas('login.id', $f['user']->id)
            ->assertSessionHas('login.guard', $f['guard'])
            ->assertSessionHas('login.remember', true)
            ->assertCookieMissing(Auth::guard($f['guard'])->getRecallerName());
        self::assertFalse(Auth::guard($f['guard'])->check());
        $code = app(Google2FA::class)->getCurrentOtp($f['secret']);
        $completed = $this->post($f['root'].'/two-factor-challenge', ['code' => $code]);
        $expected = $mode === 'admin' ? '/superadmin' : ($mode === 'path' ? $f['root'].'/dashboard' : '/dashboard');
        $completed->assertRedirect($expected)->assertCookie(Auth::guard($f['guard'])->getRecallerName());
        self::assertNotSame($before, session()->getId());
        self::assertSame($f['user']->id, Auth::guard($f['guard'])->id());
        foreach (['login.id', 'login.guard', 'login.tenant_id', 'login.remember'] as $key) {
            self::assertNull(session($key));
        }
        if ($mode !== 'admin') {
            self::assertSame($f['pending']['login.tenant_id'], session('auth_tenant_id'));
        }
    }

    public function test_setup_confirm_and_disable_lifecycle_uses_only_authenticated_account(): void
    {
        foreach (['admin', 'path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            $other = $f['user']->replicate();
            $other->email = 'other-'.$f['user']->email;
            $other->global_uid = null;
            $other->save();
            $otherSecret = $other->two_factor_secret;
            $f['user']->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null, 'two_factor_recovery_codes' => null])->save();
            $this->actingAs($f['user'], $f['guard']);
            $this->postJson($f['root'].'/two-factor-authentication', ['user_id' => $other->id])->assertOk();
            $setup = $f['user']->fresh();
            self::assertNotEmpty($setup->two_factor_secret);
            self::assertNull($setup->two_factor_confirmed_at);
            $secret = decrypt($setup->two_factor_secret);
            $this->getJson($f['root'].'/two-factor-qr-code')->assertOk()->assertJsonStructure(['svg']);
            $engine = app(Google2FA::class);
            parse_str(parse_url($setup->twoFactorQrCodeUrl(), PHP_URL_QUERY), $qr);
            self::assertSame($secret, $qr['secret']);
            self::assertStringContainsString($setup->email, urldecode($setup->twoFactorQrCodeUrl()));
            $this->postJson($f['root'].'/confirmed-two-factor-authentication', ['code' => ''])->assertStatus(422);
            $valid = $engine->getCurrentOtp($secret);
            $wrong = $valid === '000000' ? '000001' : '000000';
            $this->postJson($f['root'].'/confirmed-two-factor-authentication', ['code' => $wrong])->assertStatus(422);
            self::assertNull($f['user']->fresh()->two_factor_confirmed_at);
            self::assertSame($secret, decrypt($f['user']->fresh()->two_factor_secret));
            $this->postJson($f['root'].'/confirmed-two-factor-authentication', ['code' => $valid, 'user_id' => $other->id])->assertOk();
            self::assertNotNull($f['user']->fresh()->two_factor_confirmed_at);
            self::assertSame($otherSecret, $other->fresh()->two_factor_secret);
            Auth::guard($f['guard'])->logout();
            $login = $mode === 'admin' ? '/superadmin/login' : $f['root'].'/login';
            $this->post($login, ['email' => $f['user']->email, 'password' => 'SyntheticReview123!'])
                ->assertRedirect($f['root'].'/two-factor-challenge');
            self::assertFalse(Auth::guard($f['guard'])->check());
        }
    }

    public function test_deactivation_and_deletion_after_password_reject_valid_code(): void
    {
        foreach (['admin', 'path', 'domain'] as $mode) {
            foreach (['inactive', 'deleted'] as $change) {
                $f = $this->fixture($mode);
                $login = $mode === 'admin' ? '/superadmin/login' : $f['root'].'/login';
                $this->post($login, ['email' => $f['user']->email, 'password' => 'SyntheticReview123!'])
                    ->assertRedirect($f['root'].'/two-factor-challenge');
                if ($change === 'inactive') {
                    $f['user']->forceFill(['status' => 'inactive'])->save();
                } else {
                    $f['user']->delete();
                }
                $code = app(Google2FA::class)->getCurrentOtp($f['secret']);
                $response = $this->postJson($f['root'].'/two-factor-challenge', ['code' => $code]);
                self::assertContains($response->status(), [403, 419, 422]);
                self::assertFalse(Auth::guard($f['guard'])->check());
            }
        }
    }

    public function test_validly_encrypted_malformed_secret_is_rejected_without_server_error(): void
    {
        foreach (['admin', 'path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            $f['user']->forceFill(['two_factor_secret' => encrypt('not-base32-!')])->save();
            $this->withSession($f['pending'])->postJson($f['root'].'/two-factor-challenge', ['code' => '123456'])
                ->assertStatus(422)->assertJsonValidationErrors('code');
            self::assertFalse(Auth::guard($f['guard'])->check());
        }
    }

    public function test_reset_authorization_from_another_tenant_cannot_change_current_account(): void
    {
        foreach (['path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            $before = $f['user']->password;
            $verification = [
                'tenant_id' => 'different-tenant', 'email' => $f['user']->email, 'user_id' => $f['user']->id,
                'verified' => true, 'expires_at' => now()->addMinutes(5)->timestamp,
            ];
            $response = $this->withSession(['password_reset_verification' => $verification])
                ->post($f['root'].'/reset-password', ['password' => 'DifferentReview456!', 'password_confirmation' => 'DifferentReview456!']);
            self::assertSame($before, $f['user']->fresh()->password, 'A reset grant from tenant A must not mutate tenant B.');
            $response->assertRedirect($f['root'].'/forgot-password');
            self::assertFalse(Auth::guard('tenant')->check());
        }
    }

    public function test_foreign_tenant_otp_cannot_be_verified_or_resent(): void
    {
        foreach (['path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            $verification = [
                'tenant_id' => 'different-tenant', 'email' => $f['user']->email, 'user_id' => $f['user']->id,
                'verified' => false, 'otp_hash' => hash('sha256', '123456'),
                'expires_at' => now()->addMinutes(2)->timestamp, 'session_expires_at' => now()->addMinutes(15)->timestamp,
            ];
            $this->withSession(['password_reset_verification' => $verification])
                ->post($f['root'].'/forgot-password/verify', ['otp' => '123456'])
                ->assertRedirect($f['root'].'/forgot-password');
            self::assertEmpty(session('password_reset_verification.verified'));
            $this->withSession(['password_reset_verification' => $verification])
                ->postJson($f['root'].'/forgot-password/resend-otp')->assertStatus(422);
            self::assertEmpty(session('password_reset_verification.verified'));
        }
    }

    public function test_current_tenant_verified_reset_still_changes_only_the_verified_account(): void
    {
        foreach (['path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            $verification = [
                'tenant_id' => $f['pending']['login.tenant_id'], 'email' => $f['user']->email, 'user_id' => $f['user']->id,
                'verified' => true, 'expires_at' => now()->addMinutes(5)->timestamp,
            ];
            $this->withSession(['password_reset_verification' => $verification])->post($f['root'].'/reset-password', [
                'email' => 'tampered@example.test', 'password' => 'DifferentReview456!', 'password_confirmation' => 'DifferentReview456!',
            ])->assertRedirect($f['login']);
            self::assertTrue(Hash::check('DifferentReview456!', $f['user']->fresh()->password));
            self::assertNull(session('password_reset_verification'));
        }
    }

    public function test_optional_disable_is_persisted_and_next_password_login_skips_challenge(): void
    {
        foreach (['admin', 'path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            $this->actingAs($f['user'], $f['guard']);
            $this->deleteJson($f['root'].'/two-factor-authentication')->assertOk();
            foreach (['two_factor_secret', 'two_factor_confirmed_at', 'two_factor_recovery_codes'] as $field) {
                self::assertNull($f['user']->fresh()->{$field});
            }
            Auth::guard($f['guard'])->logout();
            $login = $mode === 'admin' ? '/superadmin/login' : $f['root'].'/login';
            $destination = $mode === 'admin' ? '/superadmin' : ($mode === 'path' ? $f['root'].'/dashboard' : '/dashboard');
            $this->post($login, ['email' => $f['user']->email, 'password' => 'SyntheticReview123!'])->assertRedirect($destination);
            self::assertSame($f['user']->id, Auth::guard($f['guard'])->id());
            Auth::guard($f['guard'])->logout();
            $this->withSession($f['pending'])->postJson($f['root'].'/two-factor-challenge', ['recovery_code' => $f['codes'][0]])
                ->assertStatus(403);
        }
    }

    public static function subscriptionChanges(): array
    {
        return [
            ['path', 'cancelled'], ['domain', 'cancelled'],
            ['path', 'expired'], ['domain', 'expired'],
            ['path', 'suspended'], ['domain', 'suspended'],
            ['path', 'pending'], ['domain', 'pending'],
            ['path', 'expired', true], ['domain', 'expired', true],
        ];
    }

    #[DataProvider('subscriptionChanges')]
    public function test_subscription_changed_during_challenge_cannot_use_cached_active_access(string $mode, string $status, bool $owner = false): void
    {
        Schema::connection('mysql')->create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->string('status');
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
        Schema::connection('mysql')->create('bills', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->string('status');
            $table->softDeletes();
        });
        $f = $this->fixture($mode);
        if (! $owner) {
            $f['user']->forceFill(['id' => 2])->save();
            $f['pending']['login.id'] = 2;
        }
        $tenantId = $f['pending']['login.tenant_id'];
        DB::connection('mysql')->table('subscriptions')->insert(['tenant_id' => $tenantId, 'status' => 'active']);
        $active = Subscription::first();
        cache()->put("tenant:$tenantId:subscription", $active, 300);
        DB::connection('mysql')->table('subscriptions')->where('tenant_id', $tenantId)->update(['status' => $status]);
        $code = app(Google2FA::class)->getCurrentOtp($f['secret']);
        $this->withSession($f['pending'])->post($f['root'].'/two-factor-challenge', ['code' => $code])->assertRedirect();
        $protectedReached = false;
        $response = app(CheckSubscription::class)->handle(request(), function () use (&$protectedReached) {
            $protectedReached = true;

            return response('protected');
        });
        self::assertFalse($protectedReached, 'Cached ACTIVE must not grant protected access after an eligibility change during pending 2FA.');
        self::assertSame(302, $response->getStatusCode());
        if ($owner) {
            self::assertTrue(Auth::guard('tenant')->check());
            self::assertSame(tenant_route_name('subscriptions.index'), $response->getTargetUrl());
        } else {
            self::assertFalse(Auth::guard('tenant')->check());
        }
    }

    public function test_colliding_ids_and_emails_in_distinct_databases_do_not_share_challenge_or_reset_grants(): void
    {
        $this->fixture('admin');
        $this->withoutMiddleware(ThrottleRequests::class);
        $usersSql = DB::connection('tenant')->selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'")->sql;
        $settingsSql = DB::connection('tenant')->selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name='settings'")->sql;
        $directory = storage_path('framework/testing');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        foreach (['closure-a', 'closure-b'] as $tenantId) {
            $path = tempnam($directory, 'twofactor-');
            self::assertNotFalse($path);
            $this->temporaryDatabases[] = $path;
            TwoFactorClosureTenancyBootstrapper::$databases[$tenantId] = $path;
            $tenant = Tenant::create(['id' => $tenantId]);
            $tenant->domains()->create(['domain' => "$tenantId.example.test"]);
        }
        config(['tenancy.bootstrappers' => [TwoFactorClosureTenancyBootstrapper::class]]);
        $secrets = [];
        $connections = [];
        foreach (['closure-a', 'closure-b'] as $tenantId) {
            tenancy()->initialize(Tenant::findOrFail($tenantId));
            $connections[] = DB::connection('tenant')->getPdo();
            DB::connection('tenant')->statement($usersSql);
            DB::connection('tenant')->statement($settingsSql);
            $secrets[$tenantId] = app(Google2FA::class)->generateSecretKey();
            $user = User::create([
                'name' => $tenantId, 'email' => 'collision@example.test', 'password' => bcrypt('SyntheticReview123!'),
                'status' => 'active',
            ]);
            $user->forceFill([
                'two_factor_secret' => encrypt($secrets[$tenantId]),
                'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => encrypt(json_encode(["$tenantId-recovery"])),
            ])->save();
            self::assertSame(1, $user->id);
            self::assertSame(1, User::count());
            tenancy()->end();
        }
        self::assertNotSame($connections[0], $connections[1]);
        $pending = ['login.id' => 1, 'login.guard' => 'tenant', 'login.tenant_id' => 'closure-a'];
        $codeA = app(Google2FA::class)->getCurrentOtp($secrets['closure-a']);
        foreach (['/closure-b', 'http://closure-b.example.test'] as $root) {
            $this->withSession($pending)->postJson($root.'/two-factor-challenge', ['code' => $codeA])->assertStatus(403);
            $this->withSession($pending)->postJson($root.'/two-factor-challenge', ['recovery_code' => 'closure-a-recovery'])->assertStatus(403);
            self::assertFalse(Auth::guard('tenant')->check());
            $this->withSession(['password_reset_verification' => [
                'tenant_id' => 'closure-a', 'user_id' => 1, 'email' => 'collision@example.test',
                'verified' => true, 'expires_at' => now()->addMinutes(5)->timestamp,
            ]])->post($root.'/reset-password', [
                'password' => 'DifferentReview456!', 'password_confirmation' => 'DifferentReview456!',
            ])->assertRedirect($root.'/forgot-password');
        }
        foreach (['closure-a', 'closure-b'] as $tenantId) {
            tenancy()->initialize(Tenant::findOrFail($tenantId));
            $user = User::findOrFail(1);
            self::assertTrue(Hash::check('SyntheticReview123!', $user->password));
            self::assertSame(["$tenantId-recovery"], $user->recoveryCodes());
            tenancy()->end();
        }
        // Release test-held PDO handles before Windows removes the owned temporary files.
        $connections = [];
    }

    public function test_company_deleted_after_password_step_cannot_complete_challenge(): void
    {
        foreach (['path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            $this->post($f['root'].'/login', ['email' => $f['user']->email, 'password' => 'SyntheticReview123!'])
                ->assertRedirect($f['root'].'/two-factor-challenge');
            Tenant::findOrFail($f['pending']['login.tenant_id'])->delete();
            tenancy()->end();
            $code = app(Google2FA::class)->getCurrentOtp($f['secret']);
            $response = $this->postJson($f['root'].'/two-factor-challenge', ['code' => $code]);
            self::assertContains($response->status(), [403, 404, 419, 422]);
            self::assertFalse(Auth::guard('tenant')->check());
        }
    }

    public function test_missing_session_after_password_step_cannot_be_replaced_with_payload_ids(): void
    {
        foreach (['admin', 'path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            $login = $mode === 'admin' ? '/superadmin/login' : $f['root'].'/login';
            $this->post($login, ['email' => $f['user']->email, 'password' => 'SyntheticReview123!'])->assertRedirect();
            $this->flushSession();
            $code = app(Google2FA::class)->getCurrentOtp($f['secret']);
            $this->postJson($f['root'].'/two-factor-challenge', [
                'code' => $code, 'login' => $f['pending'], 'tenant_id' => $f['pending']['login.tenant_id'], 'user_id' => $f['user']->id,
            ])->assertStatus(403);
            self::assertFalse(Auth::guard($f['guard'])->check());
            $this->get($f['root'].'/two-factor-challenge')->assertRedirect($f['login']);
        }
    }

    public static function adminIntendedDestinations(): array
    {
        return [
            ['/another-tenant/dashboard', '/superadmin'],
            ['https://external.example.test/superadmin/users', '/superadmin'],
            ['/superadmin/users', '/superadmin/users'],
            ['http://localhost:8000/superadmin/users', 'http://localhost:8000/superadmin/users'],
            ['http://evil.example\\@localhost:8000/superadmin/users', '/superadmin'],
            ['http://user:pass@localhost:8000/superadmin/users', '/superadmin'],
            ['/superadmin/../another-tenant/dashboard', '/superadmin'],
            ['/superadmin/%2e%2e/another-tenant/dashboard', '/superadmin'],
            ['/superadmin/users?filter=active#rows', '/superadmin/users?filter=active#rows'],
        ];
    }

    #[DataProvider('adminIntendedDestinations')]
    public function test_admin_two_factor_completion_keeps_the_intended_destination_in_its_boundary(string $intended, string $expected): void
    {
        $f = $this->fixture('admin');
        $code = app(Google2FA::class)->getCurrentOtp($f['secret']);
        $this->withSession($f['pending'] + ['url.intended' => $intended])
            ->post('/superadmin/two-factor-challenge', ['code' => $code])->assertRedirect($expected);
        self::assertSame($f['user']->id, Auth::guard('admin')->id());
        self::assertNull(session('url.intended'));
    }

    public function test_authenticated_wrong_guard_cannot_use_any_settings_secret_endpoint(): void
    {
        foreach (['admin', 'path', 'domain'] as $mode) {
            $f = $this->fixture($mode);
            $this->actingAs($f['user'], $mode === 'admin' ? 'tenant' : 'admin');
            foreach ([
                ['postJson', '/two-factor-authentication'],
                ['deleteJson', '/two-factor-authentication'],
                ['postJson', '/confirmed-two-factor-authentication'],
                ['getJson', '/two-factor-qr-code'],
                ['postJson', '/two-factor-recovery-codes'],
            ] as [$method, $endpoint]) {
                $this->{$method}($f['root'].$endpoint, ['code' => '123456'])->assertStatus(401);
            }
            self::assertSame($f['codes'], $f['user']->fresh()->recoveryCodes());
        }
    }

    public static function tenantBoundaryScopes(): array
    {
        return [['path'], ['domain']];
    }

    #[DataProvider('tenantBoundaryScopes')]
    public function test_colliding_admin_and_tenant_ids_cannot_complete_opposite_guard_in_path_or_domain(string $mode): void
    {
        $f = $this->fixture($mode);
        self::assertSame(1, $f['user']->id);
        $adminSecret = app(Google2FA::class)->generateSecretKey();
        $admin = AdminUser::firstOrCreate(['email' => 'guard-collision@example.test'], [
            'name' => 'Guard collision', 'password' => bcrypt('SyntheticReview123!'), 'status' => 'active',
        ]);
        $admin->forceFill(['two_factor_secret' => encrypt($adminSecret), 'two_factor_confirmed_at' => now()])->save();
        self::assertSame(1, $admin->id);
        $code = app(Google2FA::class)->getCurrentOtp($adminSecret);
        $this->withSession(['login.id' => $admin->id, 'login.guard' => 'admin'])
            ->postJson($f['root'].'/two-factor-challenge', ['code' => $code])->assertStatus(403);
        self::assertFalse(Auth::guard('tenant')->check());
        $tenantCode = app(Google2FA::class)->getCurrentOtp($f['secret']);
        $this->withSession($f['pending'])->postJson('http://localhost:8000/superadmin/two-factor-challenge', ['code' => $tenantCode])
            ->assertStatus(403);
        self::assertFalse(Auth::guard('admin')->check());
    }
}
