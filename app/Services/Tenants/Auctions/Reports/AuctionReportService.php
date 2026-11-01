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

            $connection = (string) config('auction-renderer.queue_connection', 'auction_reports');

            GenerateAuctionReportsJob::dispatch(
                $tenantId,
                (int) $auction->id,
                (int) $generation->id,
                $generation->revision_token,
            )->onConnection($connection)->afterCommit();

            DB::afterCommit(function () {
                $this->ensureQueueWorkerIsRunning();
            });

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

        $connection = (string) config('auction-renderer.queue_connection', 'auction_reports');

        GenerateAuctionReportsJob::dispatch(
            $tenantId,
            (int) $auction->id,
            (int) $generation->id,
            $generation->revision_token,
        )->onConnection($connection)->afterCommit();

        DB::afterCommit(function () {
            $this->ensureQueueWorkerIsRunning();
        });

        return $generation->fresh();
    }

    /**
     * تشغيل عامل المعالجة في الخلفية تلقائياً إذا كانت الطوابير تستخدم قاعدة البيانات،
     * لضمان تنفيذ تقارير المزاد دون الحاجة لتشغيل أمر queue:work يدوياً.
     */
    public function ensureQueueWorkerIsRunning(): void
    {
        $connection = (string) config('auction-renderer.queue_connection', 'auction_reports');
        if ($connection === 'sync') {
            return;
        }

        // تفادي تكرار إطلاق عدة عمال متزامنين في نافذة 3 ثوانٍ
        if (! Cache::add('auction_reports_worker_spawn_lock', 1, 3)) {
            return;
        }

        $queue = 'auction-reports';
        $artisan = base_path('artisan');

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $php = PHP_BINARY;
            $cmd = sprintf(
                'start "" /B "%s" "%s" queue:work %s --queue=%s --stop-when-empty --tries=3',
                $php,
                $artisan,
                escapeshellarg($connection),
                escapeshellarg($queue)
            );
            pclose(popen('cmd /c ' . $cmd, 'r'));
        } else {
            if (! function_exists('exec')) {
                Log::warning('AuctionReportService: exec() is disabled in php.ini; cannot spawn background queue worker.');
                return;
            }

            // على خوادم لينكس والويب، PHP_BINARY داخل PHP-FPM يشير لمسار php-fpm وليس مفسر السطر CLI
            $php = env('PHP_CLI_BINARY');
            if (! $php) {
                $fpmReplacement = str_replace(['sbin/php-fpm', 'sbin/fpm'], ['bin/php', 'bin/php'], PHP_BINARY);
                if (@file_exists($fpmReplacement) && @is_executable($fpmReplacement)) {
                    $php = $fpmReplacement;
                } else {
                    foreach ([
                        '/usr/local/bin/ea-php82',
                        '/opt/cpanel/ea-php82/root/usr/bin/php',
                        '/usr/local/bin/ea-php83',
                        '/opt/cpanel/ea-php83/root/usr/bin/php',
                        '/usr/bin/php8.2',
                        '/usr/bin/php82',
                    ] as $candidate) {
                        if (@file_exists($candidate) && @is_executable($candidate)) {
                            $php = $candidate;
                            break;
                        }
                    }
                }
            }
            $php = $php ?: (str_contains(PHP_BINARY, 'fpm') ? 'php' : PHP_BINARY);
            $cmd = sprintf(
                '"%s" "%s" queue:work %s --queue=%s --stop-when-empty --tries=3 > /dev/null 2>&1 &',
                $php,
                $artisan,
                escapeshellarg($connection),
                escapeshellarg($queue)
            );
            exec($cmd);
        }
    }
}
