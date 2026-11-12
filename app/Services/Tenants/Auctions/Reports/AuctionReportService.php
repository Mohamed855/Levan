<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use App\Jobs\Tenants\Auctions\GenerateAuctionReportsJob;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionReportGeneration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\PhpExecutableFinder;
use Throwable;

final class AuctionReportService
{
    public function __construct(private readonly AuctionReportDataBuilder $dataBuilder) {}

    public function requestGeneration(Auction $auction, string $tenantId, bool $refreshBranding = false): ?AuctionReportGeneration
    {
        try {
            $generation = DB::transaction(function () use ($auction, $refreshBranding): AuctionReportGeneration {
                $auction = Auction::query()->whereKey($auction->id)->lockForUpdate()->firstOrFail();
                $snapshot = $auction->closing?->finalized_snapshot ?? $this->dataBuilder->build($auction);
                if ($refreshBranding) {
                    $snapshot = (new AuctionReportBranding)->apply($snapshot);
                }

                return $auction->reportGenerations()->create([
                    'revision_token' => (string) Str::uuid(), 'status' => 'queued', 'progress' => 0,
                    'snapshot' => $snapshot,
                ]);
            });

            return $generation->fresh();
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
        return $this->requestGeneration($auction, $tenantId, refreshBranding: true)
            ?? throw new \RuntimeException('تعذر جدولة إعادة توليد التقارير.');
    }

    public function startPendingGeneration(Auction $auction, string $tenantId): ?AuctionReportGeneration
    {
        $generation = null;
        try {
            $generation = DB::transaction(function () use ($auction, $tenantId, &$generation): ?AuctionReportGeneration {
                $auction = Auction::query()->whereKey($auction->id)->lockForUpdate()->firstOrFail();
                $generation = $auction->latestReportGeneration()->lockForUpdate()->first();
                if (! $generation || $generation->status !== 'queued') {
                    return null;
                }

                // Zero progress means the details page has not started this revision yet.
                if ((int) $generation->progress === 0) {
                    $generation->update(['progress' => 5]);
                    GenerateAuctionReportsJob::dispatch(
                        $tenantId,
                        (int) $auction->id,
                        (int) $generation->id,
                        $generation->revision_token,
                    )->onConnection($this->backgroundConnection())->onQueue('auction-reports')->afterCommit();
                }

                return $generation;
            });

            if ($generation) {
                DB::afterCommit(fn () => $this->ensureQueueWorkerIsRunning());
            }

            return $generation?->fresh();
        } catch (Throwable $exception) {
            Log::error('Auction report generation could not be started.', [
                'tenant_id' => $tenantId, 'auction_id' => $auction->id, 'exception' => $exception,
            ]);
            if ($generation) {
                AuctionReportGeneration::query()->whereKey($generation->id)->where('status', 'queued')->update([
                    'status' => 'failed', 'error_message' => $exception->getMessage(), 'completed_at' => now(),
                ]);
            }

            return $generation?->fresh();
        }
    }

    private function backgroundConnection(): string
    {
        $connection = (string) config('auction-renderer.queue_connection', 'auction_reports');

        return in_array(config('queue.connections.'.$connection.'.driver'), ['database', 'redis', 'beanstalkd', 'sqs'], true)
            ? $connection : 'auction_reports';
    }

    /**
     * تشغيل عامل المعالجة في الخلفية تلقائياً إذا كانت الطوابير تستخدم قاعدة البيانات،
     * لضمان تنفيذ تقارير المزاد دون الحاجة لتشغيل أمر queue:work يدوياً.
     */
    public function ensureQueueWorkerIsRunning(): void
    {
        $connection = $this->backgroundConnection();

        // تفادي تكرار إطلاق عدة عمال متزامنين في نافذة 3 ثوانٍ
        if (! Cache::add('auction_reports_worker_spawn_lock', 1, 3)) {
            return;
        }

        $queue = 'auction-reports';
        $artisan = base_path('artisan');
        $php = config('auction-renderer.php_binary') ?: (new PhpExecutableFinder)->find(false);
        if (! $php) {
            Log::warning('AuctionReportService: PHP CLI executable could not be found; configure PHP_CLI_BINARY and rebuild the configuration cache.');

            return;
        }
        $workerLog = storage_path('logs/auction-report-worker.log');

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = sprintf(
                'start "" /B %s %s queue:work %s --queue=%s --stop-when-empty --tries=3 >> %s 2>&1',
                escapeshellarg($php),
                escapeshellarg($artisan),
                escapeshellarg($connection),
                escapeshellarg($queue),
                escapeshellarg($workerLog),
            );
            pclose(popen('cmd /c '.$cmd, 'r'));
        } else {
            if (! function_exists('exec')) {
                Log::warning('AuctionReportService: exec() is disabled in php.ini; cannot spawn background queue worker.');

                return;
            }

            $cmd = sprintf(
                '%s %s queue:work %s --queue=%s --stop-when-empty --tries=3 >> %s 2>&1 &',
                escapeshellarg($php),
                escapeshellarg($artisan),
                escapeshellarg($connection),
                escapeshellarg($queue),
                escapeshellarg($workerLog),
            );
            exec($cmd);
        }
    }
}
