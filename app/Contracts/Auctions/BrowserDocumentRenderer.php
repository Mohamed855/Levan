<?php

declare(strict_types=1);

namespace App\Contracts\Auctions;

interface BrowserDocumentRenderer
{
    public function pdf(string $html, string $outputPath): string;

    public function png(string $html, string $outputPath): string;
}
