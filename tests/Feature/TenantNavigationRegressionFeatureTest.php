<?php

declare(strict_types=1);

namespace Tests\Feature;

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

        $loginResponse->assertRedirect('/admin/dashboard');

        $links = [
            '/admin/dashboard',
            '/admin/users',
            '/admin/roles',
            '/admin/properties',
            '/admin/assignments',
            '/admin/auctions',
            '/admin/subscriptions',
            '/admin/bills',
            '/admin/tickets',
            '/admin/settings',
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
        $revisitLogin->assertRedirect('/admin/dashboard');
    }
}

