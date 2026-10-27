<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportFormat;
use PHPUnit\Framework\TestCase;

final class AuctionReportFormatTest extends TestCase
{
    public function test_it_formats_reference_bound_values_without_conflating_zero_and_missing_values(): void
    {
        $format = new AuctionReportFormat;

        self::assertSame('-', $format->number(null));
        self::assertSame('0', $format->number(0));
        self::assertSame('10,200,000', $format->number(10200000));
        self::assertSame('10,200,000.00', $format->currency(10200000));
    }

    public function test_it_formats_iso_dates_and_missing_date_time_values_deterministically(): void
    {
        $format = new AuctionReportFormat;

        self::assertSame('26 أكتوبر 2026', $format->date('2026-10-26'));
        self::assertSame('26 أكتوبر 2026 - 10:00', $format->dateTime('2026-10-26', '10:00'));
        self::assertSame('-', $format->date(null));
        self::assertSame('-', $format->dateTime(null, null));
    }

    public function test_it_formats_um_al_qura_dates_and_arabic_digits_for_the_asir_date_cards(): void
    {
        $format = new AuctionReportFormat;
        self::assertSame('١٠,٢٠٠,٠٠٠', $format->arabicDigits('10,200,000'));
        self::assertSame('١٤٤٧/١٢/٠٢', $format->hijriDate('2026-05-19'));
        self::assertSame('-', $format->hijriDate(null));
        self::assertSame('الثلاثاء', $format->dateParts('2026-05-19')['weekday']);
    }
}
