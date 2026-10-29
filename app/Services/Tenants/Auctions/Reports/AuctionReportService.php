<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use App\Jobs\Tenants\Auctions\GenerateAuctionReportsJob;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionReportGeneration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class AuctionReportService
{
    public function __construct(private readonly AuctionReportDataBuilder $dataBuilder) {}

    public function requestGeneration(Auction $auction, string $tenantId): ?AuctionReportGeneration
    {
        try {
            $generation = $auction->reportGenerations()->create([
                'revision_token' => (string) Str::uuid(),
                'status' => 'queued',
                'progress' => 0,
                'snapshot' => $this->dataBuilder->build($auction),
            ]);

            GenerateAuctionReportsJob::dispatch(
                $tenantId,
                (int) $auction->id,
                (int) $generation->id,
                $generation->revision_token,
            )->onConnection(config('auction-renderer.queue_connection', 'database'))->afterCommit();

            return $generation;
        } catch (Throwable $exception) {
            Log::error('Auction report generation could not be queued.', [
                'tenant_id' => $tenantId,
                'auction_id' => $auction->id,
                'exception' => $exception,
            ]);

            return null;
        }
    }

    public function retryLatest(Auction $auction, string $tenantId): AuctionReportGeneration
    {
        $generation = $auction->latestReportGeneration()->first();
        if (! $generation) {
            $generation = $auction->reportGenerations()->create([
                'revision_token' => (string) Str::uuid(),
                'status' => 'queued',
                'progress' => 0,
                'snapshot' => $this->dataBuilder->build($auction),
            ]);
        } else {
            $generation->update([
                'status' => 'queued',
                'progress' => 0,
                'outputs_path' => null,
                'report_path' => null,
                'error_message' => null,
                'started_at' => null,
                'completed_at' => null,
            ]);
        }

        GenerateAuctionReportsJob::dispatch(
            $tenantId,
            (int) $auction->id,
            (int) $generation->id,
            $generation->revision_token,
        )->onConnection(config('auction-renderer.queue_connection', 'database'))->afterCommit();

        return $generation->fresh();
    }
}
