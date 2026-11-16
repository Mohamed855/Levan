<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;

final class HaramNativeContentTest extends TestCase
{
    private function snapshot(): array
    {
        $snapshot = require __DIR__.'/../../Fixtures/AuctionReports/haram.php';
        $snapshot['cycle_version'] = 1;
        $snapshot['closing_saved'] = true;
        $snapshot['marketing']['results_narrative'] = 'نتائج البيع المسجلة';
        $snapshot['marketing']['campaigns'] = [
            ['id' => 1, 'platform' => 'x', 'impressions' => 0, 'url' => 'https://example.com/x'],
            ['id' => 2, 'platform' => 'tiktok', 'views' => 290000, 'reach' => 195000, 'profile_views' => 0],
            ['id' => 3, 'platform' => 'snapchat', 'impressions' => 510000],
            ['id' => 4, 'platform' => 'brokers', 'display_name' => '<b>وسيط</b>'],
        ];
        $image = ['id' => 10, 'uri' => 'data:image/svg+xml;base64,'.base64_encode(file_get_contents(resource_path('auction-reports/fallback.svg'))), 'fit' => 'contain'];
        $snapshot['evidence'] = [
            'x' => [array_replace($image, ['campaign_id' => 1, 'channel' => 'x'])],
            'social' => [array_replace($image, ['id' => 11, 'campaign_id' => 3, 'channel' => 'snapchat'])],
            'board_before' => [$image], 'board_after' => [$image],
            'brokers' => [array_replace($image, ['campaign_id' => 4, 'display_name' => '<b>وسيط</b>'])],
        ];

        return $snapshot;
    }

    private function document(array $snapshot): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.app(AuctionReportRenderer::class)->html($snapshot, 'outputs'));

        return new DOMXPath($document);
    }

    public function test_every_internal_page_uses_native_headings_tables_and_image_frames(): void
    {
        $xpath = $this->document($this->snapshot());
        $pages = $xpath->query('//*[@data-page-type and @data-page-type!="haram-cover" and @data-page-type!="haram-closing"]');
        self::assertGreaterThanOrEqual(5, $pages->length);
        foreach ($pages as $page) {
            self::assertSame(0, $xpath->query('.//*[local-name()="svg"]|.//canvas|.//object', $page)->length, $page->getAttribute('data-page-type'));
            self::assertSame(1, $xpath->query('.//header/h1', $page)->length);
            self::assertSame(1, $xpath->query('.//footer', $page)->length);
        }
        self::assertSame(4, $xpath->query('//table[@data-native-result-table="sold"]/tbody/tr')->length);
        self::assertSame(7, $xpath->query('//table[@data-native-result-table="unsold"]/tbody/tr')->length);
        self::assertSame(1, $xpath->query('//*[@data-report-field="x_impressions" and text()="0"]')->length);
        self::assertSame(1, $xpath->query('//a[@data-link-role="x" and @href="https://example.com/x"]')->length);
        self::assertStringContainsString('<b>وسيط</b>', $xpath->query('//*[@data-native-brokers]')->item(0)->textContent);
        self::assertSame(0, $xpath->query('//*[@data-native-brokers]//b')->length);
    }

    public function test_absent_metrics_reasons_images_and_unsafe_links_do_not_leave_empty_sections(): void
    {
        $snapshot = $this->snapshot();
        $snapshot['properties'] = array_slice($snapshot['properties'], 0, 1);
        $snapshot['properties'][0]['result_status'] = 'unsold';
        $snapshot['properties'][0]['unsold_reason'] = '';
        $snapshot['marketing']['campaigns'] = [['id' => 1, 'platform' => 'x', 'views' => 0, 'url' => 'javascript:alert(1)']];
        $snapshot['evidence'] = [];
        $xpath = $this->document($snapshot);
        self::assertSame(0, $xpath->query('//*[@data-result-section="unsold"]')->length);
        self::assertSame(0, $xpath->query('//*[@data-haram-channel="snapchat" or @data-haram-channel="tiktok"]')->length);
        self::assertSame(0, $xpath->query('//a[starts-with(@href,"javascript:")]')->length);
        self::assertSame(0, $xpath->query('//*[@data-native-marketing-image]')->length);
        self::assertSame(1, $xpath->query('//*[@data-report-field="x_views" and text()="0"]')->length);
    }

    public function test_evidence_appendices_also_use_native_captions_and_keep_document_page_numbers(): void
    {
        $snapshot = $this->snapshot();
        unset($snapshot['cycle_version']);
        $snapshot['evidence']['brochure'] = [['id' => 20, 'display_name' => 'ملف الأدلة', 'document_page' => 2, 'uri' => null]];
        $xpath = $this->document($snapshot);
        $page = $xpath->query('//*[contains(@data-page-type,"marketing-evidence-continuation")]')->item(0);
        self::assertNotNull($page);
        self::assertSame(0, $xpath->query('.//*[local-name()="svg"]', $page)->length);
        self::assertStringContainsString('ملف الأدلة - 2', $page->textContent);
    }
}
