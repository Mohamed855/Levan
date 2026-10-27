<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use Tests\TestCase;

final class AuctionReportRendererTest extends TestCase
{
    public function test_reference_capacity_fixtures_render_as_seven_and_twenty_one_pages(): void
    {
        $renderer = app(AuctionReportRenderer::class);
        foreach (['haram' => ['outputs', 7], 'asir' => ['report', 21]] as $fixture => [$type, $count]) {
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
        self::assertGreaterThanOrEqual(21, substr_count($report, 'data-page-type='));
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
        self::assertStringContainsString('data-page-type="asir-social-continuation"', $report);
        self::assertStringContainsString('P-17', $outputs);
        self::assertStringContainsString('Campaign-25', $report);
    }
}
