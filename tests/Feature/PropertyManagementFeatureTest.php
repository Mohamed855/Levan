<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Tenants\Properties\PropertyType;
use App\Enums\Tenants\Properties\PropertyUsageClassification;
use App\Enums\Tenants\Properties\PropertyOwnershipType;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Properties\PropertyService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyManagementFeatureTest extends TestCase
{
    private Tenant $tenant;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::findOrFail('test');
        tenancy()->initialize($this->tenant);
        $this->owner = User::where('email', 'demo@levan.com')->firstOrFail();
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

    public function test_user_can_view_properties_index(): void
    {
        $response = $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('properties'));

        $response->assertOk();
        $response->assertSee('إجمالي العقارات');
        $response->assertSee('مكتملة البيانات');
        $response->assertSee('غير مرتبطة بإسناد');
        $response->assertSee('تحتاج للمراجعة');
    }

    public function test_user_can_view_property_create_page(): void
    {
        $response = $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('properties/create'));

        $response->assertOk();
        $response->assertSee('البيانات الأساسية');
        $response->assertSee('الموقع والحدود');
        $response->assertSee('الملكية والتفاصيل');
        $response->assertSee('المستندات والصور');
        $response->assertSee('المراجعة والحفظ');
    }

    public function test_property_creation_with_subtables_and_attachments(): void
    {
        Storage::fake('tenant_public');

        $payload = [
            'name' => 'فيلا تجريبية للاختبار',
            'type' => PropertyType::VILLA->value,
            'usage_classification' => PropertyUsageClassification::RESIDENTIAL->value,
            'area' => 550.50,
            'is_active' => '1',
            'short_description' => 'فيلا فاخرة بموقع متميز',
            'city' => 'الرياض',
            'district' => 'النرجس',
            'administrative_region' => 'منطقة الرياض',
            'plan_number' => 'PLAN-999',
            'parcel_number' => 'PARCEL-123',
            'google_maps_url' => 'https://maps.google.com/?q=24.7136,46.6753',
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'boundary_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [
                    [
                        [46.6750, 24.7130],
                        [46.6760, 24.7130],
                        [46.6760, 24.7140],
                        [46.6750, 24.7140],
                        [46.6750, 24.7130],
                    ],
                ],
            ],
            'boundaries' => [
                ['direction' => 'north', 'border_type' => 'شارع', 'border_description' => 'شارع عرض 20م', 'length' => 25.0],
                ['direction' => 'south', 'border_type' => 'قطعة', 'border_description' => 'قطعة 12', 'length' => 25.0],
            ],
            'deed_number' => '31099887766',
            'deed_date' => now()->subYears(2)->toDateString(),
            'issuing_authority' => 'كتابة العدل الأولى بالرياض',
            'ownership_type' => PropertyOwnershipType::SOLE_OWNERSHIP->value,
            'owners' => [
                ['owner_name' => 'أحمد عبدالله', 'national_id_or_cr' => '1020304050'],
            ],
            'agent_name' => 'سعد محمد',
            'agent_national_id' => '1099887766',
            'agency_number' => 'AG-5544',
            'agency_date' => now()->subYear()->toDateString(),
            'units' => [
                ['unit_number' => '1', 'unit_type' => 'فيلا', 'area' => 400.0, 'current_use' => 'سكني', 'status' => 'available'],
            ],
            'deed_document' => UploadedFile::fake()->create('deed.pdf', 500, 'application/pdf'),
            'cadastral_plan' => UploadedFile::fake()->create('plan.pdf', 500, 'application/pdf'),
            'coordinates_statement' => UploadedFile::fake()->create('coords.pdf', 500, 'application/pdf'),
            'main_image' => UploadedFile::fake()->image('main.jpg', 800, 600),
            'notes' => 'ملاحظات تدقيق نهائية',
        ];

        $response = $this->actingAs($this->owner, 'tenant')
            ->post($this->tenantUrl('properties'), $payload);

        $response->assertRedirect($this->tenantUrl('properties'));

        $this->assertDatabaseHas('properties', [
            'name' => 'فيلا تجريبية للاختبار',
            'plan_number' => 'PLAN-999',
            'deed_number' => '31099887766',
            'data_status' => 'completed',
        ]);

        $property = Property::where('deed_number', '31099887766')->firstOrFail();
        $this->assertNotNull($property->boundary_geojson);
        $this->assertEquals('Polygon', $property->boundary_geojson['type']);
        $this->assertCount(2, $property->boundaries);
        $this->assertCount(1, $property->owners);
        $this->assertCount(1, $property->units);
        $this->assertGreaterThanOrEqual(4, $property->attachments()->count());

        $property->forceDelete();
    }

    public function test_property_location_search_endpoint(): void
    {
        $mockService = $this->createMock(\App\Services\Tenants\Properties\GooglePlacesService::class);
        $mockService->method('search')->willReturn([
            [
                'id' => 'place_123',
                'name' => 'حي النرجس - الرياض',
                'address' => 'الرياض، المملكة العربية السعودية',
                'latitude' => 24.8123,
                'longitude' => 46.6543,
            ]
        ]);
        $this->app->instance(\App\Services\Tenants\Properties\GooglePlacesService::class, $mockService);

        $response = $this->actingAs($this->owner, 'tenant')
            ->postJson($this->tenantUrl('properties/search-location'), [
                'query' => 'حي النرجس',
                'latitude' => 24.7136,
                'longitude' => 46.6753,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'places' => [
                    [
                        'id' => 'place_123',
                        'name' => 'حي النرجس - الرياض',
                        'latitude' => 24.8123,
                        'longitude' => 46.6543,
                    ]
                ]
            ]);
    }

    public function test_property_service_statistics(): void
    {
        $service = app(PropertyService::class);
        $stats = $service->getStatistics();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('ready_for_assignment', $stats);
        $this->assertArrayHasKey('unlinked', $stats);
        $this->assertArrayHasKey('needs_review', $stats);
    }
}
