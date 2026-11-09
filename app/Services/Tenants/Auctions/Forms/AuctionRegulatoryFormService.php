<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Forms;

use App\Jobs\Tenants\Auctions\GenerateAuctionRegulatoryFormJob;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionFormGeneration;
use App\Services\Tenants\Auctions\Reports\AuctionReportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class AuctionRegulatoryFormService
{
    public function __construct(private readonly AuctionRegulatoryFormDataBuilder $dataBuilder) {}

    public function requestGeneration(Auction $auction, string $type): ?AuctionFormGeneration
    {
        $this->dataBuilder->assertType($type);
        try {
            return DB::transaction(function () use ($auction, $type): AuctionFormGeneration {
                $locked = Auction::query()->whereKey($auction->id)->lockForUpdate()->firstOrFail();

                return AuctionFormGeneration::query()->create([
                    'auction_id' => $locked->id, 'type' => $type,
                    'revision_token' => (string) Str::uuid(), 'status' => 'queued', 'progress' => 0,
                    'snapshot' => $this->dataBuilder->build($locked, $type),
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('Regulatory form could not be queued.', ['auction_id' => $auction->id, 'type' => $type, 'exception' => $exception]);

            return null;
        }
    }

    public function latest(Auction $auction, string $type): ?AuctionFormGeneration
    {
        $this->dataBuilder->assertType($type);

        return AuctionFormGeneration::query()->where('auction_id', $auction->id)->where('type', $type)->latest('id')->first();
    }

    public function published(Auction $auction, string $type): ?AuctionFormGeneration
    {
        $this->dataBuilder->assertType($type);

        return AuctionFormGeneration::query()->where('auction_id', $auction->id)->where('type', $type)
            ->where('status', 'ready')->whereNotNull('file_path')->latest('id')->first();
    }

    public function retryLatest(Auction $auction, string $type): AuctionFormGeneration
    {
        return $this->requestGeneration($auction, $type)
            ?? throw new RuntimeException('تعذر جدولة إعادة توليد النموذج.');
    }

    public function startPendingGeneration(Auction $auction, string $type, string $tenantId): ?AuctionFormGeneration
    {
        $this->dataBuilder->assertType($type);
        if (! tenancy()->initialized || (string) tenant()->getTenantKey() !== $tenantId) {
            throw new RuntimeException('A matching tenant context is required.');
        }
        $generation = null;
        try {
            $generation = DB::transaction(function () use ($auction, $type, $tenantId): ?AuctionFormGeneration {
                $locked = Auction::query()->whereKey($auction->id)->lockForUpdate()->firstOrFail();
                $generation = $this->latest($locked, $type);
                if (! $generation || $generation->status !== 'queued') {
                    return null;
                }
                if ($generation->progress === 0) {
                    $generation->update(['progress' => 5]);
                    $connection = (string) config('auction-renderer.queue_connection', 'auction_reports');
                    if (! in_array(config('queue.connections.'.$connection.'.driver'), ['database', 'redis', 'beanstalkd', 'sqs'], true)) {
                        $connection = 'auction_reports';
                    }
                    GenerateAuctionRegulatoryFormJob::dispatch($tenantId, (int) $locked->id, (int) $generation->id, $generation->revision_token)
                        ->onConnection($connection)->onQueue('auction-reports')->afterCommit();
                }

                return $generation;
            });
            if ($generation) {
                DB::afterCommit(fn () => app(AuctionReportService::class)->ensureQueueWorkerIsRunning());
            }

            return $generation?->fresh();
        } catch (Throwable $exception) {
            Log::error('Regulatory form could not be started.', ['auction_id' => $auction->id, 'type' => $type, 'exception' => $exception]);
            if ($generation) {
                AuctionFormGeneration::query()->whereKey($generation->id)->where('status', 'queued')->update([
                    'status' => 'failed', 'error_message' => $exception->getMessage(), 'completed_at' => now(),
                ]);
            }

            return $generation?->fresh();
        }
    }
}
