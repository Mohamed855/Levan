<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Middleware\Tenancy\SingleSessionPerUser;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class EnforcePanelCsrfToken extends ValidateCsrfToken
{
    protected function runningUnitTests(): bool
    {
        return false;
    }
}

final class DatabasePanelSessionIsolationTest extends TestCase
{
    private array $browserCookies = [];

    private bool $reuseRequestServices = false;

    private AdminUser $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'tenancy.bootstrappers' => [],
            'tenancy.database.central_connection' => 'mysql',
            'session.driver' => 'database',
            'session.connection' => 'mysql',
            'session.cookie' => 'panel_test_session',
            'session.lottery' => [0, 100],
            'session.secure' => false,
            'session.domain' => null,
            'activitylog.enabled' => false,
        ]);
        DB::purge('mysql');
        DB::purge('tenant');
        $this->withoutVite();

        foreach (['mysql' => 'admin_users', 'tenant' => 'users'] as $connection => $name) {
            Schema::connection($connection)->create($name, function (Blueprint $table): void {
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
                $table->softDeletes();
                $table->timestamps();
            });
            Schema::connection($connection)->create('settings', function (Blueprint $table): void {
                $table->id();
                $table->boolean('maintenance_mode')->default(false);
                $table->boolean('force_two_factor_auth')->default(false);
            });
        }
        Schema::connection('mysql')->create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->json('data')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::connection('mysql')->create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity');
        });

        Tenant::create(['id' => 'session-tenant']);
        $this->admin = AdminUser::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'Password123!', 'status' => 'active', 'is_super_admin' => true]);
        $this->user = User::create(['name' => 'Tenant', 'email' => 'tenant@example.test', 'password' => Hash::make('Password123!'), 'status' => 'active']);

        $probe = static fn (string $guard) => response()->json([
            'user_id' => Auth::guard($guard)->id(),
            'session_id' => session()->getId(),
            'session_cookie' => session()->getName(),
            'csrf_token' => session()->token(),
            'pending_guard' => session('login.guard'),
        ]);
        Route::middleware(['web', 'auth:admin'])->get('/superadmin/__session-probe', fn () => $probe('admin'))->name('admin.session-probe');
        Route::middleware(['web', 'tenant.path', 'tenant.session', 'auth:tenant', SingleSessionPerUser::class])
            ->get('/{tenant}/__session-probe', fn () => $probe('tenant'))->name('path.session-probe');
        Route::middleware('web')->get('/superadmin/__pending-probe', fn () => $probe('admin'))->name('admin.pending-probe');
        Route::middleware(['web', 'tenant.path', 'tenant.session'])->get('/{tenant}/__pending-probe', fn () => $probe('tenant'))->name('path.pending-probe');
        Route::middleware(['web', 'auth:admin', EnforcePanelCsrfToken::class])->post('/superadmin/__csrf-probe', fn () => response()->json(['ok' => true]))->name('admin.csrf-probe');
        Route::middleware(['web', 'tenant.path', 'tenant.session', 'auth:tenant', EnforcePanelCsrfToken::class])
            ->post('/{tenant}/__csrf-probe', fn () => response()->json(['ok' => true]))->name('path.csrf-probe');
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        DB::purge('tenant');
        DB::purge('mysql');
        parent::tearDown();
    }

    public function test_both_panels_have_different_database_sessions_and_csrf_tokens_in_one_browser(): void
    {
        $this->loginAdmin();
        $this->loginTenant();
        $admin = $this->browser('GET', '/superadmin/__session-probe')->assertOk()->json();
        $tenant = $this->browser('GET', '/session-tenant/__session-probe')->assertOk()->json();

        $this->assertNotSame($admin['session_cookie'], $tenant['session_cookie']);
        $this->assertNotSame($admin['session_id'], $tenant['session_id']);
        $this->assertNotSame($admin['csrf_token'], $tenant['csrf_token']);
        $this->assertSame(2, DB::connection('mysql')->table('sessions')->whereIn('id', [$admin['session_id'], $tenant['session_id']])->count());
    }

    public function test_admin_logout_does_not_log_the_tenant_out(): void
    {
        $this->loginAdmin();
        $this->loginTenant();
        $before = $this->browser('GET', '/session-tenant/__session-probe')->assertOk()->json();

        $this->browser('POST', '/superadmin/logout')->assertRedirect();
        $after = $this->browser('GET', '/session-tenant/__session-probe')->assertOk()->json();

        $this->assertSame($before['session_id'], $after['session_id']);
        $this->assertSame($before['csrf_token'], $after['csrf_token']);
        $this->browser('GET', '/superadmin/__session-probe')->assertRedirect('/superadmin/login');
    }

    public function test_tenant_logout_does_not_log_the_admin_out(): void
    {
        $this->loginAdmin();
        $this->loginTenant();
        $before = $this->browser('GET', '/superadmin/__session-probe')->assertOk()->json();

        $this->browser('POST', '/session-tenant/logout')->assertRedirect();
        $after = $this->browser('GET', '/superadmin/__session-probe')->assertOk()->json();

        $this->assertSame($before['session_id'], $after['session_id']);
        $this->assertSame($before['csrf_token'], $after['csrf_token']);
    }

    public function test_admin_login_does_not_invalidate_the_tenant_single_session_binding(): void
    {
        $this->loginTenant();
        $before = $this->browser('GET', '/session-tenant/__session-probe')->assertOk()->json();

        $this->loginAdmin();
        $after = $this->browser('GET', '/session-tenant/__session-probe')->assertOk()->json();

        $this->assertSame($before['session_id'], $after['session_id']);
        $this->assertSame($before['session_id'], $this->user->fresh()->current_session_id);
    }

    public function test_two_factor_challenges_can_be_pending_in_both_panels(): void
    {
        foreach ([$this->admin, $this->user] as $user) {
            $user->forceFill(['two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_confirmed_at' => now()])->save();
        }
        $this->loginAdmin(true);
        $this->loginTenant(true);

        $this->browser('GET', '/superadmin/__pending-probe')->assertOk()->assertJsonPath('pending_guard', 'admin');
        $this->browser('GET', '/session-tenant/__pending-probe')->assertOk()->assertJsonPath('pending_guard', 'tenant');
    }

    public function test_loading_another_panel_does_not_break_csrf_in_an_already_open_tab(): void
    {
        $this->loginAdmin();
        $adminToken = $this->browser('GET', '/superadmin/__session-probe')->assertOk()->json('csrf_token');
        $this->loginTenant();
        $tenantToken = $this->browser('GET', '/session-tenant/__session-probe')->assertOk()->json('csrf_token');

        $this->browser('POST', '/superadmin/__csrf-probe', [], [
            'HTTP_X_CSRF_TOKEN' => $adminToken,
            'HTTP_X_XSRF_TOKEN' => $this->browserCookies['XSRF-TOKEN'],
        ])->assertOk();
        $this->browser('POST', '/session-tenant/__csrf-probe', [], ['HTTP_X_CSRF_TOKEN' => $tenantToken])->assertOk();
        $this->browser('POST', '/superadmin/__csrf-probe', [], ['HTTP_X_CSRF_TOKEN' => $tenantToken])->assertStatus(419);
    }

    public function test_cached_request_services_do_not_reuse_another_panels_session_or_guard(): void
    {
        $this->reuseRequestServices = true;
        $this->loginAdmin();
        $this->loginTenant();
        $admin = $this->browser('GET', '/superadmin/__session-probe')->assertOk()->json();
        $tenant = $this->browser('GET', '/session-tenant/__session-probe')->assertOk()->json();

        $this->assertNotSame($admin['session_id'], $tenant['session_id']);
        $adminData = unserialize(base64_decode(DB::connection('mysql')->table('sessions')->where('id', $admin['session_id'])->value('payload')), ['allowed_classes' => false]);
        $tenantData = unserialize(base64_decode(DB::connection('mysql')->table('sessions')->where('id', $tenant['session_id'])->value('payload')), ['allowed_classes' => false]);
        $this->assertArrayNotHasKey('auth_tenant_id', $adminData);
        $this->assertSame('session-tenant', $tenantData['auth_tenant_id']);
        $this->assertArrayNotHasKey(Auth::guard('admin')->getName(), $tenantData);
    }

    public function test_admin_two_factor_completion_does_not_destroy_a_pending_tenant_challenge(): void
    {
        foreach ([$this->admin, $this->user] as $user) {
            $user->forceFill([
                'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_confirmed_at' => now(),
                'two_factor_recovery_codes' => encrypt(json_encode(['recovery-'.$user->email])),
            ])->save();
        }
        $this->loginAdmin(true);
        $this->loginTenant(true);

        $this->browser('POST', '/superadmin/two-factor-challenge', ['recovery_code' => 'recovery-'.$this->admin->email])->assertRedirect('/superadmin');
        $this->browser('GET', '/session-tenant/__pending-probe')->assertOk()->assertJsonPath('pending_guard', 'tenant');
        $this->browser('POST', '/session-tenant/two-factor-challenge', ['recovery_code' => 'recovery-'.$this->user->email])->assertRedirect('/session-tenant/dashboard');
        $this->browser('GET', '/superadmin/__session-probe')->assertOk();
        $this->browser('GET', '/session-tenant/__session-probe')->assertOk();
    }

    public function test_legacy_shared_session_cookie_cannot_authenticate_either_new_panel(): void
    {
        $this->loginAdmin();
        $adminCookie = (string) config('session.panel_cookies.admin');
        $this->browserCookies['panel_test_session'] = $this->browserCookies[$adminCookie];
        unset($this->browserCookies[$adminCookie]);

        $this->browser('GET', '/superadmin/__session-probe')->assertRedirect('/superadmin/login');
        $this->browser('GET', '/session-tenant/__session-probe')->assertRedirect('/admin/login');
    }

    private function loginAdmin(bool $twoFactor = false): void
    {
        $this->browser('POST', '/superadmin/login', ['email' => $this->admin->email, 'password' => 'Password123!'])
            ->assertRedirect($twoFactor ? '/superadmin/two-factor-challenge' : '/superadmin');
    }

    private function loginTenant(bool $twoFactor = false): void
    {
        $response = $this->browser('POST', '/admin/login', ['tenant_id' => 'session-tenant', 'email' => $this->user->email, 'password' => 'Password123!']);
        if ($twoFactor) {
            $this->assertStringContainsString('/session-tenant/two-factor-challenge', (string) $response->headers->get('Location'));
        } else {
            $response->assertRedirect('/session-tenant/dashboard');
        }
    }

    private function browser(string $method, string $uri, array $data = [], array $server = []): TestResponse
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        DB::setDefaultConnection('mysql');
        if (! $this->reuseRequestServices) {
            Auth::forgetGuards();
            $this->app['session']->forgetDrivers();
            $this->app->forgetInstance('session.store');
        }
        app('cookie')->flushQueuedCookies();
        $response = $this->call($method, 'http://localhost'.$uri, $data, $this->browserCookies, [], $server);
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->isCleared()) {
                unset($this->browserCookies[$cookie->getName()]);
            } else {
                $this->browserCookies[$cookie->getName()] = $cookie->getValue();
            }
        }

        return $response;
    }
}
