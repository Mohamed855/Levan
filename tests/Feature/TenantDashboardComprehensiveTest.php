<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use Tests\TestCase;

class TenantDashboardComprehensiveTest extends TestCase
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
        if (tenancy()->initialized) {
            tenancy()->end();
        }
        parent::tearDown();
    }

    protected function tenantUrl(string $path): string
    {
        return 'http://test.localhost/' . ltrim($path, '/');
    }

    /**
     * Test 1: Tenant Dashboard views and active statistics
     */
    public function test_tenant_dashboard_and_metrics(): void
    {
        $response = $this->get($this->tenantUrl('dashboard'));
        $response->assertStatus(200);

        // Core statistics endpoint
        $this->getJson($this->tenantUrl('dashboard/statistics'))->assertStatus(200);
    }

    /**
     * Test 2: Settings, Settings Center, and API Token Settings
     */
    public function test_settings_and_center_endpoints(): void
    {
        $this->get($this->tenantUrl('settings'))->assertStatus(200);
        $this->get($this->tenantUrl('settings/center'))->assertStatus(200);
        $this->get($this->tenantUrl('api-token-settings'))->assertStatus(200);
    }

    /**
     * Test 3: Tickets Management (الدعم الفني)
     */
    public function test_tickets(): void
    {
        $settings = \App\Models\Admin\Settings\Settings::first();
        $originalEnabled = $settings?->tickets_enabled ?? false;

        try {
            // 1. Unconfigured redirects to dashboard with error message
            $settings?->update(['tickets_enabled' => 0]);
            $this->get($this->tenantUrl('tickets'))
                ->assertRedirect($this->tenantUrl('dashboard'));

            // 2. When configured, loads index and create views
            \Illuminate\Support\Facades\Http::fake([
                '*' => \Illuminate\Support\Facades\Http::response(['success' => true, 'data' => ['data' => ['data' => []]]], 200),
            ]);

            $settings?->update([
                'tickets_enabled' => 1,
                'etmam_base_url' => 'https://api.example.com',
                'etmam_api_token' => 'token123',
                'etmam_system_code' => 'SYS123',
                'etmam_hmac_secret' => 'SECRET123',
            ]);

            $this->get($this->tenantUrl('tickets'))->assertStatus(200);
            $this->get($this->tenantUrl('tickets/create'))->assertStatus(200);
        } finally {
            $settings?->update([
                'tickets_enabled' => $originalEnabled,
            ]);
        }
    }

    /**
     * Test 4: Subscriptions and Bills
     */
    public function test_subscriptions_and_bills(): void
    {
        $this->get($this->tenantUrl('subscriptions'))->assertSuccessful();
        $this->get($this->tenantUrl('subscriptions/licenses?new_licenses_count=1'))->assertSuccessful();
        $this->get($this->tenantUrl('subscriptions/renew'))->assertSuccessful();

        $this->get($this->tenantUrl('bills'))->assertSuccessful();
    }

    /**
     * Test 5: Activity Logs
     */
    public function test_activity_logs(): void
    {
        $this->get($this->tenantUrl('activity-logs'))->assertStatus(200);
        $this->get($this->tenantUrl('activity-logs/archives'))->assertStatus(200);
    }

    /**
     * Test 6: Archive Endpoints for Active Domains
     */
    public function test_archive_endpoints(): void
    {
        $this->get($this->tenantUrl('archive/companies'))->assertStatus(200);
        $this->get($this->tenantUrl('archive/users'))->assertStatus(200);
        $this->get($this->tenantUrl('archive/assignments'))->assertStatus(200);
        $this->get($this->tenantUrl('archive/properties'))->assertStatus(200);
        $this->get($this->tenantUrl('archive/auctions'))->assertStatus(200);
    }

    /**
     * Test 7: Verify that decommissioned legacy debt collection routes return 404
     */
    public function test_legacy_debt_collection_routes_are_decommissioned(): void
    {
        $decommissionedPaths = [
            'tasks',
            'my-tasks',
            'customers',
            'contracts',
            'portfolios',
            'company-letters',
            'communication/message-logs',
            'communication/message-individual-logs',
            'general-settings/settings-customer-status',
            'general-settings/message-templates',
            'general-settings/settings-payment-methods',
            'general-settings/settings-country',
            'reports/customers',
            'reports/financial',
            'reports/companies',
            'archive/customers',
            'archive/contracts',
            'archive/portfolios',
        ];

        foreach ($decommissionedPaths as $path) {
            $this->get($this->tenantUrl($path))->assertNotFound();
        }
    }
}
