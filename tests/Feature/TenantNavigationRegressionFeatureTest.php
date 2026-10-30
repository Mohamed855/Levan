<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantNavigationRegressionFeatureTest extends TestCase
{
    public function test_tenant_user_can_navigate_links_without_superadmin_redirect_or_database_empty_error(): void
    {
        // 1. Authenticate via tenant admin login
        $loginResponse = $this->post('/admin/login', [
            'tenant_id' => 'test',
            'email' => 'demo@levan.com',
            'password' => '123456789',
        ]);

        $loginResponse->assertRedirect('/test/dashboard');

        $links = [
            '/test/dashboard',
            '/test/users',
            '/test/roles',
            '/test/properties',
            '/test/assignments',
            '/test/auctions',
            '/test/subscriptions',
            '/test/bills',
            '/test/tickets',
            '/test/settings',
        ];

        foreach ($links as $uri) {
            // Simulate fresh incoming HTTP request by resetting in-memory singletons
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            $this->app['auth']->forgetGuards();
            Config::set('database.connections.tenant.host', null);
            Config::set('database.connections.tenant.database', null);
            Config::set('database.connections.tenant.username', null);
            DB::purge('tenant');

            $response = $this->get($uri);

            // Assert route is accessible with 200 OK and not redirected to superadmin login
            $response->assertOk();
            $this->assertFalse($response->isRedirection());
            $response->assertDontSee('/admin/dashboard');
            $response->assertDontSee('/admin/properties');
            $response->assertSee('/test/dashboard');
        }

        // 2. Visiting login while authenticated must redirect to tenant dashboard, NOT superadmin
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        $this->app['auth']->forgetGuards();
        Config::set('database.connections.tenant.host', null);
        Config::set('database.connections.tenant.database', null);
        Config::set('database.connections.tenant.username', null);
        DB::purge('tenant');

        $revisitLogin = $this->get('/admin/login');
        $revisitLogin->assertRedirect('/test/dashboard');
    }

    public function test_shared_admin_prefix_does_not_expose_dashboard_or_management_routes(): void
    {
        $this->withSession(['tenant_login_context' => 'test']);

        foreach (['dashboard', 'users', 'properties', 'assignments', 'auctions', 'settings'] as $path) {
            $this->get('/admin/'.$path)->assertNotFound();
        }
    }

    public function test_guest_deep_link_is_preserved_after_shared_login(): void
    {
        $this->get('/test/properties?search=example')->assertRedirect('/admin/login');
        $this->assertSame(url('/test/properties?search=example'), session('url.intended'));

        $this->post('/admin/login', [
            'email' => 'demo@levan.com',
            'password' => '123456789',
        ])->assertRedirect(url('/test/properties?search=example'));
    }

    public function test_path_dashboard_rejects_a_session_authenticated_for_another_tenant(): void
    {
        tenancy()->initialize(Tenant::findOrFail('test'));
        $user = User::where('email', 'demo@levan.com')->firstOrFail();
        $connection = $user->getConnection();
        $connection->beginTransaction();

        try {
            $user->forceFill(['remember_token' => 'destination-token'])->save();
            $guard = $this->app['auth']->guard('tenant');

            $this->withSession([
                'auth_tenant_id' => 'another-tenant',
                $guard->getName() => $user->id,
            ])->get('/test/dashboard')->assertNotFound();

            $this->assertSame('destination-token', $user->fresh()->remember_token);
        } finally {
            $connection->rollBack();
            tenancy()->end();
        }
    }

    public function test_tenant_links_use_the_current_central_host_and_tenant_path(): void
    {
        foreach (array_unique(config('tenancy.central_domains')) as $domain) {
            $host = str_replace(['http://', 'https://'], '', $domain);
            $this->get('http://'.$host.'/test/login')->assertRedirect('/admin/login');

            $this->assertNotNull(tenant(), $host);
            $this->assertSame('/test/dashboard', tenant_route_name('dashboard', [], false), $host.' / '.request()->getHost());
            $this->assertSame('/test/users', tenant_route_name('users.index', [], false));
            $this->assertSame('/test/assignments/5/properties', tenant_route_name('assignments.properties', ['assignment' => 5], false));
            $this->assertSame('/admin/login', tenant_route_name('login', [], false));
            $this->assertSame('http://'.$host.'/test/dashboard', tenant_route_name('dashboard'));
        }
    }

    public function test_remembered_login_binds_the_restored_session_to_its_tenant(): void
    {
        tenancy()->initialize(Tenant::findOrFail('test'));
        $user = User::where('email', 'demo@levan.com')->firstOrFail();
        $connection = $user->getConnection();
        $connection->beginTransaction();

        try {
            $user->forceFill(['remember_token' => 'remembered-login-token'])->save();
            $guard = $this->app['auth']->guard('tenant');
            $recaller = implode('|', [$user->id, $user->remember_token, $user->getAuthPassword()]);

            $this->withCookie($guard->getRecallerName(), $recaller)
                ->get('/test/dashboard')->assertOk()->assertSessionHas('auth_tenant_id', 'test');

            $this->assertAuthenticatedAs($user, 'tenant');
        } finally {
            $connection->rollBack();
            tenancy()->end();
        }
    }

    public function test_session_without_an_authenticated_tenant_binding_cannot_access_another_tenant_path(): void
    {
        $this->withSession([
            'tenant_login_context' => 'another-tenant',
            $this->app['auth']->guard('tenant')->getName() => 1,
        ])->get('/test/dashboard')->assertNotFound();
    }

    public function test_custom_domain_login_and_dashboard_links_remain_unprefixed(): void
    {
        $this->get('http://test.localhost/login')->assertOk();

        $this->assertSame('/login', tenant_route_name('login', [], false));
        $this->assertSame('/dashboard', tenant_route_name('dashboard', [], false));
        $this->assertSame('/properties', tenant_route_name('properties.index', [], false));
        $this->assertSame('/dashboard', RouteServiceProvider::home());
    }
}
