<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Support\Auctions\AuctionFontRegistry;
use Tests\TestCase;

final class AuctionFontRegistryTest extends TestCase
{
    public function test_the_approved_local_font_catalog_is_complete(): void
    {
        $registry = app(AuctionFontRegistry::class);
        $expected = [
            'cairo', 'tajawal', 'almarai', 'alexandria', 'ibm-plex-sans-arabic',
            'noto-sans-arabic', 'noto-kufi-arabic', 'noto-naskh-arabic', 'amiri',
            'reem-kufi', 'changa', 'markazi-text', 'mada', 'aref-ruqaa',
        ];

        $this->assertSame($expected, array_keys($registry->all()));
        $this->assertSame('almarai', config('auction-template-fonts.default'));
        foreach ($expected as $slug) {
            $font = $registry->find($slug);
            $this->assertNotEmpty($font['weights']);
            $this->assertTrue($registry->supportsWeight($slug, 400));
            $this->assertFileExists(base_path($font['license']));
            foreach ($font['files'] as $path) {
                $this->assertFileExists(base_path($path));
            }
        }
    }

    public function test_unknown_fonts_and_weights_are_rejected_and_css_is_deterministic(): void
    {
        $registry = app(AuctionFontRegistry::class);

        $this->assertSame([], $registry->find('does-not-exist'));
        $this->assertFalse($registry->supportsWeight('almarai', 999));
        $css = $registry->cssFor(['cairo', 'almarai']);
        $this->assertStringContainsString("font-family: 'Cairo'", $css);
        $this->assertStringContainsString("font-family: 'Almarai'", $css);
        $this->assertStringNotContainsString('Tajawal', $css);
    }
}
