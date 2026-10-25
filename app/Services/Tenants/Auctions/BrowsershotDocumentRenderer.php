<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions;

use App\Contracts\Auctions\BrowserDocumentRenderer;
use Spatie\Browsershot\Browsershot;

final class BrowsershotDocumentRenderer implements BrowserDocumentRenderer
{
    public function pdf(string $html, string $outputPath): string
    {
        Browsershot::html($html)->setNodeBinary(config('auction-renderer.node_binary'))->setNpmBinary(config('auction-renderer.npm_binary'))->setChromePath(config('auction-renderer.chrome_path'))->timeout((int) config('auction-renderer.timeout', 120))->save($outputPath);

        return $outputPath;
    }

    public function png(string $html, string $outputPath): string
    {
        Browsershot::html($html)->setNodeBinary(config('auction-renderer.node_binary'))->setNpmBinary(config('auction-renderer.npm_binary'))->setChromePath(config('auction-renderer.chrome_path'))->timeout((int) config('auction-renderer.timeout', 120))->windowSize(1200, 1200)->save($outputPath);

        return $outputPath;
    }
}
