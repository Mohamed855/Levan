<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use Tests\TestCase;

final class AuctionReportRendererTest extends TestCase
{
    public function test_unsold_only_result_continuation_has_no_repeated_sold_table_and_starts_near_the_top(): void
    {
        $snapshot = require dirname(__DIR__, 2).'/Fixtures/AuctionReports/asir.php';
        $html = app(AuctionReportRenderer::class)->html($snapshot, 'outputs');
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $page = $xpath->query('//*[@data-page-type="haram-results-continuation"]')->item(0);
        self::assertNotNull($page);
        self::assertSame(0, $xpath->query('.//*[@data-report-cell and starts-with(@data-report-cell,"sold:")]', $page)->length);
        self::assertSame(1, $xpath->query('.//*[@data-result-section="unsold"]', $page)->length, 'A one-section continuation needs its own table geometry.');
        $cell = $xpath->query('.//*[@data-report-cell="unsold:0:0"]', $page)->item(0);
        self::assertLessThan(200, (float) $cell->getAttribute('y'));
    }
    public function test_long_subtitle_status_and_seven_digit_campaign_values_remain_atomic(): void
    {
        $snapshot = require dirname(__DIR__, 2).'/Fixtures/AuctionReports/asir.php';
        $snapshot['auction']['status'] = 'pending_approval';
        $snapshot['marketing']['contacts'] = ['phone' => '0534811800', 'unified_phone' => '920000000', 'social' => 'https://example.com/social'];
        $snapshot['marketing']['campaigns'] = [['platform' => 'tiktok', 'impressions' => 1000006], ['platform' => 'snapchat', 'impressions' => 78000], ['platform' => 'x', 'impressions' => 1547]];
        $renderer = app(AuctionReportRenderer::class);
        foreach (['outputs', 'report'] as $type) {
            $html = $renderer->html($snapshot, $type);
            foreach (['cover', 'marketing', 'platform', 'social', 'closing'] as $role) {
                self::assertFalse(str_contains($html, '-'.$role.'-continuation"'), 'A scalar '.$role.' value duplicated its page in '.$type);
            }
            self::assertTrue(str_contains($html, '>1,000,006</tspan>'), 'A seven-digit metric was broken into separate text chunks.');
        }
    }
    public function test_unknown_statistics_never_reuse_the_reference_pie_and_metrics_stay_on_one_page(): void
    {
        $snapshot = require dirname(__DIR__, 2).'/Fixtures/AuctionReports/asir.php';
        $renderer = app(AuctionReportRenderer::class);
        $html = $renderer->html($snapshot, 'report');
        self::assertFalse(str_contains($html, 'clip-path="url(#report_12_p13_clip_32)"'));
        self::assertTrue(str_contains($html, 'data-report-empty-statistics'));
        $snapshot['marketing']['campaigns'] = [['platform' => 'whatsapp', 'messages' => 100000, 'views' => 78000, 'delivered' => 70000, 'failed' => 20000, 'pending' => 10000, 'total' => 100000]];
        $html = $renderer->html($snapshot, 'report');
        self::assertSame(19, substr_count($html, 'data-page-type='), 'Atomic campaign statistics must not create repeated template pages.');
        self::assertTrue(str_contains($html, 'data-report-chart="whatsapp"'));
        self::assertTrue(str_contains($html, 'data-percent="70"'));
    }
    public function test_every_channel_image_reaches_the_document_when_its_capacity_is_exceeded(): void
    {
        $snapshot = require dirname(__DIR__, 2).'/Fixtures/AuctionReports/asir.php';
        $uris = [];
        $index = 0;
        foreach (['platform' => 3, 'video' => 4, 'x' => 8, 'whatsapp' => 2, 'brochure' => 25, 'social' => 4, 'brokers' => 2, 'board_before' => 14, 'board_after' => 14] as $section => $count) {
            for ($slot = 0; $slot < $count; $slot++) {
                $bitmap = imagecreatetruecolor(1, 1);
                imagesetpixel($bitmap, 0, 0, imagecolorallocate($bitmap, ++$index, 20, 30));
                ob_start();
                imagepng($bitmap);
                $uri = 'data:image/png;base64,'.base64_encode(ob_get_clean());
                imagedestroy($bitmap);
                $uris[] = $uri;
                $snapshot['evidence'][$section][] = ['uri' => $uri, 'name' => $section.'-'.$slot];
            }
        }
        $html = app(AuctionReportRenderer::class)->html($snapshot, 'report');
        foreach ($uris as $uri) {
            self::assertStringContainsString($uri, $html, 'A classified evidence image disappeared.');
        }
    }

