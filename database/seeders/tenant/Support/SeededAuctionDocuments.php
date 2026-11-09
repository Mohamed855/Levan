<?php

declare(strict_types=1);

namespace Database\Seeders\tenant\Support;

use App\Jobs\Tenants\Auctions\GenerateAuctionRegulatoryFormJob;
use App\Jobs\Tenants\Auctions\GenerateAuctionReportsJob;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionFormGeneration;
use App\Models\Tenants\Auctions\AuctionReportGeneration;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryFormService;
use App\Services\Tenants\Auctions\Reports\AuctionReportService;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class SeededAuctionDocuments
{
    public function generate(Auction $auction): void
    {
        if (! tenancy()->initialized) {
            throw new RuntimeException('Generating seeded auction documents requires an initialized tenant.');
        }
        $auction = $auction->fresh(['establishmentRequest', 'closing']);
        if (! $auction || $auction->trashed()) {
            return;
        }

        $this->reports($auction);
        if ($auction->establishmentRequest) {
            $this->form($auction, 'establishment');
            if ($auction->establishmentRequest->isApproved() && $auction->closing?->saved_at) {
                $this->form($auction, 'closing');
            }
        }
    }

    private function reports(Auction $auction): void
    {
        $generation = $auction->latestReportGeneration()->first();
        if ($this->reportsReady($generation) && (int) $auction->published_report_generation_id === $generation->id) {
            return;
        }
        if (! $generation || in_array($generation->status, ['ready', 'processing', 'superseded'], true)) {
            $generation = app(AuctionReportService::class)->requestGeneration($auction, (string) tenant('id'));
        }
        if (! $generation) {
            throw new RuntimeException('Could not prepare seeded auction reports for auction '.$auction->id.'.');
        }

        app()->call([new GenerateAuctionReportsJob(
            (string) tenant('id'), $auction->id, $generation->id, $generation->revision_token,
        ), 'handle']);
        if (! $this->reportsReady($generation->fresh()) || (int) $auction->fresh()->published_report_generation_id !== $generation->id) {
            throw new RuntimeException('Seeded auction reports are not ready for auction '.$auction->id.'.');
        }
    }

    private function form(Auction $auction, string $type): void
    {
        $forms = app(AuctionRegulatoryFormService::class);
        $generation = $forms->latest($auction, $type);
        if ($this->formReady($generation)) {
            return;
        }
        if (! $generation || in_array($generation->status, ['ready', 'processing', 'superseded'], true)) {
            $generation = $forms->requestGeneration($auction, $type);
        }
        if (! $generation) {
            throw new RuntimeException('Could not prepare seeded '.$type.' form for auction '.$auction->id.'.');
        }

        app()->call([new GenerateAuctionRegulatoryFormJob(
            (string) tenant('id'), $auction->id, $generation->id, $generation->revision_token,
        ), 'handle']);
        if (! $this->formReady($generation->fresh())) {
            throw new RuntimeException('Seeded '.$type.' form is not ready for auction '.$auction->id.'.');
        }
    }

    private function reportsReady(?AuctionReportGeneration $generation): bool
    {
        return $generation?->isReady() && $this->validPdf($generation->outputs_path)
            && $this->validPdf($generation->report_path);
    }

    private function formReady(?AuctionFormGeneration $generation): bool
    {
        return $generation?->isReady() && $this->validPdf($generation->file_path);
    }

    private function validPdf(string $path): bool
    {
        $disk = Storage::disk('tenant_private');
        if (! $disk->exists($path)) {
            return false;
        }
        $stream = $disk->readStream($path);
        if (! is_resource($stream)) {
            return false;
        }
        try {
            if (fread($stream, 5) !== '%PDF-') {
                return false;
            }
            $size = fstat($stream)['size'] ?? 0;
            if ($size > 0 && stream_get_meta_data($stream)['seekable']) {
                fseek($stream, max(0, $size - 1024));
            }
            $tail = '';
            while (! feof($stream)) {
                $chunk = fread($stream, 8192);
                if ($chunk === false || ($chunk === '' && ! feof($stream))) {
                    return false;
                }
                $tail = substr($tail.$chunk, -1024);
            }

            return str_contains($tail, '%%EOF');
        } finally {
            fclose($stream);
        }
    }
}
