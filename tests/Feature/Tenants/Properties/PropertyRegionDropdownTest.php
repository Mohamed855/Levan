<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Properties;

use App\Models\Admin\Region;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
use Tests\TestCase;

class PropertyRegionDropdownTest extends TestCase
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
            tenancy()->end();
        }

        parent::tearDown();
    }

    private function tenantUrl(string $path = ''): string
    {
        return 'http://test.localhost/'.ltrim($path, '/');
    }

    public function test_gateway_provides_region_options(): void
    {
        $gateway = app(CentralCityOptionsGateway::class);
        $options = $gateway->regionOptionsByName();

        $this->assertNotEmpty($options);
        $this->assertArrayHasKey('منطقة الرياض', $options);
        $this->assertArrayHasKey('منطقة مكة المكرمة', $options);
        $this->assertArrayHasKey('المنطقة الشرقية', $options);
        $this->assertSame('منطقة الرياض', $options['منطقة الرياض']);
    }

    public function test_create_form_renders_administrative_region_select(): void
    {
        $response = $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('properties/create'));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/<select[^>]*id="property-administrative-region"[^>]*name="administrative_region"/',
            $content
        );
        $this->assertStringContainsString('اختر المنطقة الإدارية', $content);
        $this->assertStringContainsString('منطقة الرياض', $content);
    }

    public function test_edit_form_renders_administrative_region_select_with_selected_value(): void
    {
        $property = Property::query()->first() ?? Property::create([
            'property_number' => 'PROP-TEST-001',
            'name' => 'عقار تجريبي للاختبار',
            'type' => 'residential_land',
            'city' => 'الرياض',
            'district' => 'النرجس',
            'administrative_region' => 'منطقة مكة المكرمة',
            'plan_number' => '100',
            'parcel_number' => '200',
            'area' => 500,
            'short_description' => 'وصف تجريبي',
            'created_by' => $this->owner->id,
        ]);

        $property->update(['administrative_region' => 'منطقة مكة المكرمة']);

        $response = $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl("properties/{$property->id}/edit"));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/<select[^>]*id="property-administrative-region"[^>]*name="administrative_region"/',
            $content
        );
        $this->assertStringContainsString('value="منطقة مكة المكرمة" selected', $content);
    }

    public function test_city_and_region_selects_are_independent(): void
    {
        $response = $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('properties/create'));

        $response->assertOk();
        $content = $response->getContent();

        // City selector has its own id and name
        $this->assertMatchesRegularExpression(
            '/<select[^>]*id="property-city"[^>]*name="city"/',
            $content
        );

        // Region selector has its own id and name
        $this->assertMatchesRegularExpression(
            '/<select[^>]*id="property-administrative-region"[^>]*name="administrative_region"/',
            $content
        );

        // District lookup route only passes city parameter, not administrative_region
        $this->assertStringContainsString('window.routes.districts', $content);
    }
}
