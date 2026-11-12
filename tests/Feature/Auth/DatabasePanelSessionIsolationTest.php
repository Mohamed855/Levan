<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Http\Middleware\Tenancy\SingleSessionPerUser;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Shared\Payment\Moyasar\MoyasarCallbackHandler;
use App\Services\Shared\Subscription\SubscriptionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
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
            Schema::connection($connection)->create('permissions', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
            });
            Schema::connection($connection)->create('roles', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
            });
            Schema::connection($connection)->create('model_has_roles', function (Blueprint $table): void {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
            });
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
        Route::middleware(['web', 'tenant.path', 'tenant.session', 'auth:tenant'])
            ->get('/{tenant}/__navbar-probe', fn () => view('tenant.layouts.sections.navbar.navbar', ['configData' => []]))
            ->name('path.navbar-probe');
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

    public function test_database_session_user_ids_follow_the_correct_guard_when_services_are_reused(): void
    {
        $this->user->forceFill(['id' => 7])->save();
        $this->reuseRequestServices = true;
        $this->loginAdmin();
        $admin = $this->browser('GET', '/superadmin/__session-probe')->assertOk()->json();
        $this->loginTenant();
        $tenant = $this->browser('GET', '/session-tenant/__session-probe')->assertOk()->json();

        $this->assertSame($this->admin->id, (int) DB::connection('mysql')->table('sessions')->where('id', $admin['session_id'])->value('user_id'));
        $this->assertSame(7, (int) DB::connection('mysql')->table('sessions')->where('id', $tenant['session_id'])->value('user_id'));
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

    public function test_tenant_login_redirects_to_the_shared_form_with_its_hidden_tenant_id(): void
    {
        $this->browser('GET', '/session-tenant/login')->assertRedirect('/admin/login');
        $this->browser('GET', '/admin/login')->assertOk()
            ->assertSee('action="http://localhost/admin/login"', false)
            ->assertSee('name="tenant_id" value="session-tenant"', false);
    }

    public function test_opening_another_tenant_entry_always_replaces_the_previous_login_context(): void
    {
        Tenant::create(['id' => 'second-tenant']);

        foreach (['session-tenant', 'second-tenant', 'session-tenant'] as $tenantId) {
            $this->browser('GET', '/'.$tenantId.'/login')->assertRedirect('/admin/login');
            $this->browser('GET', '/admin/login')->assertOk()
                ->assertSee('name="tenant_id" value="'.$tenantId.'"', false)
                ->assertSessionHas('tenant_login_context', $tenantId);
        }
    }

    public function test_a_submitted_tenant_id_wins_over_the_context_from_another_open_tab(): void
    {
        Tenant::create(['id' => 'second-tenant']);
        $this->browser('GET', '/second-tenant/login');

        $this->browser('POST', '/admin/login', [
            'tenant_id' => 'session-tenant',
            'email' => $this->user->email,
            'password' => 'Password123!',
        ])->assertRedirect('/session-tenant/dashboard')
            ->assertSessionHas('auth_tenant_id', 'session-tenant');
    }

    public function test_switching_tenants_discards_previous_authentication_and_pending_challenges(): void
    {
        Tenant::create(['id' => 'second-tenant']);
        $this->loginAdmin();
        $this->loginTenant();

        $this->browser('GET', '/second-tenant/login')->assertRedirect('/admin/login');
        $this->browser('GET', '/admin/login')->assertOk()
            ->assertSee('name="tenant_id" value="second-tenant"', false)
            ->assertSessionMissing('auth_tenant_id')
            ->assertSessionMissing('login.id');
        $this->browser('GET', '/superadmin/__session-probe')->assertOk();
    }

    public function test_all_tenant_auth_entry_pages_redirect_to_the_shared_prefix(): void
    {
        foreach (['register', 'forgot-password', 'forgot-password/edit', 'reset-password', 'two-factor-challenge', 'two-factor-challenge/cancel', 'verify-email', 'confirm-password', 'two-factor-authentication'] as $page) {
            $this->browser('GET', '/session-tenant/'.$page)->assertRedirect('/admin/'.$page);
        }
    }

    public function test_shared_guest_auth_forms_include_the_resolved_tenant(): void
    {
        $this->browser('GET', '/session-tenant/login');

        foreach (['login', 'register', 'forgot-password'] as $page) {
            $this->browser('GET', '/admin/'.$page)->assertOk()
                ->assertSee('name="tenant_id" value="session-tenant"', false)
                ->assertSee('http://localhost/admin/', false)
                ->assertDontSee('http://localhost/session-tenant/'.$page, false);
        }
    }

    public function test_shared_auth_rejects_invalid_explicit_tenants_instead_of_using_an_old_context(): void
    {
        $this->browser('GET', '/session-tenant/login');
        $this->browser('GET', '/admin/login?tenant_id=unknown-tenant')->assertNotFound();
        $this->browser('POST', '/admin/login', ['tenant_id' => ['session-tenant']])->assertNotFound();
        $this->browser('GET', '/session-tenant/login');
        $this->browser('POST', '/admin/login', ['tenant_id' => ''])->assertNotFound();
    }

    public function test_legacy_auth_redirects_preserve_query_parameters(): void
    {
        $this->browser('GET', '/session-tenant/reset-password?email=tenant%40example.test')
            ->assertRedirect('/admin/reset-password?email=tenant%40example.test');
    }

    public function test_the_path_tenant_wins_over_a_conflicting_query_tenant(): void
    {
        Tenant::create(['id' => 'second-tenant']);
        $this->browser('GET', '/second-tenant/login?tenant_id=session-tenant')->assertRedirect('/admin/login');
        $this->browser('GET', '/admin/login')->assertOk()
            ->assertSee('name="tenant_id" value="second-tenant"', false);
    }

    public function test_login_links_with_explicit_tenant_selection_work_in_fresh_and_different_browser_contexts(): void
    {
        Tenant::create(['id' => 'second-tenant']);
        $this->browser('GET', '/admin/login?tenant_id=session-tenant')->assertOk()
            ->assertSee('name="tenant_id" value="session-tenant"', false);
        $this->browser('GET', '/second-tenant/login')->assertRedirect('/admin/login');
        $this->browser('GET', '/admin/login?tenant_id=session-tenant')->assertOk()
            ->assertSee('name="tenant_id" value="session-tenant"', false)
            ->assertSessionHas('tenant_login_context', 'session-tenant');
    }

    public function test_shared_login_can_select_another_tenant_while_a_previous_tenant_is_authenticated(): void
    {
        Tenant::create(['id' => 'second-tenant']);
        $this->loginTenant();

        $this->browser('POST', '/admin/login', [
            'tenant_id' => 'second-tenant', 'email' => $this->user->email, 'password' => 'Password123!',
        ])->assertRedirect('/second-tenant/dashboard')->assertSessionHas('auth_tenant_id', 'second-tenant');
    }

    public function test_shared_authenticated_forms_cannot_select_a_different_tenant(): void
    {
        Tenant::create(['id' => 'second-tenant']);
        $this->loginTenant();

        $this->browser('POST', '/admin/confirm-password', [
            'tenant_id' => 'second-tenant', 'password' => 'Password123!',
        ])->assertNotFound()->assertSessionMissing('auth.password_confirmed_at');
    }

    public function test_shared_password_reset_forms_and_otp_complete_in_the_selected_tenant(): void
    {
        Mail::fake();
        config(['services.moyasar.mode' => 'test']);
        $this->browser('GET', '/session-tenant/forgot-password')->assertRedirect('/admin/forgot-password');
        $this->browser('POST', '/admin/forgot-password', [
            'tenant_id' => 'session-tenant', 'email' => $this->user->email,
        ])->assertRedirect('http://localhost/admin/forgot-password');
        $this->browser('GET', '/admin/forgot-password')->assertOk()
            ->assertSee('name="tenant_id" value="session-tenant"', false)
            ->assertSee('http://localhost/admin/forgot-password/verify', false)
            ->assertSee('http://localhost/admin/forgot-password/resend-otp', false);
        $this->browser('POST', '/admin/forgot-password/verify', [
            'tenant_id' => 'session-tenant', 'otp' => '000000',
        ])->assertRedirect('http://localhost/admin/reset-password');
        $this->browser('GET', '/admin/reset-password')->assertOk()
            ->assertSee('name="tenant_id" value="session-tenant"', false);
        $this->browser('POST', '/admin/reset-password', [
            'tenant_id' => 'session-tenant', 'email' => $this->user->email,
            'password' => 'NewPassword456!', 'password_confirmation' => 'NewPassword456!',
        ])->assertRedirect('http://localhost/admin/login');
        $this->assertTrue(Hash::check('NewPassword456!', $this->user->fresh()->password));
    }

    public function test_switching_tenants_clears_the_previous_password_reset_grant(): void
    {
        Tenant::create(['id' => 'second-tenant']);
        Mail::fake();
        $this->browser('POST', '/admin/forgot-password', [
            'tenant_id' => 'session-tenant', 'email' => $this->user->email,
        ])->assertRedirect()->assertSessionHas('password_reset_verification');
        $this->browser('GET', '/second-tenant/login')->assertRedirect('/admin/login')
            ->assertSessionMissing('password_reset_verification');
    }

    public function test_shared_password_confirmation_uses_the_authenticated_tenant(): void
    {
        $this->loginTenant();
        $this->browser('GET', '/admin/confirm-password')->assertOk()
            ->assertSee('name="tenant_id" value="session-tenant"', false)
            ->assertSee('http://localhost/admin/confirm-password/validate', false);
        $this->browser('POST', '/admin/confirm-password/validate', [
            'tenant_id' => 'session-tenant', 'target_path' => '/admin/confirm-password',
            'target_method' => 'POST', 'fields' => ['password'], 'password' => '',
        ], ['HTTP_ACCEPT' => 'application/json'])->assertUnprocessable()->assertJsonStructure(['errors' => ['password']]);
        $this->browser('POST', '/admin/confirm-password', [
            'tenant_id' => 'session-tenant', 'password' => 'Password123!',
        ])->assertRedirect('/session-tenant/dashboard')->assertSessionHas('auth.password_confirmed_at');
    }

    public function test_shared_authenticated_auth_pages_render_with_their_hidden_tenant_context(): void
    {
        Gate::before(static fn (): bool => true);
        $this->loginTenant();
        foreach (['verify-email', 'confirm-password', 'two-factor-authentication'] as $page) {
            $this->browser('GET', '/admin/'.$page)->assertOk()
                ->assertSee('name="tenant_id" value="session-tenant"', false);
        }
    }

    public function test_navbar_logout_posts_its_own_tenant_to_the_shared_endpoint(): void
    {
        Gate::before(static fn (): bool => true);
        $this->loginTenant();
        $content = $this->browser('GET', '/session-tenant/__navbar-probe')->assertOk()->getContent();
        $this->assertMatchesRegularExpression(
            '/<form\b[^>]*id="tenant-logout-form"[^>]*action="http:\/\/localhost\/admin\/logout"[^>]*>\s*<input[^>]+name="_token"[^>]*>\s*<input[^>]+name="tenant_id"[^>]+value="session-tenant"/s',
            $content,
        );
    }

    public function test_signed_verification_redirects_remain_signed_and_bound_to_the_path_tenant(): void
    {
        $this->browser('GET', '/session-tenant/login');
        $signed = URL::temporarySignedRoute('path.verification.verify', now()->addMinutes(5), [
            'tenant' => 'session-tenant', 'id' => 1, 'hash' => sha1($this->user->email),
        ]);
        $uri = parse_url($signed, PHP_URL_PATH).'?'.parse_url($signed, PHP_URL_QUERY);
        $response = $this->browser('GET', $uri)->assertRedirect();
        $destination = $response->headers->get('Location');
        $this->assertStringContainsString('/admin/verify-email/1/', $destination);
        $this->assertStringContainsString('tenant_id=session-tenant', $destination);
        $this->assertTrue(URL::hasValidSignature(Request::create($destination)));
        $this->browser('GET', $uri.'&tampered=1')->assertForbidden();
    }

    public function test_login_links_and_forms_keep_the_current_central_host(): void
    {
        foreach (array_unique(config('tenancy.central_domains')) as $domain) {
            $host = str_replace(['http://', 'https://'], '', $domain);

            $this->get('http://'.$host.'/session-tenant/login')->assertRedirect('/admin/login');
            $this->get('http://'.$host.'/admin/login')->assertOk()
                ->assertSee('action="http://'.$host.'/admin/login"', false);
            $this->assertSame('/admin/login', tenant_route_name('login', [], false));
        }
    }

    public function test_login_without_a_known_tenant_does_not_render_a_shared_form(): void
    {
        $this->browser('GET', '/admin/login')->assertNotFound();
        $this->browser('GET', '/unknown-tenant/login')->assertNotFound();
    }

    public function test_completed_checkout_returns_to_the_created_tenant_login(): void
    {
        $bill = new Bill;
        $payment = ['id' => 'payment-test', 'status' => 'paid'];

        $this->mock(MoyasarCallbackHandler::class, function (MockInterface $mock) use ($bill, $payment): void {
            $mock->shouldReceive('verify')->once()->andReturn(['bill' => $bill, 'payment' => $payment]);
        });
        $this->mock(SubscriptionService::class, function (MockInterface $mock) use ($bill, $payment): void {
            $mock->shouldReceive('completeOnlineSubscription')->once()->with($bill, $payment)
                ->andReturn(['tenant_id' => 'session-tenant']);
        });

        $this->browser('GET', route('site.plans.checkout.callback', [], false))
            ->assertRedirect('/session-tenant/login')
            ->assertSessionHas('tenant_login_context', 'session-tenant')
            ->assertSessionHas('success');
        $this->browser('GET', '/session-tenant/login')->assertRedirect('/admin/login')->assertSessionHas('success');
        $this->browser('GET', '/admin/login')->assertOk();
    }

    public function test_tenant_logout_returns_to_the_tenant_login(): void
    {
        $this->loginTenant();

        $this->browser('POST', '/session-tenant/logout')->assertRedirect('/admin/login');
    }

    public function test_a_replaced_tenant_session_returns_to_the_tenant_login(): void
    {
        $this->loginTenant();
        $this->user->update(['current_session_id' => 'another-session']);

        $this->browser('GET', '/session-tenant/__session-probe')->assertRedirect('/admin/login');
    }

    public function test_canceling_a_tenant_two_factor_challenge_returns_to_its_login(): void
    {
        $this->browser('GET', '/session-tenant/two-factor-challenge/cancel')->assertRedirect('/admin/two-factor-challenge/cancel');
        $this->browser('GET', '/admin/two-factor-challenge/cancel')->assertRedirect('http://localhost/admin/login');
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
            $this->assertStringContainsString('/admin/two-factor-challenge', (string) $response->headers->get('Location'));
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
