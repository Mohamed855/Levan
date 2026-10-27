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
        self::assertSame('start', $xpath->query('//*[@data-report-field="name"]')->item(0)->getAttribute('text-anchor'));
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
}
