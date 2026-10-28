<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AsirReportData;
use App\Services\Tenants\Auctions\Reports\AuctionReportFormat;
use PHPUnit\Framework\TestCase;

final class AuctionReportChannelsTest extends TestCase
{
    public function test_platform_status_and_inclusive_calendar_duration_come_from_the_auction(): void
    {
        $snapshot = ['auction' => ['name' => 'اختبار', 'status' => 'active', 'start_date' => '2026-05-19', 'end_date' => '2026-05-21', 'start_time' => '10:00', 'end_time' => '17:00', 'timezone' => 'Asia/Riyadh'], 'properties' => [], 'totals' => []];
        $binder = new AsirReportData(new AuctionReportFormat);
        $content = $binder->page($snapshot, ['page_type' => 'asir-platform', 'payload' => []]);
        self::assertSame('مزاد نشط', $content['values']['status']);
        self::assertSame('أيام المزاد: ٣', $content['values']['duration']);
        unset($snapshot['auction']['status']);
        self::assertSame('غير متوفر', $binder->page($snapshot, ['page_type' => 'asir-platform', 'payload' => []])['values']['status']);
    }
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
