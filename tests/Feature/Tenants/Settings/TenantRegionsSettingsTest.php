<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Settings;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Settings\Settings;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
use Tests\TestCase;

class TenantRegionsSettingsTest extends TestCase
{
    private Tenant $tenant;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::findOrFail('test');
        tenancy()->initialize($this->tenant);
        $this->owner = User::where('email', 'demo@lot.com')->firstOrFail();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            // Reset operational_regions
            Settings::query()->update(['operational_regions' => null]);
            tenancy()->end();
        }

        parent::tearDown();
    }

    private function tenantUrl(string $path = ''): string
    {
        return 'http://test.localhost/'.ltrim($path, '/');
    }

    public function test_tenant_can_view_regions_settings_tab(): void
    {
        $response = $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('settings'));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('href="#regionsSettings"', $content);
        $this->assertStringContainsString('مناطق العمل', $content);
        $this->assertStringContainsString('id="regionsSettings"', $content);
        $this->assertStringContainsString('name="operational_regions[]"', $content);
        $this->assertStringContainsString('منطقة الرياض', $content);
        $this->assertStringContainsString('المنطقة الشرقية', $content);
    }

    public function test_tenant_can_save_operational_regions(): void
    {
        $payload = [
            'active_tab' => '#regionsSettings',
            'operational_regions' => ['منطقة الرياض', 'المنطقة الشرقية'],
        ];

        $response = $this->actingAs($this->owner, 'tenant')
            ->put($this->tenantUrl('settings'), $payload);

        $response->assertRedirect('http://test.localhost/settings#regionsSettings');
        $response->assertSessionHas('success');

        $settings = Settings::first();
        $this->assertNotNull($settings);
        $this->assertSame(['منطقة الرياض', 'المنطقة الشرقية'], $settings->operational_regions);
    }

    public function test_property_dropdown_only_shows_selected_operational_regions(): void
    {
        $settings = Settings::firstOrNew();
        $settings->operational_regions = ['منطقة الرياض', 'المنطقة الشرقية'];
        $settings->save();

        $gateway = app(CentralCityOptionsGateway::class);
        $options = $gateway->regionOptionsByName();

        $this->assertCount(2, $options);
        $this->assertArrayHasKey('منطقة الرياض', $options);
        $this->assertArrayHasKey('المنطقة الشرقية', $options);
        $this->assertArrayNotHasKey('منطقة مكة المكرمة', $options);
        $this->assertArrayNotHasKey('منطقة تبوك', $options);

        // When visiting property create, only the operational regions are present in the dropdown
        $response = $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('properties/create'));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('منطقة الرياض', $content);
        $this->assertStringContainsString('المنطقة الشرقية', $content);
        // The unselected regions should not be options in the select
        $this->assertStringNotContainsString('value="منطقة تبوك"', $content);
        $this->assertStringNotContainsString('value="منطقة الجوف"', $content);
    }

    public function test_clearing_operational_regions_restores_all_regions(): void
    {
        $settings = Settings::firstOrNew();
        $settings->operational_regions = ['منطقة الرياض'];
        $settings->save();

        $payload = [
            'active_tab' => '#regionsSettings',
            'operational_regions' => [],
        ];

        $response = $this->actingAs($this->owner, 'tenant')
            ->put($this->tenantUrl('settings'), $payload);

        $response->assertRedirect('http://test.localhost/settings#regionsSettings');

        $settings->refresh();
        $this->assertNull($settings->operational_regions);

        $gateway = app(CentralCityOptionsGateway::class);
        $options = $gateway->regionOptionsByName();

        $this->assertGreaterThan(5, count($options));
        $this->assertArrayHasKey('منطقة الرياض', $options);
        $this->assertArrayHasKey('منطقة مكة المكرمة', $options);
        $this->assertArrayHasKey('منطقة تبوك', $options);
    }

    public function test_operational_regions_does_not_affect_cities(): void
    {
        $settings = Settings::firstOrNew();
        $settings->operational_regions = ['منطقة الرياض'];
        $settings->save();

        $gateway = app(CentralCityOptionsGateway::class);
        $cityOptions = $gateway->optionsByName();

        // City options must remain completely intact
        $this->assertGreaterThan(5, count($cityOptions));
        $this->assertArrayHasKey('الرياض', $cityOptions);
        $this->assertArrayHasKey('جدة', $cityOptions);
        $this->assertArrayHasKey('الدمام', $cityOptions);
    }
}
