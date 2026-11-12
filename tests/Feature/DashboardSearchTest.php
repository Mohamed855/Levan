<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Shared\Navigation\DashboardSearchService;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DashboardSearchTest extends TestCase
{
    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $this->admin = AdminUser::first() ?? AdminUser::factory()->create();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_dashboard_search_service_returns_admin_pages_for_superadmin(): void
    {
        Auth::guard('admin')->setUser($this->admin);

        $service = app(DashboardSearchService::class);
        $pages = $service->getSearchablePages('admin');

        $this->assertNotEmpty($pages);

        $pageNames = array_column($pages, 'name');
        $this->assertContains('لوحة القيادة', $pageNames);
        $this->assertContains('إدارة العملاء والشركات', $pageNames);
        $this->assertContains('إدارة الباقات', $pageNames);
        $this->assertContains('إدارة المستخدمين', $pageNames);

        foreach ($pages as $page) {
            $this->assertArrayHasKey('name', $page);
            $this->assertArrayHasKey('url', $page);
            $this->assertArrayHasKey('icon', $page);
            $this->assertArrayHasKey('category', $page);
            $this->assertArrayHasKey('keywords', $page);
            $this->assertNotEmpty($page['url']);
            $this->assertNotEquals('#', $page['url']);
        }
    }

    public function test_dashboard_search_service_returns_tenant_pages(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) {
            $this->markTestSkipped('No tenant available for testing.');
        }

        tenancy()->initialize($tenant);

        $tenantUser = User::first();
        if (! $tenantUser) {
            $this->markTestSkipped('No tenant user available for testing.');
        }

        Auth::guard('tenant')->setUser($tenantUser);

        $service = app(DashboardSearchService::class);
        $pages = $service->getSearchablePages('tenant');

        $this->assertNotEmpty($pages);

        $pageNames = array_column($pages, 'name');
        $this->assertContains('لوحة القيادة', $pageNames);

        foreach ($pages as $page) {
            $this->assertArrayHasKey('name', $page);
            $this->assertArrayHasKey('url', $page);
            $this->assertArrayHasKey('icon', $page);
            $this->assertArrayHasKey('category', $page);
            $this->assertArrayHasKey('keywords', $page);
            $this->assertNotEmpty($page['url']);
            $this->assertNotEquals('#', $page['url']);
        }
    }

    public function test_admin_quick_search_endpoint_returns_json_pages(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.quick-search'));

        $response->assertOk()
            ->assertJsonStructure([
                'pages' => [
                    '*' => ['name', 'url', 'icon', 'category', 'keywords'],
                ],
            ]);
    }

    public function test_tenant_quick_search_endpoint_returns_json_pages(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) {
            $this->markTestSkipped('No tenant available for testing.');
        }

        tenancy()->initialize($tenant);

        $tenantUser = User::first();
        if (! $tenantUser) {
            $this->markTestSkipped('No tenant user available for testing.');
        }

        $url = tenant_route_name('quick-search');
        $this->assertNotEmpty($url);
        $this->assertNotEquals('#', $url);

        $response = $this->actingAs($tenantUser, 'tenant')->get($url);
        $response->assertOk()
            ->assertJsonStructure([
                'pages' => [
                    '*' => ['name', 'url', 'icon', 'category', 'keywords'],
                ],
            ]);
    }

    public function test_dashboard_search_view_renders_for_admin(): void
    {
        Auth::guard('admin')->setUser($this->admin);

        $view = $this->view('shared.dashboard-search', [
            'scope' => 'admin',
            'containerNav' => 'container-xxl',
            'menuHorizontal' => null,
        ]);

        $view->assertSee('window.dashboardSearchPages =', false);
        $view->assertSee('لوحة القيادة', false);
        $view->assertSee('search-input', false);
    }

    public function test_dashboard_search_view_renders_for_tenant(): void
    {
        $tenant = Tenant::first();
        if (! $tenant) {
            $this->markTestSkipped('No tenant available for testing.');
        }

        tenancy()->initialize($tenant);

        $tenantUser = User::first();
        if (! $tenantUser) {
            $this->markTestSkipped('No tenant user available for testing.');
        }

        Auth::guard('tenant')->setUser($tenantUser);

        $view = $this->view('shared.dashboard-search', [
            'scope' => 'tenant',
            'containerNav' => 'container-xxl',
            'menuHorizontal' => null,
        ]);

        $view->assertSee('window.dashboardSearchPages =', false);
        $view->assertSee('لوحة القيادة', false);
        $view->assertSee('search-input', false);
    }

    public function test_unauthenticated_request_returns_empty(): void
    {
        Auth::guard('admin')->logout();
        Auth::guard('tenant')->logout();

        $service = app(DashboardSearchService::class);
        $pages = $service->getSearchablePages('admin');

        $this->assertEmpty($pages);
    }
}
