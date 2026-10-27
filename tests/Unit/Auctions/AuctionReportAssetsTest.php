<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use PHPUnit\Framework\TestCase;

final class AuctionReportAssetsTest extends TestCase
{
    public function test_packaged_report_assets_are_local_and_match_their_recorded_hashes(): void
    {
        $root = dirname(__DIR__, 3).'/resources/auction-reports';
        self::assertFileExists($root.'/manifest.php');
        $manifest = require $root.'/manifest.php';

        self::assertNotEmpty($manifest['files']);
        foreach ($manifest['files'] as $relativePath => $hash) {
            self::assertStringNotContainsString('..', $relativePath);
            self::assertFileExists($root.'/'.$relativePath);
            self::assertSame($hash, hash_file('sha256', $root.'/'.$relativePath), $relativePath);
        }
        self::assertFileExists($root.'/fonts/noto-sans-arabic.ttf');
        self::assertFileExists($root.'/fonts/ibm-plex-sans-arabic.ttf');
        self::assertFileExists($root.'/fonts/NotoSansArabic-OFL.txt');
        self::assertFileExists($root.'/fonts/IBMPlexSansArabic-OFL.txt');
        self::assertFileExists($root.'/fonts/ge-contrast-bold.ttf');
        self::assertCount(7, require $root.'/haram/pages.php');
        self::assertCount(21, require $root.'/asir/pages.php');
        $asir = require $root.'/asir/pages.php';
        self::assertCount(6, array_filter($asir[7]['image_defs'], static fn (array $frame): bool => $frame['section'] === 'board_before'));
        self::assertStringNotContainsString('static-3cf40a9f812e2527f4cb', file_get_contents($root.'/asir/page-8.svg'));
        foreach ([10 => 'i8', 11 => 'i10', 12 => 'i3', 13 => 'i4', 14 => 'i20', 16 => 'i17'] as $page => $id) {
            self::assertArrayNotHasKey($id, $asir[$page]['image_defs'], 'Brand icons and shadows are static artwork.');
        }
        foreach (glob($root.'/asir/static-*') as $asset) {
            self::assertArrayHasKey('asir/'.basename($asset), $manifest['files'], 'Obsolete source assets must not be shipped.');
        }
    }
}
