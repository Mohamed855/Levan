<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AsirNativeContent;
use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;

final class AsirNativeContentTest extends TestCase
{
    public function test_empty_narrative_fields_do_not_restore_template_copy_or_binder_defaults(): void
    {
        foreach ([3, 4, 5, 6] as $index) {
            $content = app(AsirNativeContent::class)->prepare([
                'index' => $index,
                'values' => ['name_context' => 'عنوان افتراضي من اسم المزاد', 'planning_methodology' => 'نص افتراضي'],
            ], ['marketing' => []], ['payload' => []]);
            self::assertSame([], $content['paragraphs']);
        }
    }

    public function test_narrative_contains_only_recorded_text_without_original_template_claims(): void
    {
        foreach ([0, 1] as $version) {
            $snapshot = (require __DIR__.'/../../Fixtures/AuctionReports/asir-native.php')('populated');
            $snapshot['cycle_version'] = $version;
            $snapshot['marketing'] = [
                'city_context' => 'النطاق الجغرافي المسجل فقط',
                'planning_methodology' => 'خطة المستخدم فقط',
                'brochure_methodology' => 'تصميم المستخدم فقط',
                'audience_methodology' => 'الفئة المسجلة فقط',
                'messages_methodology' => 'الرسالة المسجلة فقط',
                'campaigns' => [],
            ];
            $xpath = $this->document($snapshot);
            $paragraphs = $xpath->query('//*[contains(@class,"asir-narrative-main")]//*[@data-asir-paragraph]');
            self::assertSame(array_values(array_slice($snapshot['marketing'], 0, 5)), array_map(static fn ($node): string => trim($node->textContent), iterator_to_array($paragraphs)));
            self::assertSame(0, $xpath->query('//*[contains(@class,"asir-narrative-main")]//li')->length);
        }
    }

    public function test_campaign_narrative_does_not_invent_unfilled_design_fields_or_mix_campaigns(): void
    {
        $snapshot = (require __DIR__.'/../../Fixtures/AuctionReports/asir-native.php')('sparse');
        $snapshot['marketing'] = ['campaigns' => [
            ['id' => 71, 'platform' => 'brochure', 'brochure_methodology' => 'تصميم الحملة الأولى فقط'],
            ['id' => 72, 'platform' => 'brochure', 'brochure_methodology' => 'تصميم الحملة الثانية فقط'],
        ]];
        $xpath = $this->document($snapshot);
        $pages = $xpath->query('//*[@data-page-type="asir-design"]');
        self::assertSame(2, $pages->length);
        foreach ($pages as $index => $page) {
            $paragraphs = $xpath->query('.//*[@data-asir-paragraph]', $page);
            self::assertSame(1, $paragraphs->length);
            self::assertSame($snapshot['marketing']['campaigns'][$index]['brochure_methodology'], trim($paragraphs->item(0)->textContent));
        }
    }

