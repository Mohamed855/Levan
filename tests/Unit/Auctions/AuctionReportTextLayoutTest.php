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

    public function test_an_expanded_row_keeps_its_position_between_the_preceding_and_following_records(): void
    {
        $root = dirname(__DIR__, 3).'/resources/auction-reports';
        $definition = (require $root.'/haram/pages.php')[1];
        $content = ['values' => [], 'images' => [], 'tables' => ['properties' => [
            ['-', 'A', '-', 'أرض', '1'],
            ['-', 'B', '-', str_repeat('عقار طويل الاسم ', 25), '2'],
            ['-', 'C', '-', 'أرض', '3'],
        ]]];
        $pages = (new AuctionReportTextLayout($root))->flow($content, $definition);
        $identifiers = [];
        foreach ($pages as $page) {
            $identifiers = array_merge($identifiers, array_column($page['tables']['properties'], 1));
        }
        self::assertSame(['A', 'B', 'C'], $identifiers);
        self::assertTrue($pages[1]['table_layouts']['properties']['expanded']);
    }

    public function test_reference_numeric_fonts_keep_two_digit_board_labels_on_one_line(): void
    {
        $layout = new AuctionReportTextLayout(dirname(__DIR__, 3).'/resources/auction-reports');
        self::assertSame(['١٣'], $layout->wrap('١٣', 22.78, 30.013, 'STV'));
    }

    public function test_the_reference_two_deed_row_fits_without_an_extra_page(): void
    {
        $root = dirname(__DIR__, 3).'/resources/auction-reports';
        $definition = (require $root.'/asir/pages.php')[1];
        $rows = array_fill(0, 3, ['-', '1', '286000', '٣٧١٧٠١٠٠٢٦٣١', 'أرض', '1']);
        $rows[2][3] = "٤٩٩٦٦١٠٠٥٦٣٥\n٦٩٩٦٦١٠٠٥٦٣١";
        $pages = (new AuctionReportTextLayout($root))->flow(['values' => [], 'images' => [], 'tables' => ['properties' => $rows]], $definition);
        self::assertCount(1, $pages);
        self::assertSame($rows[2][3], $pages[0]['tables']['properties'][2][3]);
    }
}
