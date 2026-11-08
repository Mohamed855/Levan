<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AsirReportData;
use App\Services\Tenants\Auctions\Reports\AuctionReportArtwork;
use App\Services\Tenants\Auctions\Reports\AuctionReportFormat;
use App\Services\Tenants\Auctions\Reports\AuctionReportPaginator;
use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use Tests\TestCase;

final class AuctionReportNativeCycleTest extends TestCase
{
    public function test_rendered_pdf_pages_keep_native_fields_and_per_campaign_links(): void
    {
        $snapshot = $this->snapshot([['id' => 1, 'platform' => 'x', 'views' => 0, 'url' => 'https://example.com/one'], ['id' => 2, 'platform' => 'x', 'views' => 7, 'url' => 'https://example.com/two']]);
        $html = app(AuctionReportRenderer::class)->html($snapshot, 'report');
        self::assertStringNotContainsString('data-report-field="campaign_heading"', $html);
        self::assertStringNotContainsString('data-report-field="closing_details"', $html);
        $document = new \DOMDocument;
        $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $pages = (new \DOMXPath($document))->query('//section[@data-page-type="asir-x"]');
        self::assertSame(2, $pages->length);
        self::assertStringContainsString('https://example.com/one', $document->saveHTML($pages->item(0)));
        self::assertStringNotContainsString('https://example.com/two', $document->saveHTML($pages->item(0)));
        self::assertStringContainsString('https://example.com/two', $document->saveHTML($pages->item(1)));
        $snapshot = $this->snapshot([['id' => 3, 'platform' => 'youtube', 'views' => 0, 'subscribers' => 3]]);
        $html = app(AuctionReportRenderer::class)->html($snapshot, 'report');
        self::assertStringContainsString('data-report-field="youtube_views"', $html);
        self::assertStringNotContainsString('data-report-field="youtube_watch_hours"', $html);
        self::assertStringNotContainsString('data-report-empty-evidence=', $html);
    }

    public function test_native_platform_and_video_replace_the_issuing_entity_logos_in_their_original_positions(): void
    {
        $branding = ['company_logo' => 'data:image/png;base64,Y29tcGFueQ==', 'issuing_entity_logo' => 'data:image/png;base64,ZW50aXR5'];
        $artwork = app(AuctionReportArtwork::class);
        foreach ([10, 11] as $index) {
            $svg = $artwork->render('asir', $index, [], [], [], $index + 1, [], [], ['branding' => $branding]);
            self::assertStringContainsString('data-report-branding="issuing_entity_logo"', $svg);
            self::assertStringContainsString('xlink:href="'.$branding['issuing_entity_logo'].'"', $svg);
        }
    }

    public function test_partial_social_campaign_keeps_all_evidence_in_repeated_native_pages(): void
    {
        $snapshot = $this->snapshot([['id' => 8, 'platform' => 'snapchat', 'views' => 0]]);
        $snapshot['evidence']['social'] = [['id' => 81, 'campaign_id' => 8, 'channel' => 'snapchat', 'uri' => null], ['id' => 82, 'campaign_id' => 8, 'channel' => 'snapchat', 'uri' => null]];
        $pages = array_values(array_filter(app(AuctionReportPaginator::class)->paginate($snapshot, 'report'), fn ($page) => $page['page_type'] === 'asir-social'));
        self::assertCount(2, $pages);
        self::assertSame(81, $pages[0]['payload']['social'][0]['id']);
        self::assertSame(82, $pages[1]['payload']['social'][0]['id']);
        $content = (new AsirReportData(new AuctionReportFormat))->page($snapshot, $pages[0]);
        self::assertSame('0', $content['values']['campaign_1_views']);
    }

    public function test_brochure_role_uses_campaign_owned_files_in_the_native_brochure_page(): void
    {
        $snapshot = $this->snapshot([['id' => 8, 'platform' => 'other', 'platform_label' => 'كتيب', 'link_role' => 'brochure', 'url' => 'https://example.com/brochure']]);
        $snapshot['evidence']['marketing_evidence'] = [['id' => 81, 'campaign_id' => 8, 'channel' => 'other', 'uri' => null]];
        $pages = array_values(array_filter(app(AuctionReportPaginator::class)->paginate($snapshot, 'report'), fn ($page) => $page['page_type'] === 'asir-brochure'));
        self::assertCount(1, $pages);
        self::assertSame(81, $pages[0]['payload']['brochure'][0]['id']);
    }

