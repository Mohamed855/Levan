<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportArtwork;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

final class AuctionReportArtworkTest extends TestCase
{
    public function test_dynamic_text_is_escaped_and_reference_business_values_are_removed_from_the_artwork(): void
    {
        $artwork = new AuctionReportArtwork(dirname(__DIR__, 3).'/resources/auction-reports');
        $svg = $artwork->render('haram', 0, ['name' => '<script>اسم جديد</script>', 'footer' => 'اختبار'], [], [], 1);
        self::assertStringContainsString('&lt;script&gt;اسم جديد&lt;/script&gt;', $svg);
        self::assertStringNotContainsString('<script>', $svg);
        self::assertStringNotContainsString('جوار الحرم', $svg);
        self::assertStringNotContainsString('data-text=', $svg);
        self::assertStringNotContainsString('__BACKGROUND__', $svg);
        $document = new DOMDocument;
        $document->loadXML($svg);
        $xpath = new DOMXPath($document);
        self::assertSame('middle', $xpath->query('//*[@data-report-field="name"]')->item(0)->getAttribute('text-anchor'));
        self::assertSame(0, $xpath->query('//*[@id and not(starts-with(@id,"report_1_"))]')->length);
    }

    public function test_table_cells_use_the_measured_column_positions_and_missing_images_use_the_platform_logo(): void
    {
        $artwork = new AuctionReportArtwork(dirname(__DIR__, 3).'/resources/auction-reports');
        $svg = $artwork->render('haram', 1, [], ['properties' => [['2×4', 'P-99', 'DEED-99', 'أرض', '1']]], [], 2);
        self::assertStringContainsString('P-99', $svg);
        self::assertStringContainsString('DEED-99', $svg);
        $document = new DOMDocument;
        self::assertTrue($document->loadXML($svg));
        $xpath = new DOMXPath($document);
        self::assertSame(5, $xpath->query('//*[@data-report-cell]')->length);
        $gallery = $artwork->render('haram', 4, [], [], [], 5);
        self::assertStringContainsString('data:image/svg+xml;base64,', $gallery);
        self::assertStringContainsString('xMidYMid meet', $gallery);
    }

    public function test_multiline_narrative_starts_at_the_original_first_baseline(): void
    {
        $artwork = new AuctionReportArtwork(dirname(__DIR__, 3).'/resources/auction-reports');
        $definition = $artwork->page('haram', 2);
        $expected = min(array_map(static fn (string $id): float => $definition['fields'][$id]['y'], $definition['groups']['narrative']));
        $document = new DOMDocument;
        $document->loadXML($artwork->render('haram', 2, ['narrative' => "السطر الأول\nالسطر الثاني"], [], [], 3));
        $node = (new DOMXPath($document))->query('//*[@data-report-field="narrative"]')->item(0);
        self::assertEqualsWithDelta($expected, (float) $node->getAttribute('y'), .001);
    }

    public function test_missing_asir_media_fits_the_visible_clipped_frame_instead_of_the_original_photo_extent(): void
    {
        $artwork = new AuctionReportArtwork(dirname(__DIR__, 3).'/resources/auction-reports');
        $document = new DOMDocument;
        $document->loadXML($artwork->render('asir', 8, [], [], [], 9));
        $node = (new DOMXPath($document))->query('//*[@data-report-contained="board_before:0"]')->item(0);
        self::assertNotNull($node);
        self::assertSame('xMidYMid meet', $node->getAttribute('preserveAspectRatio'));
        self::assertEqualsWithDelta(350.483, (float) $node->getAttribute('x'), .01);
        self::assertEqualsWithDelta(139.351, (float) $node->getAttribute('width'), .01);
    }

    public function test_document_pages_use_the_same_visible_frame_without_cropping(): void
    {
        $artwork = new AuctionReportArtwork(dirname(__DIR__, 3).'/resources/auction-reports');
        $uri = 'data:image/png;base64,'.base64_encode('document-page');
        $document = new DOMDocument;
        $document->loadXML($artwork->render('asir', 8, [], [], ['board_before' => [['uri' => $uri, 'fit' => 'contain']]], 9));
        $node = (new DOMXPath($document))->query('//*[@data-report-contained="board_before:0"]')->item(0);
        self::assertNotNull($node);
        self::assertSame($uri, $node->getAttributeNS('http://www.w3.org/1999/xlink', 'href'));
        self::assertSame('xMidYMid meet', $node->getAttribute('preserveAspectRatio'));
    }

    public function test_an_expanded_continuation_row_uses_the_table_body_without_clipping_the_text(): void
    {
        $root = dirname(__DIR__, 3).'/resources/auction-reports';
        $artwork = new AuctionReportArtwork($root);
        $definition = $artwork->page('haram', 1);
        $definition['groups'] = [];
        $parts = (new \App\Services\Tenants\Auctions\Reports\AuctionReportTextLayout($root))->flow(['values' => [], 'tables' => ['properties' => [['-', str_repeat("123456\n", 8), 'DEED', 'أرض', '1']]], 'images' => []], $definition);
        self::assertCount(1, $parts);
        $svg = $artwork->render('haram', 1, [], $parts[0]['tables'], [], 3, $parts[0]['table_layouts']);
        self::assertTrue(str_contains($svg, 'data-reflowed-table="properties"'));
        self::assertSame(8, substr_count($svg, '>123456</tspan>'));
    }
}
