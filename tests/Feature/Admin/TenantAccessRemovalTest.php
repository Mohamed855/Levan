<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\DataTables\Admin\Clients\ClientsDataTable;
use App\DataTables\Admin\Subscriptions\SubscriptionDataTable;
use App\DataTables\Admin\SubscriptionMangment\subscriptionMangmentDataTable;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

final class TenantAccessRemovalTest extends TestCase
{
    public function test_no_tenant_impersonation_route_is_registered(): void
    {
        foreach (Route::getRoutes() as $route) {
            $this->assertStringNotContainsString('impersonate', $route->getActionName());
        }
    }

    public function test_client_actions_offer_the_tenant_dashboard_shortcut(): void
    {
        $html = view('admin.clients.action', ['row' => (object) ['id' => 'tenant-a']])->render();
        $this->assertStringContainsString('/tenant-a/dashboard', $html);
        $this->assertStringContainsString('/superadmin/clients/tenant-a', $html);
    }

    public function test_regular_tenant_login_and_welcome_email_link_remain_available(): void
    {
        foreach (['tenant.login', 'path.login', 'domain.login'] as $name) {
            $this->assertNotNull(Route::getRoutes()->getByName($name));
        }

        $tenant = new Tenant();
        $tenant->id = 'tenant-a';
        $this->assertStringContainsString('/tenant-a/login', $tenant->getLoginUrl());
    }

    public function test_admin_tables_link_tenant_identifiers_to_their_dashboard(): void
    {
        $tenant = new Tenant();
        $tenant->id = 'tenant-a';
        $subscription = new Subscription(['tenant_id' => 'tenant-a']);
        $tables = [
            ClientsDataTable::class => $tenant,
            SubscriptionDataTable::class => $subscription,
            subscriptionMangmentDataTable::class => $subscription,
        ];

        foreach ($tables as $class => $row) {
            $columns = new class {
                public array $renderers = [];

                public function __call(string $method, array $arguments): self
                {
                    if (in_array($method, ['addColumn', 'editColumn'], true)) {
                        $this->renderers[$arguments[0]] = $arguments[1];
                    }

                    return $this;
                }
            };

            (new ReflectionMethod($class, 'addCustomColumns'))->invoke(app($class), $columns);
            $html = ($columns->renderers['tenant_id'])($row);
            $this->assertStringContainsString('tenant-a', $html);
            $this->assertStringContainsString('<a', $html, $class);
            $this->assertStringContainsString('/tenant-a/dashboard', $html, $class);
        }
    }
}