    public function test_legacy_brochure_does_not_replace_or_mix_with_campaign_owned_files(): void
    {
        $snapshot = $this->snapshot([['id' => 8, 'platform' => 'x', 'views' => 0, 'link_role' => 'brochure']]);
        $snapshot['evidence']['brochure'] = [['id' => 21, 'uri' => null]];
        $snapshot['evidence']['x'] = [['id' => 22, 'campaign_id' => 8, 'uri' => null]];
        $pages = array_values(array_filter(app(AuctionReportPaginator::class)->paginate($snapshot, 'report'), fn ($page) => $page['page_type'] === 'asir-brochure'));
        self::assertCount(2, $pages);
        self::assertSame(22, $pages[0]['payload']['brochure'][0]['id']);
        self::assertSame(21, $pages[1]['payload']['brochure'][0]['id']);
        self::assertSame([], $pages[1]['payload']['campaigns']);
    }

    public function test_unrecorded_result_cells_are_blank_in_the_native_table(): void
    {
        $snapshot = $this->snapshot([]);
        $snapshot['closing_saved'] = true;
        $snapshot['properties'][0]['opening_price'] = null;
        $snapshot['properties'][0]['participants_count'] = null;
        $pages = array_values(array_filter(app(AuctionReportPaginator::class)->paginate($snapshot, 'report'), fn ($page) => $page['page_type'] === 'asir-sold-assets'));
        $content = (new AsirReportData(new AuctionReportFormat))->page($snapshot, $pages[0]);
        self::assertSame('', $content['tables']['sales'][0][1]);
        self::assertSame('', $content['tables']['sales'][0][3]);
    }

    private function snapshot(array $campaigns): array
    {
        $snapshot = require base_path('tests/Fixtures/AuctionReports/haram.php');
        $snapshot['cycle_version'] = 1;
        $snapshot['closing_saved'] = false;
        $snapshot['marketing']['campaigns'] = $campaigns;

        return $snapshot;
    }

    public function test_saved_campaigns_use_native_pages_and_keep_repeated_campaigns_separate(): void
    {
        $snapshot = $this->snapshot([['id' => 1, 'platform' => 'x', 'views' => 0, 'impressions' => 0, 'url' => 'https://example.com/one'], ['id' => 2, 'platform' => 'x', 'views' => 7, 'impressions' => 9, 'url' => 'https://example.com/two']]);
        $paginator = app(AuctionReportPaginator::class);
        $outputs = $paginator->paginate($snapshot, 'outputs');
        self::assertCount(2, array_filter($outputs, fn ($page) => $page['page_type'] === 'haram-marketing'));
        $report = $paginator->paginate($snapshot, 'report');
        $pages = array_values(array_filter($report, fn ($page) => $page['page_type'] === 'asir-x'));
        self::assertCount(2, $pages);
        foreach (array_merge($outputs, $report) as $page) {
            self::assertArrayNotHasKey('gallery', $page['payload']);
            self::assertStringNotContainsString('closing-details', $page['page_type']);
        }
        self::assertSame(1, $pages[0]['payload']['campaigns'][0]['id']);
        self::assertSame(2, $pages[1]['payload']['campaigns'][0]['id']);
    }

    public function test_partial_native_video_page_shows_recorded_metrics_without_missing_value_placeholders(): void
    {
        $snapshot = $this->snapshot([['id' => 1, 'platform' => 'youtube', 'views' => 0, 'subscribers' => 3]]);
        $pages = app(AuctionReportPaginator::class)->paginate($snapshot, 'report');
        $video = array_values(array_filter($pages, fn ($page) => $page['page_type'] === 'asir-video'));
        self::assertCount(1, $video);
        $content = (new AsirReportData(new AuctionReportFormat))->page($snapshot, $video[0]);
        self::assertSame(11, $content['index']);
        self::assertSame('0', $content['values']['youtube_views']);
        self::assertSame('3', $content['values']['youtube_subscribers']);
        self::assertSame('', $content['values']['youtube_watch_hours']);
        self::assertSame('', $content['values']['video_url']);
    }
}
