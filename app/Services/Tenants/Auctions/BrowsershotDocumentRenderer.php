<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions;

use App\Contracts\Auctions\BrowserDocumentRenderer;
use Spatie\Browsershot\Browsershot;

final class BrowsershotDocumentRenderer implements BrowserDocumentRenderer
{
    public function pdf(string $html, string $outputPath): string
    {
        ini_set('memory_limit', '512M');

        $this->browser($html)
            ->paperSize(8.2766667, 11.6933333, 'in')
            ->setOption('preferCSSPageSize', true)
            ->margins(0, 0, 0, 0)
            ->showBackground()
            ->save($outputPath);

        return $outputPath;
    }

    public function png(string $html, string $outputPath): string
    {
        $browser = $this->browser($html);
        if (str_contains($html, 'name="auction-report-design"')) {
            $design = str_contains($html, 'content="haram"') ? 'outputs' : 'report';
            $size = config('auction-report-templates.'.$design.'.page_size');
            $browser->windowSize((int) ceil($size['width'] * 96 / 72), (int) ceil($size['height'] * 96 / 72))
                ->emulateMedia('print')
                ->fullPage();
        } else {
            $browser->windowSize(1200, 1200);
        }
        $browser->save($outputPath);

        return $outputPath;
    }

    private function browser(string $html): Browsershot
    {
        $browser = Browsershot::html($html);

        if (config('auction-renderer.no_sandbox', true)) {
            $browser->noSandbox();
        }

        $browser->addChromiumArguments([
            'disable-setuid-sandbox',
            'disable-dev-shm-usage',
            'disable-gpu',
        ]);

        $chromePath = config('auction-renderer.chrome_path');
        if ($chromePath && @file_exists($chromePath)) {
            $browser->setChromePath($chromePath);
        } elseif (PHP_OS_FAMILY !== 'Windows') {
            foreach ([
                '/usr/bin/chromium-browser',
                '/usr/bin/chromium',
                '/usr/bin/google-chrome-stable',
                '/usr/bin/google-chrome',
                '/snap/bin/chromium',
            ] as $candidate) {
                if (@file_exists($candidate) && @is_executable($candidate)) {
                    $browser->setChromePath($candidate);
                    break;
                }
            }
        }

        $nodeBinary = config('auction-renderer.node_binary');
        if ($nodeBinary && @file_exists($nodeBinary)) {
            $browser->setNodeBinary($nodeBinary);
        } elseif (PHP_OS_FAMILY !== 'Windows') {
            foreach (['/usr/bin/node', '/usr/local/bin/node', '/usr/bin/nodejs'] as $candidate) {
                if (@file_exists($candidate) && @is_executable($candidate)) {
                    $browser->setNodeBinary($candidate);
                    break;
                }
            }
        }

        $npmBinary = config('auction-renderer.npm_binary');
        if ($npmBinary && @file_exists($npmBinary)) {
            $browser->setNpmBinary($npmBinary);
        } elseif (PHP_OS_FAMILY !== 'Windows') {
            foreach (['/usr/bin/npm', '/usr/local/bin/npm'] as $candidate) {
                if (@file_exists($candidate) && @is_executable($candidate)) {
                    $browser->setNpmBinary($candidate);
                    break;
                }
            }
        }

        $browser->timeout(max(180, (int) config('auction-renderer.timeout', 180)))
            ->waitUntilNetworkIdle();

        if (str_contains($html, 'name="auction-report-design"')) {
            $browser->waitForFunction(<<<'JS'
(async () => {
    await document.fonts.ready;
    // Navigation already waits for the SVG image resources. Decoding duplicate
    // 600-DPI images here exhausts Chromium's decoded-image memory budget.
    return document.readyState === 'complete'
        && Array.from(document.images).every(image => image.complete && image.naturalWidth > 0);
})()
JS);
        }

        return $browser;
    }
}
