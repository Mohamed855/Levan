<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AsirReportData;
use App\Services\Tenants\Auctions\Reports\AuctionReportFormat;
use PHPUnit\Framework\TestCase;

final class AuctionReportChannelsTest extends TestCase
{
    public function test_social_card_metrics_do_not_depend_on_campaign_input_order(): void
    {
        $snapshot = ['auction' => ['name' => 'اختبار'], 'properties' => [], 'marketing' => ['campaigns' => [
            ['platform' => 'tiktok', 'impressions' => 554000],
            ['platform' => 'x', 'impressions' => 573000],
            ['platform' => 'snapchat', 'impressions' => 220000],
        ]], 'totals' => []];
        $page = ['page_type' => 'asir-social', 'payload' => ['campaigns' => $snapshot['marketing']['campaigns']]];
        $content = (new AsirReportData(new AuctionReportFormat))->page($snapshot, $page);
        self::assertSame('220,000', $content['values']['campaign_1_views']);
        self::assertSame('554,000', $content['values']['campaign_2_views']);
        self::assertSame('573,000', $content['values']['campaign_3_views']);
    }
}