    public function test_reference_capacity_fixtures_render_as_seven_and_twenty_one_pages(): void
    {
        $renderer = app(AuctionReportRenderer::class);
        foreach (['haram' => ['outputs', 7], 'asir' => ['report', 19]] as $fixture => [$type, $count]) {
            $snapshot = require dirname(__DIR__, 2).'/Fixtures/AuctionReports/'.$fixture.'.php';
            $html = $renderer->html($snapshot, $type);
            self::assertSame($count, substr_count($html, 'data-page-type='), $fixture);
            self::assertFalse(str_contains($html, '__DYNAMIC_IMAGE__'));
            self::assertFalse(str_contains($html, '__ASSET_'));
            self::assertFalse(str_contains($html, 'saved-data'));
        }
    }

    public function test_renderer_uses_blade_data_without_reference_engine_or_sample_values(): void
    {
        $snapshot = [
            'auction' => [
                'id' => 99, 'name' => 'اسم مزاد حقيقي طويل دون قص', 'description' => 'وصف حقيقي',
                'city' => 'جدة', 'type' => 'إلكتروني', 'category' => 'أراضي',
                'start_date' => '2026-10-26', 'start_time' => '10:00', 'end_date' => '2026-10-27', 'end_time' => '18:00',
                'supervising_entity' => 'جهة فعلية', 'project_manager_name' => 'مدير فعلي',
                'platform_name' => 'منصة فعلية', 'platform_url' => null,
                'cover_image' => 'data:image/png;base64,COVER', 'report_image' => 'data:image/png;base64,REPORT',
            ],
            'assignment' => ['number' => 'A-1', 'name' => 'إسناد فعلي', 'issuing_entity' => 'جهة فعلية'],
            'properties' => [[
                'id' => 1, 'number' => 'P-1', 'name' => 'عقار حقيقي', 'type' => 'أرض', 'city' => 'جدة',
                'district' => 'الشاطئ', 'area' => 500.0, 'deed_number' => 'DEED-REAL', 'estimated_value' => null,
                'images' => [], 'result_status' => 'sold', 'opening_price' => 9000000.0,
                'sale_price' => 10200000.0, 'participants_count' => 7, 'bids_count' => 13,
                'unsold_reason' => null, 'board' => [],
            ]],
            'marketing' => ['campaigns' => []],
            'evidence' => ['marketing_evidence' => [], 'board_before' => [], 'board_after' => []],
            'totals' => ['properties_count' => 1, 'sold_count' => 1, 'unsold_count' => 0, 'pending_count' => 0, 'recorded_sales_total' => 10200000.0, 'participants_count' => 7, 'bids_count' => 13],
        ];

        $renderer = app(AuctionReportRenderer::class);
        $report = $renderer->html($snapshot, 'report');
        $outputs = $renderer->html($snapshot, 'outputs');

        self::assertStringContainsString('data-page-type="haram-cover"', $outputs);
        self::assertStringContainsString('data-page-type="haram-index"', $outputs);
        self::assertStringContainsString('data-page-type="haram-closing"', $outputs);
        self::assertGreaterThanOrEqual(7, substr_count($outputs, 'data-page-type='));
        self::assertStringContainsString('NotoReport', $outputs);
        self::assertTrue(str_contains($report, 'data-page-type="asir-cover"'));
        self::assertGreaterThanOrEqual(19, substr_count($report, 'data-page-type='));
        self::assertTrue(str_contains($report, 'GEReport'));

        self::assertStringContainsString('اسم مزاد حقيقي طويل دون قص', $report);
        self::assertStringContainsString('DEED-REAL', $report);
        self::assertStringContainsString('10,200,000', $report);
        self::assertStringContainsString('data:image/png;base64,REPORT', $report);
        self::assertStringNotContainsString('data:image/png;base64,COVER', $report);
        self::assertStringNotContainsString('saved-data', $report.$outputs);
        self::assertStringNotContainsString('4,026,000', $report.$outputs);
        self::assertStringNotContainsString('واجهة عسير', $report.$outputs);
        self::assertStringNotContainsString('لمزاد مزاد', $report.$outputs);

        $property = $snapshot['properties'][0];
        $snapshot['properties'] = [];
        for ($index = 1; $index <= 17; $index++) {
            $snapshot['properties'][] = array_replace($property, ['number' => 'P-'.$index]);
        }
        $snapshot['marketing']['campaigns'] = [];
        for ($index = 1; $index <= 25; $index++) {
            $snapshot['marketing']['campaigns'][] = ['platform' => 'Campaign-'.$index];
        }

        $outputs = $renderer->html($snapshot, 'outputs');
        $report = $renderer->html($snapshot, 'report');
        self::assertStringContainsString('data-page-type="haram-index-continuation"', $outputs);
        self::assertTrue(str_contains($report, 'data-page-type="asir-campaigns-continuation"'), 'Extra campaigns must render in a campaign appendix.');
        self::assertStringContainsString('P-17', $outputs);
        self::assertStringContainsString('Campaign-25', $report);
    }
}
