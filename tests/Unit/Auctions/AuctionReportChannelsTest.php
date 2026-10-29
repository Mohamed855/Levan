<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AsirReportData;
use App\Services\Tenants\Auctions\Reports\AuctionReportFormat;
use PHPUnit\Framework\TestCase;

final class AuctionReportChannelsTest extends TestCase
{
    public function test_evidence_section_captions_do_not_claim_that_every_property_was_marketed_without_proof(): void
    {
        $snapshot = ['auction' => ['name' => 'اختبار'], 'properties' => [], 'totals' => []];
        $binder = new AsirReportData(new AuctionReportFormat);
        $artwork = new \App\Services\Tenants\Auctions\Reports\AuctionReportArtwork(dirname(__DIR__, 3).'/resources/auction-reports');
        foreach ([['asir-board-installation-1', 7, 'board_context', ['f4', 'f5']], ['asir-board-installation-2', 8, 'board_context', ['f4', 'f5']], ['asir-x', 12, 'x_context', ['f5']], ['asir-social', 14, 'social_context', ['f5']]] as [$type, $index, $role, $fields]) {
            $content = $binder->page($snapshot, ['page_type' => $type, 'payload' => []]);
            self::assertArrayHasKey($role, $content['values']);
            self::assertSame($fields, $artwork->page('asir', $index)['groups'][$role]);
            self::assertStringNotContainsString('تم ', $content['values'][$role]);
            self::assertStringNotContainsString('جميع', $content['values'][$role]);
        }
    }

    public function test_whatsapp_table_and_chart_share_the_aggregate_of_all_campaigns(): void
    {
        $snapshot = ['auction' => ['name' => 'اختبار'], 'properties' => [], 'totals' => [], 'marketing' => ['campaigns' => [
            ['platform' => 'whatsapp', 'delivered' => 70, 'failed' => 20, 'pending' => 10, 'total' => 100],
            ['platform' => 'واتساب', 'delivered' => 10, 'failed' => 80, 'pending' => 10, 'total' => 100],
        ]]];
        $content = (new AsirReportData(new AuctionReportFormat))->page($snapshot, ['page_type' => 'asir-whatsapp', 'payload' => []]);
        self::assertSame('80', $content['values']['whatsapp_delivered']);
        self::assertSame('100', $content['values']['whatsapp_failed']);
        self::assertSame('20', $content['values']['whatsapp_pending']);
        self::assertSame('200', $content['values']['whatsapp_total']);
        self::assertStringContainsString('data-chart-state="delivered" data-percent="40"', $content['artwork_overrides']['charts'][0]);
    }

    public function test_social_total_covers_only_native_channels_and_never_treats_missing_counts_as_zero(): void
    {
        $snapshot = ['auction' => ['name' => 'اختبار'], 'properties' => [], 'totals' => [], 'marketing' => ['campaigns' => [
            ['platform' => 'snapchat', 'impressions' => null], ['platform' => 'tiktok', 'impressions' => null],
        ]]];
        $binder = new AsirReportData(new AuctionReportFormat);
        $page = ['page_type' => 'asir-social', 'payload' => []];
        self::assertSame('-', $binder->page($snapshot, $page)['values']['social_total']);
        $snapshot['marketing']['campaigns'][0]['impressions'] = 10;
        self::assertSame('-', $binder->page($snapshot, $page)['values']['social_total']);
        $snapshot['marketing']['campaigns'][1]['impressions'] = 0;
        $snapshot['marketing']['campaigns'][] = ['platform' => 'youtube', 'impressions' => 999];
        self::assertSame('١٠', $binder->page($snapshot, $page)['values']['social_total']);
    }

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
        $artwork = new \App\Services\Tenants\Auctions\Reports\AuctionReportArtwork(dirname(__DIR__, 3).'/resources/auction-reports');
        $document = new \DOMDocument;
        $document->loadXML($artwork->render('asir', 14, $content['values'], [], [], 15));
        $xpath = new \DOMXPath($document);
        self::assertLessThan(300, (float) $xpath->query('//*[@data-report-field="campaign_1_views"]')->item(0)->getAttribute('x'), 'Snapchat belongs to the left reference phone.');
        self::assertGreaterThan(350, (float) $xpath->query('//*[@data-report-field="campaign_2_views"]')->item(0)->getAttribute('x'), 'TikTok belongs to the right reference phone.');
        self::assertSame(0, $artwork->page('asir', 14)['image_defs']['i14']['slot']);
    }
}
