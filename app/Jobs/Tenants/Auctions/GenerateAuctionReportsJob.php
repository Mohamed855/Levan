<?php

declare(strict_types=1);

namespace App\Jobs\Tenants\Auctions;

use App\Contracts\Auctions\BrowserDocumentRenderer;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionReportGeneration;
use App\Services\Tenants\Auctions\Reports\AuctionReportDataBuilder;
use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use App\Services\Tenants\Auctions\Reports\AuctionReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class GenerateAuctionReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 420;
    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly string $tenantId,
        private readonly int $auctionId,
        private readonly int $generationId,
        private readonly string $revisionToken,
    ) {}

    public function handle(
        AuctionReportDataBuilder $dataBuilder,
        AuctionReportRenderer $renderer,
        BrowserDocumentRenderer $pdfRenderer,
    ): void {
        $previousTenant = tenancy()->initialized ? tenant() : null;
        $alreadyInitialized = $previousTenant && (string) $previousTenant->getTenantKey() === $this->tenantId;

        if (! $alreadyInitialized) {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
            $tenant = Tenant::findOrFail($this->tenantId);
            tenancy()->initialize($tenant);
        }

        try {
            $this->generate($dataBuilder, $renderer, $pdfRenderer);
        } finally {
            if (! $alreadyInitialized) {
                tenancy()->end();
                if ($previousTenant) {
                    tenancy()->initialize($previousTenant);
                }
            }
        }
    }

    private function generate(
        AuctionReportDataBuilder $dataBuilder,
        AuctionReportRenderer $renderer,
        BrowserDocumentRenderer $pdfRenderer,
    ): void {
        /** @var AuctionReportGeneration|null $generation */
        $generation = AuctionReportGeneration::query()
            ->where('id', $this->generationId)
            ->where('revision_token', $this->revisionToken)
            ->first();

        if (! $generation) {
            return; // إصدار أُلغي أو استُبدل بإصدار أحدث
        }

        // تجاهل المهمة إذا كان هناك إصدار أحدث لنفس المزاد
        $latestId = AuctionReportGeneration::query()
            ->where('auction_id', $this->auctionId)
            ->max('id');

        if ($latestId > $this->generationId) {
            $generation->update(['status' => 'superseded', 'completed_at' => now()]);
            return;
        }

        $generation->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $baseDir = 'auction-reports/'.$this->auctionId.'/'.$this->revisionToken;
        $outputsPath = $baseDir.'/outputs.pdf';
        $reportPath = $baseDir.'/report.pdf';
        $outputsTmp = sys_get_temp_dir().'/auction-outputs-'.$this->revisionToken.'.pdf';
        $reportTmp = sys_get_temp_dir().'/auction-report-'.$this->revisionToken.'.pdf';

        try {
            /** @var Auction $auction */
            $auction = Auction::query()
                ->withoutTrashed()
                ->findOrFail($this->auctionId);

            $snapshot = $generation->snapshot ?? $dataBuilder->build($auction);

            $outputsHtml = $renderer->html($snapshot, 'outputs');
            $pdfRenderer->pdf($outputsHtml, $outputsTmp);
            unset($outputsHtml);

            $reportHtml = $renderer->html($snapshot, 'report');
            $pdfRenderer->pdf($reportHtml, $reportTmp);
            unset($reportHtml);

            $this->assertPdfWasWritten($outputsTmp);
            $this->assertPdfWasWritten($reportTmp);

            if (! $this->isLatestGeneration()) {
                $generation->update(['status' => 'superseded', 'completed_at' => now()]);
                return;
            }

            $disk = Storage::disk('tenant_private');
            $outputsWritten = $disk->put($outputsPath, file_get_contents($outputsTmp));
            $reportWritten = $disk->put($reportPath, file_get_contents($reportTmp));
            if (! $outputsWritten || ! $reportWritten || ! $disk->exists($outputsPath) || ! $disk->exists($reportPath)) {
                throw new \RuntimeException('تعذر حفظ ملفي تقرير المزاد في التخزين الخاص.');
            }

            if (! $this->isLatestGeneration()) {
                $disk->delete([$outputsPath, $reportPath]);
                $generation->update(['status' => 'superseded', 'completed_at' => now()]);
                return;
            }

            $generation->update([
                'status' => 'ready',
                'snapshot' => $snapshot,
                'outputs_path' => $outputsPath,
                'report_path' => $reportPath,
                'completed_at' => now(),
                'error_message' => null,
            ]);
        } catch (Throwable $e) {
            Storage::disk('tenant_private')->delete([$outputsPath, $reportPath]);
            $generation->update([
                'status' => 'failed',
                'outputs_path' => null,
                'report_path' => null,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e; // أعد الرمي لكي يُعاد تشغيل الـ Job بعد backoff
        } finally {
            @unlink($outputsTmp);
            @unlink($reportTmp);
        }
    }

    private function isLatestGeneration(): bool
    {
        return (int) AuctionReportGeneration::query()
            ->where('auction_id', $this->auctionId)
            ->max('id') === $this->generationId;
    }

    private function assertPdfWasWritten(string $path): void
    {
        if (! is_file($path) || filesize($path) < 5) {
            throw new \RuntimeException('محرك PDF لم يُنتج ملفًا صالحًا.');
        }
    }

    /**
     * إنشاء إصدار جديد وإرسال المهمة بعد commit الـ transaction.
     */
    public static function dispatchAfterCommit(Auction $auction, string $tenantId): AuctionReportGeneration
    {
        return app(AuctionReportService::class)->requestGeneration($auction, $tenantId)
            ?? throw new \RuntimeException('تعذر إنشاء إصدار تقارير المزاد.');
    }

    /**
     * إنشاء إصدار وتوليد الملفات فوراً بشكل متزامن.
     */
    public static function dispatchSyncNow(Auction $auction, string $tenantId): AuctionReportGeneration
    {
        $dataBuilder = app(AuctionReportDataBuilder::class);
        $renderer = app(AuctionReportRenderer::class);
        $pdfRenderer = app(BrowserDocumentRenderer::class);

        $snapshot = $dataBuilder->build($auction);
        $revisionToken = (string) Str::uuid();

        /** @var AuctionReportGeneration $generation */
        $generation = $auction->reportGenerations()->create([
            'revision_token' => $revisionToken,
            'status' => 'queued',
            'snapshot' => $snapshot,
        ]);

        $job = new static($tenantId, $auction->id, $generation->id, $revisionToken);
        $job->handle($dataBuilder, $renderer, $pdfRenderer);

        return $generation->fresh();
    }
}
