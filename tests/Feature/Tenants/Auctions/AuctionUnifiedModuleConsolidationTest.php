<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Actions\Tenants\Auctions\CreateAuctionAction;
use App\Actions\Tenants\Auctions\UpdateAuctionAction;
use App\Actions\Tenants\Properties\UpdatePropertyAction;
use App\Enums\Tenants\Properties\PropertyLegalStatus;
use App\Http\Requests\Tenants\Auctions\StoreAuctionRequest;
use App\Http\Requests\Tenants\Auctions\UpdateAuctionRequest;
use App\Http\Requests\Tenants\Properties\StorePropertyRequest;
use App\Http\Requests\Tenants\Properties\UpdatePropertyRequest;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Assignments\IssuingEntity;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\MarketingCampaigns\MarketingCampaign;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AuctionUnifiedModuleConsolidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $tenant = Tenant::first() ?? Tenant::find('test');
        if ($tenant) {
            tenancy()->initialize($tenant);
        }
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        parent::tearDown();
    }

    public function test_property_model_supports_age_and_legal_status(): void
    {
        $property = Property::first();
        $this->assertNotNull($property);

        $property->update([
            'age' => 5,
            'legal_status' => PropertyLegalStatus::CLEAR,
            'legal_status_notes' => null,
        ]);

        $fresh = $property->fresh();
        $this->assertSame(5, $fresh->age);
        $this->assertSame(PropertyLegalStatus::CLEAR, $fresh->legal_status);
        $this->assertSame('سليم', $fresh->legal_status_label);
    }

    public function test_sidebar_menu_contains_only_auctions_under_auctions_and_reports(): void
    {
        $menuContent = file_get_contents(resource_path('views/tenant/layouts/sections/menu/verticalMenu.blade.php'));

        $this->assertStringContainsString('المزادات والتقارير', $menuContent);
        $this->assertStringContainsString('auctions.index', $menuContent);
        $this->assertStringNotContainsString('auction-establishment-requests.index', $menuContent);
        $this->assertStringNotContainsString('marketing-campaigns.index', $menuContent);
        $this->assertStringNotContainsString('auction-closings.index', $menuContent);
    }

    public function test_auction_closings_edit_view_returns_to_auctions_show(): void
    {
        $editContent = file_get_contents(resource_path('views/tenant/auction-closings/edit.blade.php'));

        $this->assertStringContainsString('tenant_route_name(\'auctions.show\', $auction)', $editContent);
        $this->assertStringNotContainsString('tenant_route_name(\'auction-closings.index\')', $editContent);
    }

    public function test_create_and_update_auction_with_unified_modules(): void
    {
        $user = User::where('email', 'demo@lot.com')->first();
        $this->assertNotNull($user);

        $issuingEntity = IssuingEntity::first();
        $this->assertNotNull($issuingEntity);

        $assignment = Assignment::where('is_active', true)->whereDate('end_date', '>=', now())->first();
        if (! $assignment) {
            $assignment = Assignment::create([
                'name' => 'إسناد تجريبي للمزاد',
                'assignment_number' => 'ASG-TEST-'.uniqid(),
                'issuing_entity' => $issuingEntity->name,
                'issuing_entity_id' => $issuingEntity->id,
                'created_by' => $user->id,
                'is_active' => true,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(6)->toDateString(),
            ]);
        }

        $property = Property::where('assignment_id', $assignment->id)->first();
        if (! $property) {
            $property = Property::first();
            $property->update(['assignment_id' => $assignment->id]);
        }
        $property->update([
            'age' => 3,
            'legal_status' => PropertyLegalStatus::CLEAR,
        ]);

        $valFile = UploadedFile::fake()->create('valuation.pdf', 100, 'application/pdf');
        $boardFile = UploadedFile::fake()->image('board.jpg');

        $auctionData = [
            'name' => 'مزاد الاختبار الموحد '.uniqid(),
            'description' => 'وصف تفصيلي للمزاد الموحد لاختبار الدمج الشامل لجميع الموديلات.',
            'assignment_id' => $assignment->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '18:00',
            'type' => 'electronic',
            'category' => $property->usage_classification === 'residential' ? 'residential' : 'commercial',
            'city' => 'الرياض',
            'expected_properties_count' => 1,
            'project_manager_name' => 'مهندس أحمد',
            'platform_entity_id' => $issuingEntity->id,
            'property_ids' => [$property->id],
            'award_period' => 45,
            'venue' => 'الرياض - قاعة المزادات',
            'auctioneer' => [
                'name' => 'سالم محمد',
                'national_id_or_cr' => '1098765432',
                'mobile' => '0555555555',
                'email' => 'auctioneer@test.com',
            ],
            'funds' => [
                'receipt_method' => 'wire',
                'receipt_days' => 2,
                'entry_type' => 'refundable',
                'refund_method' => 'wire',
                'refund_days' => 1,
                'safekeeping' => 'حساب الضمان البنكي المعتمد',
            ],
            'disclosures' => [
                'disposition_rights' => ['none'],
                'no_conflict_of_interest' => '1',
                'information_accuracy' => '1',
                'adherence_to_regulations' => '1',
            ],
            'properties_data' => [
                $property->id => [
                    'opening_value' => 500000,
                    'entry_amount' => 50000,
                    'ownership_share' => 100,
                    'disclosure' => 'لا توجد موانع نظامية',
                    'board_count' => 2,
                    'board_dimensions' => '3x2',
                    'valuation' => $valFile,
                    'board_before' => [$boardFile],
                ],
            ],
            'marketing' => [
                'introduction' => 'مقدمة الحملة التسويقية الشاملة',
                'report_summary' => 'ملخص تقرير التسويق',
            ],
            'channels' => ['whatsapp'],
            'campaigns' => [
                'whatsapp' => [
                    'campaign_date' => now()->toDateString(),
                    'metrics' => [
                        'delivered' => 100,
                        'failed' => 5,
                        'pending' => 0,
                    ],
                ],
            ],
        ];

        // 1. Test CreateAuctionAction
        $createAction = app(CreateAuctionAction::class);
        $auction = $createAction->execute($auctionData, $user->id);

        $this->assertInstanceOf(Auction::class, $auction);
        $this->assertDatabaseHas('auctions', ['id' => $auction->id]);

        // Verify auction_properties pivot
        $pivot = DB::table('auction_properties')
            ->where('auction_id', $auction->id)
            ->where('property_id', $property->id)
            ->first();

        $this->assertNotNull($pivot);
        $this->assertEquals(500000, $pivot->opening_price);
        $this->assertEquals(50000, $pivot->entry_amount);
        $this->assertEquals(100, $pivot->ownership_share);
        $this->assertSame('لا توجد موانع نظامية', $pivot->disclosure);

        // Verify AuctionEstablishmentRequest was automatically created
        $this->assertNotNull($auction->establishmentRequest);
        $estData = $auction->establishmentRequest->data;
        $this->assertSame('سالم محمد', $estData['auctioneer']['name']);
        $this->assertSame('حساب الضمان البنكي المعتمد', $estData['funds']['safekeeping']);
        $this->assertNotEmpty($estData['properties']);
        $this->assertSame(3, $estData['properties'][0]['age']);

        // Verify Marketing Campaigns was created
        $campaign = MarketingCampaign::where('auction_id', $auction->id)->where('channel', 'whatsapp')->first();
        $this->assertNotNull($campaign);
        $this->assertEquals(105, $campaign->metrics['total'] ?? null);

        // 2. Test UpdateAuctionAction
        $updateAction = app(UpdateAuctionAction::class);
        $updateData = $auctionData;
        $updateData['name'] = 'مزاد تم تحديثه '.uniqid();
        $updateData['properties_data'][$property->id]['opening_value'] = 600000;
        unset($updateData['properties_data'][$property->id]['valuation']);
        unset($updateData['properties_data'][$property->id]['board_before']);

        $updatedAuction = $updateAction->execute($auction, $updateData);
        $this->assertSame($updateData['name'], $updatedAuction->name);

        $updatedPivot = DB::table('auction_properties')
            ->where('auction_id', $auction->id)
            ->where('property_id', $property->id)
            ->first();
        $this->assertEquals(600000, $updatedPivot->opening_price);
    }

    public function test_auction_and_property_requests_contain_arabic_attributes_and_messages(): void
    {
        $storeAuction = new StoreAuctionRequest;
        $storeAuctionAttrs = $storeAuction->attributes();
        $storeAuctionMsgs = $storeAuction->messages();

        $this->assertSame('بيانات محرّج المزاد', $storeAuctionAttrs['auctioneer']);
        $this->assertSame('اسم محرّج المزاد', $storeAuctionAttrs['auctioneer.name']);
        $this->assertSame('مدة الترسية بالدقائق', $storeAuctionAttrs['award_period']);
        $this->assertSame('بيانات أموال المتنافسين', $storeAuctionAttrs['funds']);
        $this->assertSame('بيانات العقارات في المزاد', $storeAuctionAttrs['properties_data']);
        $this->assertSame('قيمة افتتاح المزاد', $storeAuctionAttrs['properties_data.*.opening_value']);
        $this->assertSame('قنوات الحملات التسويقية', $storeAuctionAttrs['channels']);
        $this->assertSame('الإفصاحات النظامية', $storeAuctionAttrs['disclosures']);

        $this->assertSame('اسم محرّج المزاد مطلوب.', $storeAuctionMsgs['auctioneer.name.required']);
        $this->assertSame('مدة الترسية بالدقائق مطلوبة.', $storeAuctionMsgs['award_period.required']);
        $this->assertSame('قيمة افتتاح المزاد مطلوبة لكل عقار مختار.', $storeAuctionMsgs['properties_data.*.opening_value.required']);
        $this->assertSame('يجب الإقرار بعدم وجود تعارض مصالح للمتابعة.', $storeAuctionMsgs['disclosures.no_conflict_of_interest.accepted']);

        $updateAuction = new UpdateAuctionRequest;
        $updateAuctionAttrs = $updateAuction->attributes();
        $this->assertSame('حذف صورة غلاف المزاد', $updateAuctionAttrs['remove_cover_image']);
        $this->assertSame('المرفقات المراد حذفها', $updateAuctionAttrs['remove_attachments']);

        $storeProperty = new StorePropertyRequest;
        $storePropAttrs = $storeProperty->attributes();
        $storePropMsgs = $storeProperty->messages();

        $this->assertSame('عمر العقار بالسنوات', $storePropAttrs['age']);
        $this->assertSame('الحالة النظامية', $storePropAttrs['legal_status']);
        $this->assertSame('تفاصيل الحالة النظامية الأخرى', $storePropAttrs['legal_status_notes']);
        $this->assertSame('عمر العقار بالسنوات مطلوب.', $storePropMsgs['age.required']);
        $this->assertSame('الحالة النظامية للعقار مطلوبة.', $storePropMsgs['legal_status.required']);
        $this->assertSame('تفاصيل الحالة النظامية الأخرى مطلوبة عند اختيار أخرى.', $storePropMsgs['legal_status_notes.required_if']);

        $updateProperty = new UpdatePropertyRequest;
        $updatePropAttrs = $updateProperty->attributes();
        $updatePropMsgs = $updateProperty->messages();

        $this->assertSame('عمر العقار بالسنوات', $updatePropAttrs['age']);
        $this->assertSame('الحالة النظامية', $updatePropAttrs['legal_status']);
        $this->assertSame('تفاصيل الحالة النظامية الأخرى', $updatePropAttrs['legal_status_notes']);
        $this->assertSame('عمر العقار بالسنوات مطلوب.', $updatePropMsgs['age.required']);
        $this->assertSame('الحالة النظامية للعقار مطلوبة.', $updatePropMsgs['legal_status.required']);
        $this->assertSame('تفاصيل الحالة النظامية الأخرى مطلوبة عند اختيار أخرى.', $updatePropMsgs['legal_status_notes.required_if']);
        $this->assertSame('مبلغ الدخول', $storeAuctionAttrs['properties_data.*.entry_amount']);
    }

    public function test_auction_request_validates_opening_and_entry_amount_hierarchy(): void
    {
        $property = Property::first();
        $this->assertNotNull($property);
        $property->update(['estimated_value' => 1000000]);

        // 1. opening_value exceeds estimated_value
        $requestData = [
            'property_ids' => [$property->id],
            'properties_data' => [
                $property->id => [
                    'opening_value' => 1200000,
                    'entry_amount' => 50000,
                    'estimated_value' => 1000000,
                ],
            ],
        ];

        // Test the custom after hook logic directly
        $customValidator = validator($requestData, []);
        $storeReq = new StoreAuctionRequest;
        $ref = new \ReflectionClass($storeReq);
        $prop = $ref->getProperty('validator');
        $prop->setAccessible(true);
        $prop->setValue($storeReq, $customValidator);

        // Bind request input
        $storeReq->merge($requestData);

        foreach ($storeReq->after() as $hook) {
            $hook($customValidator);
        }

        $this->assertTrue($customValidator->errors()->has("properties_data.{$property->id}.opening_value"));
        $this->assertSame('قيمة افتتاح المزاد لا يمكن أن تتجاوز القيمة التقديرية للعقار.', $customValidator->errors()->first("properties_data.{$property->id}.opening_value"));

        // 2. entry_amount exceeds opening_value
        $requestData2 = [
            'property_ids' => [$property->id],
            'properties_data' => [
                $property->id => [
                    'opening_value' => 500000,
                    'entry_amount' => 600000,
                    'estimated_value' => 1000000,
                ],
            ],
        ];
        $customValidator2 = validator($requestData2, []);
        $storeReq2 = new StoreAuctionRequest;
        $prop->setValue($storeReq2, $customValidator2);
        $storeReq2->merge($requestData2);

        foreach ($storeReq2->after() as $hook) {
            $hook($customValidator2);
        }

        $this->assertTrue($customValidator2->errors()->has("properties_data.{$property->id}.entry_amount"));
        $this->assertSame('مبلغ الدخول لا يمكن أن يكون أكبر من قيمة افتتاح المزاد.', $customValidator2->errors()->first("properties_data.{$property->id}.entry_amount"));
    }

    public function test_property_age_and_legal_status_dynamic_validation_and_actions(): void
    {
        $user = User::where('email', 'demo@lot.com')->first();
        $this->assertNotNull($user);

        // Verify form view contains wrappers and dynamic IDs
        $formView = file_get_contents(resource_path('views/tenant/properties/partials/_form.blade.php'));
        $this->assertStringContainsString('id="property-type"', $formView);
        $this->assertStringContainsString('id="property-age"', $formView);
        $this->assertStringContainsString('wrapperId="property-age-wrapper"', $formView);
        $this->assertStringContainsString('id="property-legal-status"', $formView);
        $this->assertStringContainsString('id="property-legal-status-notes"', $formView);
        $this->assertStringContainsString('wrapperId="property-legal-status-notes-wrapper"', $formView);

        // Verify JavaScript contains sync functions
        $jsContent = file_get_contents(resource_path('assets/js/tenant/properties/create.js'));
        $this->assertStringContainsString('syncPropertyAgeVisibility', $jsContent);
        $this->assertStringContainsString('syncLegalStatusNotesVisibility', $jsContent);

        // Verify action nullifies age for land and notes for non-other
        $property = Property::first();
        $this->assertNotNull($property);

        /** @var UpdatePropertyAction $updateAction */
        $updateAction = app(UpdatePropertyAction::class);

        // When updated to land with age provided, age must become null
        $updated = $updateAction->execute($property, [
            'type' => 'land',
            'age' => 15,
            'legal_status' => 'clear',
            'legal_status_notes' => 'ملاحظات غير مطلوبة',
        ]);

        $this->assertNull($updated->age);
        $this->assertNull($updated->legal_status_notes);

        // When updated to other legal status, notes are preserved
        $updatedOther = $updateAction->execute($property, [
            'type' => 'commercial',
            'age' => 7,
            'legal_status' => 'other',
            'legal_status_notes' => 'رهن بنكي قائم',
        ]);

        $this->assertSame(7, $updatedOther->age);
        $this->assertSame(PropertyLegalStatus::OTHER, $updatedOther->legal_status);
        $this->assertSame('رهن بنكي قائم', $updatedOther->legal_status_notes);
    }
}
