<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Settings\Settings as AdminSettings;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Settings\Settings as TenantSettings;
use App\Models\Tenants\UserMangment\User\User as TenantUser;
use Tests\TestCase;

class PlatformAndTenantSettingsRefactorTest extends TestCase
{
    private AdminUser $admin;
    private Tenant $tenant;
    private TenantUser $tenantUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminUser::first() ?? AdminUser::factory()->create();

        $this->tenant = Tenant::findOrFail('test');
        tenancy()->initialize($this->tenant);
        $this->tenantUser = TenantUser::where('email', 'demo@lot.com')->firstOrFail();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    private function tenantUrl(string $path = ''): string
    {
        return 'http://test.localhost/' . ltrim($path, '/');
    }

    public function test_superadmin_settings_view_has_no_tabs_and_no_etmam_or_microsoft(): void
    {
        tenancy()->end();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.settings.index'));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringNotContainsString('x-shared.ui.tabs', $content);
        $this->assertStringNotContainsString('nav-tabs', $content);
        $this->assertStringNotContainsString('tab-content', $content);
        $this->assertStringNotContainsString('tab-pane', $content);

        // No Etmam or Microsoft
        $this->assertStringNotContainsString('etmamSettings', $content);
        $this->assertStringNotContainsString('etmam_base_url', $content);
        $this->assertStringNotContainsString('microsoft_client_id', $content);
        $this->assertStringNotContainsString('microsoft_redirect_uri', $content);

        // Has general settings fields
        $this->assertStringContainsString('name="office_name"', $content);
        $this->assertStringContainsString('name="logo_text"', $content);
        $this->assertStringContainsString('name="office_address"', $content);
        $this->assertStringContainsString('name="commercial_register"', $content);
        $this->assertStringContainsString('name="tax_number"', $content);
        $this->assertStringContainsString('name="phone_number"', $content);
        $this->assertStringContainsString('name="email"', $content);
        $this->assertStringContainsString('name="google_maps_embed_url"', $content);
    }

    public function test_superadmin_can_update_settings(): void
    {
        tenancy()->end();

        $payload = [
            'office_name' => 'شركة لوت العقارية التجريبية',
            'logo_text' => 'لوت للتقنية',
            'office_address' => 'الرياض - طريق الملك فهد',
            'commercial_register' => '1010123456',
            'tax_number' => '300012345600003',
            'phone_number' => '0501234567',
            'email' => 'admin@lot.sa',
        ];

        $response = $this->actingAs($this->admin, 'admin')
            ->put(route('admin.settings.update'), $payload);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHas('success');

        $settings = AdminSettings::first();
        $this->assertNotNull($settings);
        $this->assertSame('شركة لوت العقارية التجريبية', $settings->office_name);
        $this->assertSame('لوت للتقنية', $settings->logo_text);
    }

    public function test_tenant_settings_view_has_no_tabs_and_merged_operational_regions(): void
    {
        $response = $this->actingAs($this->tenantUser, 'tenant')
            ->get($this->tenantUrl('settings'));

        $response->assertOk();
        $content = $response->getContent();

        // No tabs
        $this->assertStringNotContainsString('x-shared.ui.tabs', $content);
        $this->assertStringNotContainsString('nav-tabs', $content);
        $this->assertStringNotContainsString('tab-content', $content);
        $this->assertStringNotContainsString('tab-pane', $content);

        // No print, SMS, or maintenance mode
        $this->assertStringNotContainsString('printSettings', $content);
        $this->assertStringNotContainsString('SmsSettings', $content);
        $this->assertStringNotContainsString('horizontal_header_image', $content);
        $this->assertStringNotContainsString('sms_provider', $content);
        $this->assertStringNotContainsString('name="maintenance_mode"', $content);

        // Has general settings and operational regions
        $this->assertStringContainsString('name="office_name"', $content);
        $this->assertStringContainsString('name="logo_text"', $content);
        $this->assertStringContainsString('name="operational_regions[]"', $content);
    }

    public function test_tenant_can_update_settings(): void
    {
        $payload = [
            'office_name' => 'مكتب المستأجر المعتمد',
            'logo_text' => 'مكتب المعتمد',
            'operational_regions' => ['منطقة الرياض'],
        ];

        $response = $this->actingAs($this->tenantUser, 'tenant')
            ->put($this->tenantUrl('settings'), $payload);

        $response->assertRedirect($this->tenantUrl('settings'));
        $response->assertSessionHas('success');

        $settings = TenantSettings::first();
        $this->assertNotNull($settings);
        $this->assertSame('مكتب المستأجر المعتمد', $settings->office_name);
        $this->assertSame('مكتب المعتمد', $settings->logo_text);
        $this->assertSame(['منطقة الرياض'], $settings->operational_regions);
    }

    public function test_tenant_detailed_settings_center_is_decommissioned(): void
    {
        // 1. Center view file does not exist
        $this->assertFalse(file_exists(resource_path('views/tenant/settings/center.blade.php')));

        // 2. Accessing route returns 404
        $this->actingAs($this->tenantUser, 'tenant')
            ->get($this->tenantUrl('settings/center'))
            ->assertStatus(404);

        // 3. Navbar does not contain link to detailed settings
        $response = $this->actingAs($this->tenantUser, 'tenant')
            ->get($this->tenantUrl('dashboard'));
        $response->assertOk();
        $this->assertStringNotContainsString('settings.center', $response->getContent());
        $this->assertStringNotContainsString('الإعدادات التفصيلية', $response->getContent());
    }
}

