<?php

declare(strict_types=1);

namespace App\Services\Auctions;

use App\Models\Tenant\Auction;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;

final class AuctionSnapshotPdfExporter
{
    public function __construct(private readonly AuctionTemplateRenderer $renderer) {}

    public function make(Auction $auction, string $type): string
    {
        $output = $this->renderer->render($auction, $type);
        $output['bindings']['auction.cover_image'] = $this->imageDataUri($output['bindings']['auction.cover_image'] ?? null);
        $output['template']['schema'] = $this->withEmbeddedBackgrounds($output['template']['schema']);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => match ($type) {
                'billboard' => 'A4-L',
                'social' => [210, 210],
                default => 'A4',
            },
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
        ]);
        $mpdf->SetDirectionality('rtl');
        $mpdf->SetAutoFont();
        $mpdf->WriteHTML(view('tenant.auctions.pdf', compact('auction', 'output', 'type'))->render());

        return $mpdf->OutputBinaryData();
    }

    /** @param array<string, mixed> $schema */
    private function withEmbeddedBackgrounds(array $schema): array
    {
        foreach ($schema['pages'] ?? [] as $index => $page) {
            $path = $page['background']['image'] ?? null;
            $schema['pages'][$index]['background']['image'] = $this->imageDataUri($path);
        }

        return $schema;
    }

    private function imageDataUri(mixed $path): ?string
    {
        if (! is_string($path) || ! preg_match('/^[A-Za-z0-9_./-]+$/', $path)) {
            return null;
        }

        $disk = Storage::disk('tenant_public');
        if (! $disk->exists($path) || ($disk->size($path) ?? 0) > 10 * 1024 * 1024) {
            return null;
        }

        $mime = $disk->mimeType($path);
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($disk->get($path));
    }
}
