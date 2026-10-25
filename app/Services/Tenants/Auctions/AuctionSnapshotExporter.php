<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions;

use App\Models\Tenants\Auctions\Auction;
use Illuminate\Support\Facades\File;
use Mpdf\Mpdf;
use Spatie\Browsershot\Browsershot;

final class AuctionSnapshotExporter
{
    public function pdf(Auction $auction, string $type, string $outputPath): string
    {
        $snapshot = $auction->template_snapshot[$type] ?? null;
        if (! is_array($snapshot)) {
            throw new \RuntimeException('Template snapshot not found.');
        }
        File::ensureDirectoryExists(dirname($outputPath));
        $html = view('shared.auctions.design-document', ['schema' => $snapshot['schema'] ?? [], 'bindings' => app(AuctionTemplateRenderer::class)->bindings($auction)])->render();
        if (class_exists(Browsershot::class)) {
            return app(BrowsershotDocumentRenderer::class)->pdf($html, $outputPath);
        }
        $pdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
        $pdf->WriteHTML($html);
        $pdf->Output($outputPath, 'F');

        return $outputPath;
    }
}
