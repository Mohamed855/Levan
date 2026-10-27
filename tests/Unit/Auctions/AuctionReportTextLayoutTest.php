<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportTextLayout;
use PHPUnit\Framework\TestCase;

final class AuctionReportTextLayoutTest extends TestCase
{
    public function test_long_text_is_wrapped_without_truncating_words_or_changing_the_font_size(): void
    {
        $layout = new AuctionReportTextLayout(dirname(__DIR__, 3).'/resources/auction-reports');
        $value = 'مزاد عقاري طويل الاسم لاختبار عرض البيانات كاملة داخل التصميم الحكومي';
        $lines = $layout->wrap($value, 180, 40, 'NotoSansArabic-Bold');
        self::assertGreaterThan(1, count($lines));
        self::assertSame($value, implode(' ', $lines));
    }

    public function test_an_overlong_identifier_is_preserved_when_it_has_no_word_breaks(): void
    {
        $layout = new AuctionReportTextLayout(dirname(__DIR__, 3).'/resources/auction-reports');
        $value = str_repeat('0123456789', 10);
        $lines = $layout->wrap($value, 60, 14, 'NizarBBCKurdish');
        self::assertGreaterThan(1, count($lines));
        self::assertSame($value, implode('', $lines));
    }
}
