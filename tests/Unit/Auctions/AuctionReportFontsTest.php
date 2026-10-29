<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportFonts;
use PHPUnit\Framework\TestCase;

final class AuctionReportFontsTest extends TestCase
{
    public function test_aptos_narrow_bold_uses_the_complete_matching_face(): void
    {
        $font = (new AuctionReportFonts(dirname(__DIR__, 3).'/resources/auction-reports'))->resolve('Aptos Narrow,Bold');
        self::assertTrue($font['matched']);
        self::assertSame('aptos-narrow-bold.ttf', basename($font['file']));
        self::assertSame(700, $font['weight']);
    }

    public function test_the_complete_original_family_is_selected_for_numbers_and_arabic_text(): void
    {
        $fonts = new AuctionReportFonts(dirname(__DIR__, 3).'/resources/auction-reports');
        foreach (['Arimo-Bold' => 'arimo-bold.ttf', 'Arimo-Regular' => 'arimo-regular.ttf', 'STV' => 'stv.ttf', 'TheSans-Plain' => 'the-sans-plain.ttf', 'TheSans-Bold' => 'the-sans-bold.ttf', 'LamaSans-Bold' => 'lama-sans-bold.ttf', 'IBMPlexSansCond' => 'ibm-plex-sans-condensed.ttf'] as $name => $file) {
            self::assertSame($file, basename($fonts->resolve($name)['file']));
            self::assertFileExists($fonts->resolve($name)['file']);
        }
    }

    public function test_reference_digits_and_punctuation_have_positive_advance_widths(): void
    {
        $fonts = new AuctionReportFonts(dirname(__DIR__, 3).'/resources/auction-reports');
        foreach (str_split('0123456789,./:-%') as $character) {
            self::assertGreaterThan(0, $fonts->width($character, 100, 'Arimo-Bold'), $character);
        }
        self::assertGreaterThan($fonts->width('100006', 12, 'Arimo-Bold'), $fonts->width('1,000,006', 12, 'Arimo-Bold'));
    }

    public function test_noto_uses_static_regular_and_bold_instances_without_changing_the_requested_weight(): void
    {
        $fonts = new AuctionReportFonts(dirname(__DIR__, 3).'/resources/auction-reports');
        self::assertSame('noto-sans-arabic-regular.ttf', basename($fonts->resolve('NotoSansArabic-Regular')['file']));
        self::assertSame('noto-sans-arabic-bold.ttf', basename($fonts->resolve('NotoSansArabic-Bold')['file']));
        self::assertSame(700, $fonts->resolve('NotoSansArabic-Bold')['weight']);
    }
}
