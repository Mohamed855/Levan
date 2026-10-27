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
        foreach (['node_binary' => 'setNodeBinary', 'npm_binary' => 'setNpmBinary', 'chrome_path' => 'setChromePath'] as $key => $method) {
            if ($value = config('auction-renderer.'.$key)) {
                $browser->{$method}($value);
            }
        }
        $browser->timeout(max(180, (int) config('auction-renderer.timeout', 180)))
            ->waitUntilNetworkIdle();

        if (str_contains($html, 'name="auction-report-design"')) {
            $browser->waitForFunction(<<<'JS'
(async () => {
    await document.fonts.ready;
    await Promise.all(Array.from(document.querySelectorAll('svg image')).map(async node => {
        const uri = node.getAttributeNS('http://www.w3.org/1999/xlink', 'href') || node.getAttribute('href');
        if (!uri || uri.startsWith('#')) return;
        const image = new Image();
        image.src = uri;
        await image.decode();
    }));
    return true;
})()
JS);
        }

        return $browser;
    }
}
