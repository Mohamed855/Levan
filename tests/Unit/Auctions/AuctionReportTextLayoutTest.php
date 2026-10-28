<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportTextLayout;
use PHPUnit\Framework\TestCase;

final class AuctionReportTextLayoutTest extends TestCase
{
    public function test_conservative_wrap_width_keeps_the_original_field_geometry_and_full_text(): void
    {
        $layout = new AuctionReportTextLayout(dirname(__DIR__, 3).'/resources/auction-reports');
        $definition = ['groups' => ['body' => ['f1']], 'fields' => ['f1' => ['x' => 0.0, 'y' => 50.0, 'width' => 180.0, 'size' => 20.0, 'sy' => 20.0, 'font' => 'Arimo-Bold']], 'layouts' => ['body' => ['max_width' => 180, 'wrap_width' => 70, 'max_lines' => 4]], 'tables' => []];
        $parts = $layout->flow(['values' => ['body' => 'TEST TEST TEST TEST'], 'tables' => [], 'images' => []], $definition);
        self::assertCount(1, $parts);
        self::assertSame("TEST\nTEST\nTEST\nTEST", $parts[0]['values']['body']);
    }
    public function test_titles_shrink_only_when_needed_and_never_continue_to_another_page(): void
    {
        $layout = new AuctionReportTextLayout(dirname(__DIR__, 3).'/resources/auction-reports');
        $definition = [
            'groups' => ['title' => ['f1']],
            'fields' => ['f1' => ['x' => 0.0, 'y' => 50.0, 'width' => 220.0, 'size' => 40.0, 'sy' => 40.0, 'font' => 'Arimo-Bold']],
            'layouts' => ['title' => ['box' => [0, 0, 220, 100], 'fit' => 'shrink', 'max_lines' => 2, 'min_font_size' => 28]],
            'tables' => [],
        ];
        $short = $layout->flow(['values' => ['title' => 'TITLE'], 'tables' => [], 'images' => []], $definition);
        self::assertCount(1, $short);
        self::assertSame(40.0, $short[0]['field_layouts']['title']['font_size']);
        $long = $layout->flow(['values' => ['title' => 'LONG AUCTION TITLE'], 'tables' => [], 'images' => []], $definition);
        self::assertCount(1, $long);
        self::assertLessThan(40, $long[0]['field_layouts']['title']['font_size']);
        self::assertGreaterThanOrEqual(28, $long[0]['field_layouts']['title']['font_size']);
        self::assertLessThanOrEqual(2, count($long[0]['field_layouts']['title']['lines']));
        self::assertSame('LONG AUCTION TITLE', implode(' ', $long[0]['field_layouts']['title']['lines']));
    }

    public function test_titles_that_cannot_fit_at_the_readability_limit_are_rejected_without_truncation(): void
    {
        $layout = new AuctionReportTextLayout(dirname(__DIR__, 3).'/resources/auction-reports');
        $definition = ['groups' => ['title' => ['f1']], 'fields' => ['f1' => ['x' => 0.0, 'y' => 40.0, 'width' => 30.0, 'size' => 40.0, 'sy' => 40.0, 'font' => 'Arimo-Bold']], 'layouts' => ['title' => ['box' => [0, 0, 30, 80], 'fit' => 'shrink', 'min_font_size' => 28, 'max_lines' => 2]], 'tables' => []];
        $this->expectException(\InvalidArgumentException::class);
        $layout->flow(['values' => ['title' => str_repeat('LONG TITLE ', 20)], 'tables' => [], 'images' => []], $definition);
    }

    public function test_cover_metadata_august_and_two_digit_positions_do_not_duplicate_template_pages(): void
    {
        $root = dirname(__DIR__, 3).'/resources/auction-reports';
        $layout = new AuctionReportTextLayout($root);
        $artwork = new \App\Services\Tenants\Auctions\Reports\AuctionReportArtwork($root);
        foreach ([['haram', 0, ['name' => 'واجهة عسير', 'manager' => 'أ/ أسامه الحملي', 'project' => 'عسير154']], ['asir', 1, ['name' => 'مزاد جوار الحرم', 'manager' => 'أ/ أسامه الحملي', 'start_month' => 'أغسطس', 'end_month' => 'أغسطس']], ['asir', 18, ['sold_position_1' => '١٠']]] as [$design, $page, $values]) {
            $parts = $layout->flow(['values' => $values, 'tables' => [], 'images' => []], $artwork->page($design, $page));
            self::assertCount(1, $parts, $design.':'.$page);
            foreach ($values as $role => $value) {
                self::assertSame($value, str_replace("\n", ' ', $parts[0]['values'][$role]));
            }
        }
    }

    public function test_wrapped_table_rows_share_the_available_body_instead_of_getting_a_whole_page_each(): void
    {
        $root = dirname(__DIR__, 3).'/resources/auction-reports';
        $definition = (require $root.'/haram/pages.php')[1];
        $definition['groups'] = [];
        $rows = [
            ['-', 'A', '-', 'أرض', '1'],
            ['-', 'B', "816993652240000\n720105007238", str_repeat('عقار طويل الاسم ', 25), '2'],
            ['-', 'C', '-', 'أرض', '3'],
        ];
        $parts = (new AuctionReportTextLayout($root))->flow(['values' => [], 'tables' => ['properties' => $rows], 'images' => []], $definition);
        self::assertCount(1, $parts);
        self::assertSame(['A', 'B', 'C'], array_column($parts[0]['tables']['properties'], 1));
        $geometry = $parts[0]['table_layouts']['properties']['rows'];
        self::assertGreaterThan($geometry[0]['bottom'] - $geometry[0]['top'], $geometry[1]['bottom'] - $geometry[1]['top']);
        self::assertLessThan(200, $geometry[1]['bottom'] - $geometry[1]['top']);
    }

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
        self::assertCount(1, $pages);
        self::assertTrue($pages[0]['table_layouts']['properties']['dynamic']);
    }

    public function test_reference_numeric_fonts_keep_two_digit_board_labels_on_one_line(): void
    {
        $layout = new AuctionReportTextLayout(dirname(__DIR__, 3).'/resources/auction-reports');
        self::assertSame(['١٣'], $layout->wrap('١٣', 22.78, 30.013, 'STV'));
    }

    public function test_continuations_keep_the_document_landscape_without_repeating_evidence(): void
    {
        $root = dirname(__DIR__, 3).'/resources/auction-reports';
        $definition = (require $root.'/asir/pages.php')[2];
        $images = ['report_image' => [['uri' => 'data:image/png;base64,LANDSCAPE']], 'marketing_evidence' => [['uri' => 'EVIDENCE']]];
        $pages = (new AuctionReportTextLayout($root))->flow(['values' => ['introduction' => str_repeat('نص طويل للاختبار ', 200)], 'tables' => [], 'images' => $images], $definition);
        self::assertGreaterThan(1, count($pages));
        self::assertSame(['report_image' => $images['report_image']], $pages[1]['images']);
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
