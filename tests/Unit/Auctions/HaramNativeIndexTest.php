<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;

final class HaramNativeIndexTest extends TestCase
{
    public function test_index_is_native_html_with_original_sections_and_only_actual_property_rows(): void
    {
        $snapshot = require __DIR__.'/../../Fixtures/AuctionReports/haram.php';
        $snapshot['cycle_version'] = 1;
        $snapshot['properties'] = array_slice($snapshot['properties'], 0, 2);
        $snapshot['auction']['start_time'] = '10:00';
        $snapshot['auction']['end_time'] = '17:00';
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.app(AuctionReportRenderer::class)->html($snapshot, 'outputs'));
        $xpath = new DOMXPath($document);
        $page = $xpath->query('//*[@data-page-type="haram-index"]')->item(0);
        self::assertNotNull($page);
        self::assertSame(0, $xpath->query('.//*[local-name()="svg"]|.//canvas|.//object', $page)->length);
        self::assertSame(3, $xpath->query('.//h2', $page)->length);
        self::assertSame(2, $xpath->query('.//table[@data-native-property-table]/tbody/tr', $page)->length);
        self::assertSame(5, $xpath->query('.//table[@data-native-property-table]/thead/tr/th', $page)->length);
        self::assertSame(2, $xpath->query('.//*[@data-haram-property-badge]', $page)->length);
        self::assertStringContainsString('10:00 ص', $page->textContent);
        self::assertStringContainsString('05:00 م', $page->textContent);
    }

    public function test_native_property_values_remain_escaped_and_keep_their_order(): void
    {
        $snapshot = require __DIR__.'/../../Fixtures/AuctionReports/haram.php';
        $snapshot['properties'] = array_slice($snapshot['properties'], 0, 2);
        $snapshot['properties'][0]['number'] = '<script>property</script>';
        $snapshot['properties'][1]['number'] = 'PROP-SECOND';
        $html = app(AuctionReportRenderer::class)->html($snapshot, 'outputs');
        self::assertStringContainsString('&lt;script&gt;property&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>property</script>', $html);
        self::assertLessThan(strpos($html, 'PROP-SECOND'), strpos($html, '&lt;script&gt;property'));
    }
}
