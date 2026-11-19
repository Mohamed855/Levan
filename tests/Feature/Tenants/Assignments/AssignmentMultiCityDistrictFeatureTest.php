<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Assignments;

use App\DataTables\Tenants\Assignments\AssignmentPropertySelectionDataTable;
use App\Http\Requests\Tenants\Assignments\StoreAssignmentRequest;
use App\Http\Requests\Tenants\Assignments\UpdateAssignmentRequest;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AssignmentMultiCityDistrictFeatureTest extends TestCase
{
    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::findOrFail('test');
        tenancy()->initialize($this->tenant);
        $this->user = User::where('email', 'demo@lot.com')->firstOrFail();
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_assignment_model_handles_multiple_cities_and_districts(): void
    {
        $assignment = new Assignment;
        $assignment->city = ['الرياض', 'جدة'];
        $assignment->district = ['الملقا', 'الصفا', 'الروضة'];

        $this->assertSame(['الرياض', 'جدة'], $assignment->city);
        $this->assertSame(['الملقا', 'الصفا', 'الروضة'], $assignment->district);
        $this->assertSame('الرياض، جدة', $assignment->city_label);
        $this->assertSame('الملقا، الصفا، الروضة', $assignment->district_label);

        // Backward compatibility: legacy string in raw attributes
        $legacy = new Assignment;
        $legacy->setRawAttributes(['city' => 'الدمام', 'district' => 'الشاطئ']);
        $this->assertSame(['الدمام'], $legacy->city);
        $this->assertSame(['الشاطئ'], $legacy->district);
        $this->assertSame('الدمام', $legacy->city_label);
        $this->assertSame('الشاطئ', $legacy->district_label);
    }

    public function test_store_and_update_requests_require_city_and_district_as_non_empty_arrays(): void
    {
        $storeReq = new StoreAssignmentRequest;
        $storeRules = $storeReq->rules();

        $this->assertContains('required', $storeRules['city']);
        $this->assertContains('array', $storeRules['city']);
        $this->assertContains('min:1', $storeRules['city']);

        $this->assertContains('required', $storeRules['district']);
        $this->assertContains('array', $storeRules['district']);
        $this->assertContains('min:1', $storeRules['district']);

        $updateReq = new UpdateAssignmentRequest;
        $updateRules = $updateReq->rules();

        $this->assertContains('required', $updateRules['city']);
        $this->assertContains('array', $updateRules['city']);
        $this->assertContains('min:1', $updateRules['city']);

        $this->assertContains('required', $updateRules['district']);
        $this->assertContains('array', $updateRules['district']);
        $this->assertContains('min:1', $updateRules['district']);

        // Test validator fails when district is empty (required check)
        $validator = Validator::make([
            'city' => ['الرياض'],
            'district' => [],
        ], [
            'city' => $storeRules['city'],
            'district' => $storeRules['district'],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('district'));

        // Test validator fails when city is empty
        $validatorCity = Validator::make([
            'city' => [],
            'district' => ['الملقا'],
        ], [
            'city' => $storeRules['city'],
            'district' => $storeRules['district'],
        ]);

        $this->assertTrue($validatorCity->fails());
        $this->assertTrue($validatorCity->errors()->has('city'));

        // Passes when both are non-empty arrays
        $validatorPass = Validator::make([
            'city' => ['الرياض', 'جدة'],
            'district' => ['الملقا', 'الصفا'],
        ], [
            'city' => $storeRules['city'],
            'district' => $storeRules['district'],
        ]);

        $this->assertFalse($validatorPass->fails());
    }

    public function test_locations_districts_endpoint_supports_multiple_cities_and_includes_city_meta(): void
    {
        $response = $this->actingAs($this->user, 'tenant')
            ->getJson(tenant_route_name('locations.districts', [
                'city' => ['الرياض', 'جدة'],
            ]));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $districts = collect($response->json('districts'));
        $this->assertNotEmpty($districts);

        $first = $districts->first();
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('text', $first);
        $this->assertArrayHasKey('city', $first);
        $this->assertNotEmpty($first['city']);
    }

    public function test_property_selection_datatable_filters_by_multiple_cities_and_districts(): void
    {
        $matchingProp1 = Property::create([
            'property_number' => 'PROP-M1-'.uniqid(),
            'name' => 'عقار مطابق الرياض الملقا',
            'type' => 'villa',
            'usage_classification' => 'residential',
            'city' => 'الرياض',
            'district' => 'الملقا',
            'area' => 450,
            'data_status' => 'completed',
            'status' => 'available',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $matchingProp2 = Property::create([
            'property_number' => 'PROP-M2-'.uniqid(),
            'name' => 'عقار مطابق جدة الصفا',
            'type' => 'apartment',
            'usage_classification' => 'residential',
            'city' => 'جدة',
            'district' => 'الصفا',
            'area' => 200,
            'data_status' => 'completed',
            'status' => 'available',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $nonMatchingProp = Property::create([
            'property_number' => 'PROP-NM-'.uniqid(),
            'name' => 'عقار غير مطابق الدمام الشاطئ',
            'type' => 'villa',
            'usage_classification' => 'residential',
            'city' => 'الدمام',
            'district' => 'الشاطئ',
            'area' => 600,
            'data_status' => 'completed',
            'status' => 'available',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $dataTable = new AssignmentPropertySelectionDataTable;
        $dataTable->forCity(['الرياض', 'جدة'])
            ->forDistrict(['الملقا', 'الصفا']);

        $query = Property::query();
        $ref = new \ReflectionClass($dataTable);
        $method = $ref->getMethod('applyCustomFilters');
        $method->setAccessible(true);
        $method->invoke($dataTable, $query);

        $results = $query->pluck('id')->all();

        $this->assertContains($matchingProp1->id, $results);
        $this->assertContains($matchingProp2->id, $results);
        $this->assertNotContains($nonMatchingProp->id, $results);
    }

    public function test_assignment_creation_with_multiple_cities_and_districts_persists(): void
    {
        Storage::fake('tenant_public');

        $property = Property::create([
            'property_number' => 'PROP-MULTI-'.uniqid(),
            'name' => 'عقار اختبار متعدد المدن',
            'type' => 'villa',
            'usage_classification' => 'residential',
            'city' => 'الرياض',
            'district' => 'الملقا',
            'area' => 350,
            'data_status' => 'completed',
            'status' => 'available',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $assignmentNumber = 'TEST-MULTI-'.strtoupper(uniqid());

        $response = $this->actingAs($this->user, 'tenant')
            ->post('http://test.localhost/assignments', [
                'assignment_number' => $assignmentNumber,
                'name' => 'إسناد مدن وأحياء متعددة',
                'issuing_entity' => 'محكمة التنفيذ بالرياض',
                'city' => ['الرياض', 'جدة'],
                'district' => ['الملقا', 'الصفا'],
                'category' => ['residential'],
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'notes' => 'وصف تفصيلي للإسناد متعدد المدن والأحياء',
                'property_ids' => [$property->id],
                'assignment_image' => UploadedFile::fake()->image('asg.jpg'),
                'assignment_letter' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
                'general_documents' => [UploadedFile::fake()->create('doc.pdf', 100)],
            ]);

        $response->assertRedirect('http://test.localhost/assignments');
        $response->assertSessionHas('success', 'تم إضافة الإسناد بنجاح');

        $assignment = Assignment::where('assignment_number', $assignmentNumber)->firstOrFail();
        $this->assertSame(['الرياض', 'جدة'], $assignment->city);
        $this->assertSame(['الملقا', 'الصفا'], $assignment->district);
        $this->assertSame('الرياض، جدة', $assignment->city_label);
        $this->assertSame('الملقا، الصفا', $assignment->district_label);

        // Verify show page renders multiple cities and districts
        $showResponse = $this->actingAs($this->user, 'tenant')
            ->get('http://test.localhost/assignments/'.$assignment->id);

        $showResponse->assertOk()
            ->assertSee('الرياض، جدة')
            ->assertSee('الملقا، الصفا');
    }
}