    private function document(array $snapshot): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.app(AuctionReportRenderer::class)->html($snapshot, 'report'));

        return new DOMXPath($document);
    }

    public function test_internal_pages_are_html_and_both_covers_keep_their_artwork(): void
    {
        $snapshot = require __DIR__.'/../../Fixtures/AuctionReports/asir.php';
        $xpath = $this->document($snapshot);
        $pages = $xpath->query('//*[@data-page-type and @data-page-type!="asir-cover" and @data-page-type!="asir-closing"]');
        self::assertGreaterThanOrEqual(17, $pages->length);
        foreach ($pages as $page) {
            self::assertSame(0, $xpath->query('.//*[local-name()="svg"]|.//canvas|.//object', $page)->length, $page->getAttribute('data-page-type'));
            self::assertSame(1, $xpath->query('.//header/h1', $page)->length);
        }
        self::assertSame(2, $xpath->query('//*[@data-page-type="asir-cover" or @data-page-type="asir-closing"]/*[local-name()="svg"]')->length);
        self::assertSame(13, $xpath->query('//table[@data-asir-table="properties"]/tbody/tr')->length);
        self::assertSame(3, $xpath->query('//*[@data-asir-property]')->length);
        self::assertSame(10, $xpath->query('//table[@data-asir-table="unsold"]/tbody/tr')->length);
    }

    public function test_sparse_campaigns_keep_zero_and_escape_content_and_reject_unsafe_links(): void
    {
        $snapshot = require __DIR__.'/../../Fixtures/AuctionReports/asir.php';
        $snapshot['cycle_version'] = 1;
        $snapshot['closing_saved'] = false;
        $snapshot['auction']['description'] = '<script>alert(1)</script>';
        $snapshot['auction']['platform_name'] = '';
        $snapshot['marketing']['campaigns'] = [['id' => 1, 'platform' => 'x', 'views' => 0, 'url' => 'javascript:alert(2)']];
        $xpath = $this->document($snapshot);
        self::assertSame(0, $xpath->query('//*[@data-asir-flow]//script')->length);
        self::assertSame(0, $xpath->query('//a[starts-with(@href,"javascript:")]')->length);
        self::assertSame(0, $xpath->query('//*[@data-page-type="asir-sold-assets" or @data-page-type="asir-unsold-assets"]')->length);
        self::assertGreaterThan(0, $xpath->query('//*[@data-report-field="x_views" and text()="0"]')->length);
        self::assertSame(0, $xpath->query('//*[@data-asir-channel="snapchat" or @data-asir-channel="tiktok"]')->length);
        self::assertStringContainsString('<script>alert(1)</script>', $xpath->query('//*[@data-report-field="introduction"]')->item(0)->textContent);
    }

    public function test_native_evidence_and_links_preserve_metadata(): void
    {
        $snapshot = require __DIR__.'/../../Fixtures/AuctionReports/asir.php';
        $snapshot['cycle_version'] = 1;
        $snapshot['marketing']['campaigns'] = [['id' => 2, 'platform' => 'brokers', 'display_name' => '<b>وسيط</b>', 'url' => 'https://example.com/broker']];
        $snapshot['evidence']['brokers'] = [[
            'id' => 12, 'campaign_id' => 2, 'uri' => 'data:image/png;base64,iVBORw0KGgo=',
            'fit' => 'cover', 'focal_x' => 17, 'focal_y' => 83, 'display_name' => '<b>وثيقة</b>', 'document_page' => 2,
        ]];
        $xpath = $this->document($snapshot);
        $image = $xpath->query('//img[@data-report-attachment="12"]')->item(0);
        self::assertNotNull($image);
        self::assertStringContainsString('object-fit:cover', $image->getAttribute('style'));
        self::assertStringContainsString('17% 83%', $image->getAttribute('style'));
        self::assertSame(1, $xpath->query('//a[@href="https://example.com/broker"]')->length);
        self::assertSame(0, $xpath->query('//*[@data-asir-flow]//b')->length);
        self::assertStringContainsString('<b>وثيقة</b> - 2', $xpath->query('//*[@data-page-type="asir-brokers"]')->item(0)->textContent);
    }

    public function test_platform_duration_and_gallery_channel_captions_survive_conversion(): void
    {
        $snapshot = require __DIR__.'/../../Fixtures/AuctionReports/asir.php';
        $snapshot['evidence']['marketing_evidence'] = [[
            'id' => 44, 'channel' => 'tiktok', 'display_name' => 'وثيقة', 'document_page' => 2, 'uri' => null,
        ]];
        $xpath = $this->document($snapshot);
        self::assertSame(1, $xpath->query('//*[@data-report-field="duration"]')->length);
        $gallery = $xpath->query('//*[contains(@data-page-type,"marketing-evidence-continuation")]')->item(0);
        self::assertNotNull($gallery);
        self::assertStringContainsString('تيك توك - وثيقة - 2', $gallery->textContent);
        self::assertStringNotContainsString(' - 2 - 2', $gallery->textContent);
    }

    public function test_broker_letterhead_uses_the_current_company_logo_for_its_watermark(): void
    {
        $snapshot = require __DIR__.'/../../Fixtures/AuctionReports/asir.php';
        $logo = 'data:image/svg+xml;base64,'.base64_encode(file_get_contents(resource_path('auction-reports/fallback.svg')));
        $snapshot['branding'] = ['company_logo' => $logo, 'issuing_entity_logo' => null];
        $snapshot['evidence']['brokers'] = [['id' => 45, 'uri' => $logo]];
        $xpath = $this->document($snapshot);
        $watermark = $xpath->query('//*[@data-page-type="asir-brokers"]//img[@class="asir-broker-watermark-image"]')->item(0);
        self::assertNotNull($watermark);
        self::assertSame($logo, $watermark->getAttribute('src'));
    }
}
