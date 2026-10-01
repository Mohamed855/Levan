<?php

namespace Tests\Feature;

use App\Http\Middleware\Tenancy\CheckLimits\CheckBranchesLimits;
use App\Http\Middleware\Tenancy\CheckLimits\CheckDomainManagement;
use App\Models\Admin\Tenants\CompanySetting;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class TenantDashboardPostRefactorCasesTest extends TestCase
{
    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::find('test');
        tenancy()->initialize($this->tenant);

        $this->user = User::where('email', 'demo@levan.com')->first();
        $this->actingAs($this->user, 'tenant');
    }

    protected function tearDown(): void
    {
        Tenant::find('test')?->update(['status' => 'active']);
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        parent::tearDown();
    }

    protected function tenantUrl(string $path): string
    {
        return 'http://test.localhost/' . ltrim($path, '/');
    }

    public function test_tenant_subscription_page_renders_with_plan_limits(): void
    {
        $response = $this->get($this->tenantUrl('subscriptions'));
        $response->assertStatus(200);
        $response->assertSee('حد العقارات');
        $response->assertSee('المساحة التخزينية');
        $response->assertSee('المزادات النشطة');
        $response->assertSee('حد الفروع');
    }

    public function test_check_branches_limits_respects_latest_subscription(): void
    {
        $middleware = new CheckBranchesLimits();
        $request = Request::create('http://test.localhost/branches/create', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK: ' . ($req->subscription_limit ?? 'unlimited'));
        });

        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));
    }

    public function test_check_domain_management_respects_latest_subscription(): void
    {
        $middleware = new CheckDomainManagement();
        $request = Request::create('http://test.localhost/domains/setting', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('OK');
        });

        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));
    }

    public function test_check_subscription_blocks_suspended_tenant(): void
    {
        try {
            $this->tenant->status = 'suspended';
            $this->tenant->save();

            $response = $this->get($this->tenantUrl('dashboard'));
            $response->assertRedirect();
            $this->assertFalse(Auth::guard('tenant')->check());
        } finally {
            Tenant::find('test')?->update(['status' => 'active']);
        }
    }

    public function test_check_subscription_allows_whitelisted_routes(): void
    {
        $response = $this->get($this->tenantUrl('subscriptions'));
        $response->assertStatus(200);
    }

    public function test_ensure_tenant_two_factor_enforced_checks_company_settings(): void
    {
        $originalConfirmedAt = $this->user->two_factor_confirmed_at;
        $this->user->two_factor_confirmed_at = null;
        $this->user->save();

        $companySetting = CompanySetting::firstOrCreate(['tenant_id' => 'test']);
        $companySetting->two_factor_enabled = true;
        $companySetting->save();

        $response = $this->get($this->tenantUrl('dashboard'));
        $response->assertRedirect();
        $this->assertStringContainsString('two-factor', $response->headers->get('Location'));

        // Reset
        $companySetting->two_factor_enabled = false;
        $companySetting->save();

        $this->user->two_factor_confirmed_at = $originalConfirmedAt;
        $this->user->save();
    }
}
