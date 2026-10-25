<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions;

use App\Contracts\Auctions\BrowserDocumentRenderer;
use Spatie\Browsershot\Browsershot;

final class BrowsershotDocumentRenderer implements BrowserDocumentRenderer
{
    public function pdf(string $html, string $outputPath): string
    {
        $instance = Browsershot::html($html);

        if ($node = config('auction-renderer.node_binary')) {
            $instance->setNodeBinary($node);
        }
        if ($npm = config('auction-renderer.npm_binary')) {
            $instance->setNpmBinary($npm);
        }
        if ($chrome = config('auction-renderer.chrome_path')) {
            $instance->setChromePath($chrome);
        }

        $instance->timeout((int) config('auction-renderer.timeout', 120))
            ->format('A4')
            ->margins(0, 0, 0, 0)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->save($outputPath);

        return $outputPath;
    }

    public function png(string $html, string $outputPath): string
    {
        $instance = Browsershot::html($html);

        if ($node = config('auction-renderer.node_binary')) {
            $instance->setNodeBinary($node);
        }
        if ($npm = config('auction-renderer.npm_binary')) {
            $instance->setNpmBinary($npm);
        }
        if ($chrome = config('auction-renderer.chrome_path')) {
            $instance->setChromePath($chrome);
        }

        $instance->timeout((int) config('auction-renderer.timeout', 120))
            ->windowSize(1200, 1200)
            ->waitUntilNetworkIdle()
            ->save($outputPath);

        return $outputPath;
    }
}
