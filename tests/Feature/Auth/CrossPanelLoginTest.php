<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use App\Http\Middleware\PreventCrossPanelLogin;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Tenancy;
use Tests\TestCase;

final class CrossPanelLoginTest extends TestCase
{
    public function test_tenant_session_cannot_open_or_submit_superadmin_login(): void
    {
        $user = new User();
        $user->id = 7;
        $this->actingAs($user, 'tenant');
        $this->withSession(['auth_tenant_id' => 'tenant-a']);
        $this->get('http://localhost/superadmin/login')->assertRedirect('/admin/dashboard');
        $this->post('http://localhost/superadmin/login', ['email' => 'other@example.test', 'password' => 'invalid'])
            ->assertRedirect('/admin/dashboard');
        $this->assertSame(7, Auth::guard('tenant')->id());
        $this->assertFalse(Auth::guard('admin')->check());
    }

    public function test_superadmin_session_cannot_open_or_submit_tenant_login_without_a_tenant_hint(): void
    {
        $admin = new AdminUser();
        $admin->id = 9;
        $this->actingAs($admin, 'admin');
        $this->get('http://localhost/admin/login')->assertRedirect(route('admin.dashboard'));
        $this->post('http://localhost/admin/login', ['tenant_id' => 'unknown', 'email' => 'other@example.test', 'password' => 'invalid'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertSame(9, Auth::guard('admin')->id());
        $this->assertFalse(tenancy()->initialized);
    }

    public function test_a_tenant_hint_without_authentication_does_not_block_superadmin_login(): void
    {
        $this->withSession(['tenant_login_context' => 'old-tenant', 'auth_tenant_id' => 'old-tenant']);
        $this->get('http://localhost/superadmin/login')->assertOk();
        $this->assertFalse(tenancy()->initialized);
    }

    public function test_guest_can_access_superadmin_login(): void
    {
        $this->get('http://localhost/superadmin/login')->assertOk();
    }

    public function test_persisted_tenant_session_is_validated_in_its_database_and_restores_context(): void
    {
        $request = $this->persistedTenantRequest();
        $response = app(PreventCrossPanelLogin::class)->handle($request, fn () => response('guest'));
        $this->assertSame('http://localhost/admin/dashboard', $response->headers->get('Location'));
        $this->assertSame(7, Auth::guard('tenant')->id());
        $this->assertFalse(tenancy()->initialized);
        $this->assertNull(tenant());
    }

    public function test_deleted_tenant_user_does_not_block_superadmin_login(): void
    {
        $request = $this->persistedTenantRequest();
        DB::connection('tenant')->table('users')->where('id', 7)->delete();
        $response = app(PreventCrossPanelLogin::class)->handle($request, fn () => response('guest'));
        $this->assertSame('guest', $response->getContent());
        $this->assertFalse(tenancy()->initialized);
    }

    private function persistedTenantRequest(): Request
    {
        foreach (['mysql', 'tenant'] as $connection) {
            config(["database.connections.$connection" => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
            DB::purge($connection);
        }
        Schema::connection('mysql')->create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->text('data')->nullable();
            $table->softDeletes();
        });
        Schema::connection('tenant')->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email');
            $table->softDeletes();
        });
        DB::connection('mysql')->table('tenants')->insert(['id' => 'tenant-a']);
        DB::connection('tenant')->table('users')->insert(['id' => 7, 'email' => 'member@example.test']);
        // Avoid provisioning databases/filesystems; authentication still uses the real Eloquent provider.
        $this->app->instance(Tenancy::class, new class extends Tenancy {
            public function initialize($tenant): void
            {
                $this->tenant = $tenant;
                $this->initialized = true;
            }
            public function end(): void
            {
                $this->tenant = null;
                $this->initialized = false;
            }
        });
        $request = Request::create('http://localhost/superadmin/login');
        $route = Route::getRoutes()->getByName('admin.login');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $request->setLaravelSession(app('session.store'));
        $this->app->instance('request', $request);
        $request->session()->put(['auth_tenant_id' => 'tenant-a', Auth::guard('tenant')->getName() => 7]);
        return $request;
    }
}
