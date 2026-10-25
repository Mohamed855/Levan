<?php

declare(strict_types=1);

namespace App\Jobs\Tenants\Auctions;

use App\Contracts\Auctions\BrowserDocumentRenderer;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionReportGeneration;
use App\Services\Tenants\Auctions\Reports\AuctionReportDataBuilder;
use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
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

    public int $timeout = 300;
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
        $tenant = Tenant::findOrFail($this->tenantId);

        tenancy()->initialize($tenant);

        try {
            $this->generate($dataBuilder, $renderer, $pdfRenderer);
        } finally {
            tenancy()->end();
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
            return; // إصدار قديم — لا يلغي الأحدث
        }

        $generation->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            /** @var Auction $auction */
            $auction = Auction::query()
                ->withoutTrashed()
                ->findOrFail($this->auctionId);

            $snapshot = $generation->snapshot ?? $dataBuilder->build($auction);

            // إنشاء مسارات التخزين المعزولة
            $baseDir = 'auctions/reports/' . $this->auctionId . '/' . $this->revisionToken;
            $outputsPath = $baseDir . '/outputs.pdf';
            $reportPath = $baseDir . '/report.pdf';

            // توليد HTML لكل تصميم
            $outputsHtml = $renderer->html($snapshot, 'outputs');
            $reportHtml = $renderer->html($snapshot, 'report');

            // مسارات الملفات المؤقتة للتوليد
            $outputsTmp = sys_get_temp_dir() . '/auction-outputs-' . $this->revisionToken . '.pdf';
            $reportTmp = sys_get_temp_dir() . '/auction-report-' . $this->revisionToken . '.pdf';

            // توليد PDF عبر Browsershot
            $pdfRenderer->pdf($outputsHtml, $outputsTmp);
            $pdfRenderer->pdf($reportHtml, $reportTmp);

            // تخزين الملفات في مسار معزول بـ UUID
            Storage::disk('tenant_public')->putFileAs(
                $baseDir,
                new \Illuminate\Http\File($outputsTmp),
                'outputs.pdf',
            );
            Storage::disk('tenant_public')->putFileAs(
                $baseDir,
                new \Illuminate\Http\File($reportTmp),
                'report.pdf',
            );

            // تنظيف الملفات المؤقتة
            @unlink($outputsTmp);
            @unlink($reportTmp);

            $generation->update([
                'status' => 'ready',
                'snapshot' => $snapshot,
                'outputs_path' => $outputsPath,
                'report_path' => $reportPath,
                'completed_at' => now(),
                'error_message' => null,
            ]);
        } catch (Throwable $e) {
            $generation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e; // أعد الرمي لكي يُعاد تشغيل الـ Job بعد backoff
        }
    }

    /**
     * إنشاء إصدار جديد وإرسال المهمة بعد commit الـ transaction.
     */
    public static function dispatchAfterCommit(Auction $auction, string $tenantId): AuctionReportGeneration
    {
        $dataBuilder = app(AuctionReportDataBuilder::class);
        $snapshot = $dataBuilder->build($auction);
        $revisionToken = (string) Str::uuid();

        /** @var AuctionReportGeneration $generation */
        $generation = $auction->reportGenerations()->create([
            'revision_token' => $revisionToken,
            'status' => 'queued',
            'snapshot' => $snapshot,
        ]);

        static::dispatch($tenantId, $auction->id, $generation->id, $revisionToken)
            ->afterCommit();

        return $generation;
    }
}
