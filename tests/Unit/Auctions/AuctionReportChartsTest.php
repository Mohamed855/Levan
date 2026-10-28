<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportCharts;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AuctionReportChartsTest extends TestCase
{
    public function test_delivery_slices_use_actual_counts_and_keep_the_reference_chart_geometry(): void
    {
        $svg = (new AuctionReportCharts)->whatsapp(['delivered' => 70, 'failed' => 20, 'pending' => 10, 'total' => 100]);
        $document = new \DOMDocument;
        self::assertTrue($document->loadXML($svg));
        $xpath = new \DOMXPath($document);
        foreach (['delivered' => '70', 'failed' => '20', 'pending' => '10'] as $key => $percent) {
            self::assertSame($percent, $xpath->query('//*[@data-chart-state="'.$key.'"]')->item(0)->getAttribute('data-percent'));
        }
        self::assertSame('0 0 595.276 841.89', $document->documentElement->getAttribute('viewBox'));
    }

    public function test_missing_or_zero_statistics_do_not_display_reference_results(): void
    {
        self::assertNull((new AuctionReportCharts)->whatsapp([]));
        self::assertNull((new AuctionReportCharts)->whatsapp(['delivered' => 0, 'failed' => 0, 'pending' => 0, 'total' => 0]));
        self::assertNull((new AuctionReportCharts)->whatsapp(['delivered' => 70, 'failed' => null, 'pending' => 0]));
    }

    public function test_conflicting_total_is_rejected_instead_of_drawing_a_misleading_chart(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AuctionReportCharts)->whatsapp(['delivered' => 70, 'failed' => 20, 'pending' => 10, 'total' => 101]);
    }
}
