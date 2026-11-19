<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Actions\Tenants\Auctions\CreateAuctionAction;
use App\Actions\Tenants\Auctions\UpdateAuctionAction;
use App\Enums\Tenants\Properties\PropertyLegalStatus;
use App\Http\Requests\Tenants\Auctions\StoreAuctionRequest;
use App\Http\Requests\Tenants\Auctions\UpdateAuctionRequest;
use App\Http\Requests\Tenants\Properties\StorePropertyRequest;
use App\Http\Requests\Tenants\Properties\UpdatePropertyRequest;
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
        tenancy()->initialize('test');
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

        $assignment = Assignment::where('is_active', true)->whereDate('end_date', '>=', now())->first();
        $this->assertNotNull($assignment);

        $issuingEntity = IssuingEntity::first();
        $this->assertNotNull($issuingEntity);

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

        $this->assertSame('عمر العقار', $storePropAttrs['age']);
        $this->assertSame('الحالة النظامية', $storePropAttrs['legal_status']);
        $this->assertSame('عمر العقار مطلوب.', $storePropMsgs['age.required']);
        $this->assertSame('الحالة النظامية للعقار مطلوبة.', $storePropMsgs['legal_status.required']);

        $updateProperty = new UpdatePropertyRequest;
        $updatePropAttrs = $updateProperty->attributes();
        $updatePropMsgs = $updateProperty->messages();

        $this->assertSame('عمر العقار', $updatePropAttrs['age']);
        $this->assertSame('الحالة النظامية', $updatePropAttrs['legal_status']);
        $this->assertSame('عمر العقار مطلوب.', $updatePropMsgs['age.required']);
        $this->assertSame('الحالة النظامية للعقار مطلوبة.', $updatePropMsgs['legal_status.required']);
    }
}
