<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use Illuminate\Support\Facades\Schema;
use Tests\Support\TenantAuctionReportTestCase;

final class AuctionReportCycleSchemaTest extends TenantAuctionReportTestCase
{
    public function test_independent_campaigns_and_closing_have_their_required_schema(): void
    {
        self::assertTrue(Schema::hasTable('auction_marketing_campaigns'));
        self::assertTrue(Schema::hasTable('auction_closings'));
        self::assertTrue(Schema::hasColumns('auction_properties', ['buyer_name', 'result_notes']));
        self::assertTrue(Schema::hasColumns('settings', ['report_phone', 'report_unified_phone', 'report_social']));
        self::assertTrue(Schema::hasColumn('auctions', 'published_report_generation_id'));
        self::assertTrue(Schema::hasColumn('auction_attachments', 'marketing_campaign_id'));
    }
}
