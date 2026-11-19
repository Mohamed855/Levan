<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\DataTables\Tenants\MarketingCampaigns\MarketingCampaignsDataTable;
use App\Http\Requests\Tenants\AuctionClosings\SaveAuctionClosingRequest;
use App\Http\Requests\Tenants\AuctionEstablishments\SaveAuctionEstablishmentRequest;
use App\Http\Requests\Tenants\Auctions\StoreAuctionRequest;
use App\Http\Requests\Tenants\MarketingCampaigns\SaveMarketingCampaignRequest;
use App\Http\Requests\Tenants\Settings\MainSettingsRequest;
use App\Models\Tenants\AuctionClosings\AuctionClosing;
use App\Services\Tenants\AuctionEstablishments\AuctionEstablishmentRequestData;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use Tests\TestCase;

final class AuctionDataRestructureVerificationTest extends TestCase
{
    public function test_settings_request_validates_unified_number_fal_license_and_representative_name(): void
    {
        $request = new MainSettingsRequest;
        $rules = $request->rules();

        self::assertArrayHasKey('unified_number', $rules);
        self::assertArrayHasKey('fal_license', $rules);
        self::assertArrayHasKey('representative_name', $rules);

        self::assertFalse(Validator::make([
            'unified_number' => '7001234567',
            'fal_license' => '123456',
            'representative_name' => 'أحمد محمد',
        ], $rules)->fails());

        self::assertTrue(Validator::make([
            'unified_number' => '8001234567',
        ], ['unified_number' => $rules['unified_number']])->fails());
    }

    public function test_store_auction_request_requires_introduction_and_accepts_step4_marketing_fields(): void
    {
        $request = new StoreAuctionRequest;
        $rules = $request->rules();

        self::assertContains('required', $rules['marketing.introduction']);
        self::assertArrayHasKey('marketing.planning_methodology', $rules);
        self::assertArrayHasKey('marketing.city_context', $rules);
        self::assertArrayHasKey('marketing.brochure_methodology', $rules);
        self::assertArrayHasKey('marketing.audience_methodology', $rules);
        self::assertArrayHasKey('marketing.messages_methodology', $rules);
        self::assertArrayHasKey('marketing.brochure_context', $rules);
        self::assertArrayHasKey('marketing.brochure_url', $rules);
        self::assertArrayHasKey('marketing.board_context', $rules);
        self::assertArrayHasKey('marketing.board_url', $rules);
        self::assertArrayHasKey('marketing.contract_text', $rules);
        self::assertArrayHasKey('marketing.brokers', $rules);
        self::assertArrayHasKey('brochure_evidence', $rules);
        self::assertArrayHasKey('platform_evidence', $rules);
        self::assertArrayHasKey('broker_evidence', $rules);
    }

    public function test_marketing_campaign_request_requires_unified_social_context_and_removes_old_channels(): void
    {
        $request = SaveMarketingCampaignRequest::create('/', 'POST', [
            'auction_id' => 1,
            'channels' => ['x', 'whatsapp', 'snapchat'],
            'campaigns' => [],
        ]);
        $rules = $request->rules();

        self::assertContains('required', $rules['social_context']);
        self::assertArrayNotHasKey('campaigns.x.metrics.social_context', $rules);
        self::assertArrayNotHasKey('campaigns.x.metrics.impressions', $rules);
        self::assertArrayNotHasKey('campaigns.snapchat.metrics.impressions', $rules);
        self::assertArrayNotHasKey('campaigns.whatsapp.metrics.total', $rules);
        self::assertArrayHasKey('campaigns.x.metrics.views', $rules);
        self::assertArrayHasKey('campaigns.snapchat.metrics.views', $rules);
    }

    public function test_marketing_campaigns_datatable_no_longer_displays_completion_status(): void
    {
        $method = new ReflectionMethod(MarketingCampaignsDataTable::class, 'getColumns');
        $columns = array_column($method->invoke(new MarketingCampaignsDataTable), 'data');

        self::assertNotContains('completion_status', $columns);
    }

    public function test_establishment_schema_removes_deed_upload_and_adds_required_board_fields(): void
    {
        $schema = new AuctionEstablishmentRequestData;

        self::assertArrayNotHasKey('deed', $schema->attachmentTypes());
        self::assertArrayHasKey('board_count', $schema->propertyFields());
        self::assertArrayHasKey('board_dimensions', $schema->propertyFields());
        self::assertTrue($schema->propertyFields()['board_count']['required']);
        self::assertTrue($schema->propertyFields()['board_dimensions']['required']);

        $request = new SaveAuctionEstablishmentRequest;
        $rules = $request->rules();
        self::assertArrayHasKey('board_images_before', $rules);
        self::assertArrayHasKey('remove_board_before_evidence', $rules);
    }

    public function test_closing_request_and_model_remove_opening_price_and_approval_number(): void
    {
        $request = new SaveAuctionClosingRequest;
        $rules = $request->rules();

        self::assertArrayNotHasKey('results.*.opening_price', $rules);
        self::assertArrayNotHasKey('approval_number', $rules);
        self::assertNotContains('approval_number', (new AuctionClosing)->getFillable());
    }
}
