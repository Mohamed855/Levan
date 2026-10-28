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
use Illuminate\Support\Str;
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

    public function test_property_filters_explicitly_target_the_properties_table(): void
    {
        $content = $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('properties'))
            ->assertOk()
            ->getContent();

        foreach (['filter-assignment-status', 'filter-assignment', 'filter-type', 'filter-data-status', 'filter-city'] as $filterId) {
            $this->assertMatchesRegularExpression(
                '/<select[^>]*id="'.$filterId.'"[^>]*data-datatable-target="#properties-table"/',
                $content,
                $filterId.' must target the property DataTable explicitly.',
            );
        }
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
        $response->assertDontSee('name="is_active"', false);
        $this->assertMatchesRegularExpression(
            '/class="[^"]*\bd-none\b[^"]*"[^>]*id="btn-download-annotated"/',
            $response->getContent(),
            'The annotated-image download button must be hidden before an image is uploaded.'
        );
        $this->assertMatchesRegularExpression(
            '/class="[^"]*\bd-none\b[^"]*"[^>]*id="btn-clear-boundary"/',
            $response->getContent(),
            'The boundary-clear button must be hidden before an image is uploaded.'
        );
    }

    public function test_property_creation_with_subtables_and_attachments(): void
    {
        Storage::fake('tenant_public');
        Property::where('deed_number', '31099887766')->forceDelete();

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
            'plan_number' => '999',
            'parcel_number' => '123',
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
                ['direction' => 'north', 'border_type' => 'street', 'border_description' => 'شارع عرض 20م', 'length' => 25.0],
                ['direction' => 'south', 'border_type' => 'parcel', 'border_description' => 'قطعة 12', 'length' => 25.0],
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
            'agency_number' => '5544',
            'agency_date' => now()->subYear()->toDateString(),
            'units' => [
                ['unit_number' => '1', 'unit_type' => 'villa', 'area' => 400.0, 'current_use' => 'residential', 'status' => 'available'],
            ],
            'deed_document' => UploadedFile::fake()->create('deed.pdf', 500, 'application/pdf'),
            'cadastral_plan' => UploadedFile::fake()->create('plan.pdf', 500, 'application/pdf'),
            'coordinates_statement' => UploadedFile::fake()->create('coords.pdf', 500, 'application/pdf'),
            'main_image' => UploadedFile::fake()->image('main.jpg', 800, 600),
            'aerial_image' => UploadedFile::fake()->image('aerial.png', 800, 600),
            'notes' => 'ملاحظات تدقيق نهائية',
        ];

        $response = $this->actingAs($this->owner, 'tenant')
            ->post($this->tenantUrl('properties'), $payload);

        $response->assertRedirect($this->tenantUrl('properties'));

        $this->assertDatabaseHas('properties', [
            'name' => 'فيلا تجريبية للاختبار',
            'plan_number' => '999',
            'deed_number' => '31099887766',
            'data_status' => 'completed',
        ]);

        $property = Property::where('deed_number', '31099887766')->firstOrFail();
        $this->assertNotNull($property->boundary_geojson);
        $this->assertNotNull($property->aerial_image);
        $this->assertEquals('Polygon', $property->boundary_geojson['type']);
        $this->assertCount(2, $property->boundaries);
        $this->assertCount(1, $property->owners);
        $this->assertCount(1, $property->units);
        $this->assertGreaterThanOrEqual(5, $property->attachments()->count());
        $this->assertSame('available', $property->units->firstOrFail()->getRawOriginal('status'));

        $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('properties/'.$property->id))
            ->assertOk()
            ->assertSee('صورة العقار المحددة')
            ->assertSee('متاح');

        $property->forceDelete();
    }

    public function test_property_creation_with_base64_aerial_image(): void
    {
        Storage::fake('tenant_public');

        $dummyPngBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $payload = [
            'name' => 'عقار بتحديد صورة مدمجة',
            'type' => PropertyType::COMMERCIAL_LAND->value,
            'usage_classification' => PropertyUsageClassification::COMMERCIAL->value,
            'area' => 1200.00,
            'is_active' => '1',
            'short_description' => 'أرض تجارية بموقع متميز',
            'city' => 'جدة',
            'district' => 'الشاطئ',
            'administrative_region' => 'منطقة مكة المكرمة',
            'plan_number' => '777',
            'parcel_number' => '1',
            'aerial_image' => $dummyPngBase64,
            'boundary_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [
                    [
                        [10.5, 20.2],
                        [30.5, 20.2],
                        [30.5, 45.8],
                        [10.5, 45.8],
                        [10.5, 20.2],
                    ],
                ],
            ],
            'boundaries' => [
                ['direction' => 'north', 'border_type' => 'street', 'border_description' => 'شارع الملك', 'length' => 50.0],
            ],
            'deed_number' => '320000001',
            'deed_date' => now()->subYear()->toDateString(),
            'issuing_authority' => 'كتابة عدل جدة',
            'ownership_type' => PropertyOwnershipType::SOLE_OWNERSHIP->value,
            'owners' => [
                ['owner_name' => 'خالد محمد', 'national_id_or_cr' => '1098765432'],
            ],
            'deed_document' => UploadedFile::fake()->create('deed.pdf', 500, 'application/pdf'),
            'cadastral_plan' => UploadedFile::fake()->create('plan.pdf', 500, 'application/pdf'),
            'coordinates_statement' => UploadedFile::fake()->create('coords.pdf', 500, 'application/pdf'),
            'main_image' => UploadedFile::fake()->image('main.jpg', 800, 600),
        ];

        $response = $this->actingAs($this->owner, 'tenant')
            ->post($this->tenantUrl('properties'), $payload);

        $response->assertRedirect($this->tenantUrl('properties'));

        $property = Property::where('deed_number', '320000001')->firstOrFail();
        $this->assertNotNull($property->aerial_image);
        $this->assertStringContainsString('properties/aerial_images', $property->aerial_image);
        Storage::disk('tenant_public')->assertExists($property->aerial_image);

        $property->forceDelete();
    }

    public function test_property_draft_retains_aerial_image_and_boundary_geojson(): void
    {
        $resourceKey = 'tenant.property-create-'.Str::uuid();
        $aerialImage = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $boundary = ['type' => 'Polygon', 'coordinates' => [[[46.67, 24.71], [46.68, 24.71], [46.68, 24.72], [46.67, 24.71]]]];

        $this->actingAs($this->owner, 'tenant')
            ->postJson($this->tenantUrl('wizard-drafts'), [
                'resource_key' => $resourceKey,
                'step' => 2,
                'aerial_image' => $aerialImage,
                'boundary_geojson' => $boundary,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($this->owner, 'tenant')
            ->getJson($this->tenantUrl('wizard-drafts').'?resource_key='.urlencode($resourceKey))
            ->assertOk()
            ->assertJsonPath('data.aerial_image', $aerialImage)
            ->assertJsonPath('data.boundary_geojson.type', 'Polygon')
            ->assertJsonPath('data.boundary_geojson.coordinates.0.0.0', 46.67);
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

    public function test_property_creation_normalizes_arabic_is_active_input(): void
    {
        Storage::fake('tenant_public');
        Property::where('deed_number', '320000002')->forceDelete();

        $payload = [
            'name' => 'عقار اختبار تطبيع الحالة',
            'type' => PropertyType::COMMERCIAL_LAND->value,
            'usage_classification' => PropertyUsageClassification::COMMERCIAL->value,
            'area' => 500,
            'is_active' => 'نشط',
            'short_description' => 'وصف تجريبي',
            'city' => 'الرياض',
            'district' => 'الياسمين',
            'administrative_region' => 'منطقة الرياض',
            'plan_number' => '101',
            'parcel_number' => '102',
            'aerial_image' => UploadedFile::fake()->image('aerial.jpg', 600, 600),
            'boundary_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [
                    [[0.1, 0.1], [0.8, 0.1], [0.8, 0.8], [0.1, 0.8], [0.1, 0.1]],
                ],
            ],
            'deed_number' => '320000002',
            'deed_date' => now()->toDateString(),
            'issuing_authority' => 'كتابة عدل الرياض',
            'ownership_type' => PropertyOwnershipType::SOLE_OWNERSHIP->value,
            'owners' => [
                ['owner_name' => 'فهد عبدالعزيز', 'national_id_or_cr' => '1029384756'],
            ],
            'boundaries' => [
                ['direction' => 'north', 'border_type' => 'street', 'border_description' => 'شارع', 'length' => 20],
            ],
            'deed_document' => UploadedFile::fake()->create('deed.pdf', 100, 'application/pdf'),
            'cadastral_plan' => UploadedFile::fake()->create('plan.pdf', 100, 'application/pdf'),
            'coordinates_statement' => UploadedFile::fake()->create('coords.pdf', 100, 'application/pdf'),
            'main_image' => UploadedFile::fake()->image('main.jpg', 400, 400),
        ];

        $response = $this->actingAs($this->owner, 'tenant')
            ->post($this->tenantUrl('properties'), $payload);

        $response->assertRedirect($this->tenantUrl('properties'));

        $property = Property::where('deed_number', '320000002')->firstOrFail();
        $this->assertTrue($property->is_active);

        $property->forceDelete();
    }

    public function test_property_creation_defaults_to_active_without_form_input(): void
    {
        Storage::fake('tenant_public');
        Property::where('deed_number', '330000003')->forceDelete();

        $payload = [
            'name' => 'عقار افتراضي نشط',
            'type' => PropertyType::APARTMENT->value,
            'usage_classification' => PropertyUsageClassification::RESIDENTIAL->value,
            'area' => 180.5,
            'short_description' => 'شقة فاخرة مع حديقة',
            'city' => 'الدمام',
            'district' => 'الشاطئ',
            'administrative_region' => 'المنطقة الشرقية',
            'plan_number' => '404',
            'parcel_number' => '505',
            'aerial_image' => UploadedFile::fake()->image('aerial.jpg', 600, 600),
            'boundary_geojson' => [
                'type' => 'Polygon',
                'coordinates' => [
                    [[0.1, 0.1], [0.8, 0.1], [0.8, 0.8], [0.1, 0.8], [0.1, 0.1]],
                ],
            ],
            'deed_number' => '330000003',
            'deed_date' => now()->toDateString(),
            'issuing_authority' => 'كتابة عدل الدمام',
            'ownership_type' => PropertyOwnershipType::SOLE_OWNERSHIP->value,
            'owners' => [
                ['owner_name' => 'محمد الأحمد', 'national_id_or_cr' => '1029384755'],
            ],
            'boundaries' => [
                ['direction' => 'north', 'border_type' => 'street', 'border_description' => 'شارع', 'length' => 15],
            ],
            'deed_document' => UploadedFile::fake()->create('deed.pdf', 100, 'application/pdf'),
            'cadastral_plan' => UploadedFile::fake()->create('plan.pdf', 100, 'application/pdf'),
            'coordinates_statement' => UploadedFile::fake()->create('coords.pdf', 100, 'application/pdf'),
            'main_image' => UploadedFile::fake()->image('main.jpg', 400, 400),
        ];

        $response = $this->actingAs($this->owner, 'tenant')
            ->post($this->tenantUrl('properties'), $payload);

        $response->assertRedirect($this->tenantUrl('properties'));

        $property = Property::where('deed_number', '330000003')->firstOrFail();
        $this->assertTrue($property->is_active, 'Newly created property must be active by default.');

        $property->forceDelete();
    }
}
