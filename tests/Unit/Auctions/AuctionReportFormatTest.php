<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportFormat;
use Tests\TestCase;

final class AuctionReportFormatTest extends TestCase
{
    public function test_it_formats_reference_bound_values_without_conflating_zero_and_missing_values(): void
    {
        $format = app(AuctionReportFormat::class);

        self::assertSame('-', $format->number(null));
        self::assertSame('0', $format->number(0));
        self::assertSame('10,200,000', $format->number(10200000));
        self::assertSame('10,200,000.00', $format->currency(10200000));
    }

    public function test_it_formats_iso_dates_and_missing_date_time_values_deterministically(): void
    {
        $format = app(AuctionReportFormat::class);

        self::assertSame('26 أكتوبر 2026', $format->date('2026-10-26'));
        self::assertSame('26 أكتوبر 2026 - 10:00', $format->dateTime('2026-10-26', '10:00'));
        self::assertSame('-', $format->date(null));
        self::assertSame('-', $format->dateTime(null, null));
    }
}
