<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Auctions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auctions\RetryAuctionReportsRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionReportGeneration;
use App\Services\Tenants\Auctions\Reports\AuctionReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AuctionReportController extends Controller
{
    public function __construct(private readonly AuctionReportService $reportService)
    {
        $this->middleware('auth:tenant');
        $this->middleware('can:auctions.view');
    }

    /**
     * حالة آخر إصدار لمزاد.
     */
    public function status(Auction $auction): JsonResponse
    {
        $this->authorize('view', $auction);

        $generation = $auction->latestReportGeneration;
        $published = $auction->publishedReportGeneration;

        if (! $generation) {
            return response()->json(['status' => 'none']);
        }

        if ($generation->status === 'queued') {
            $generation = $this->reportService->startPendingGeneration($auction, (string) tenant('id')) ?? $generation;
        }

        return response()->json($this->reportStatePayload($auction, $generation, $published));
    }

    /**
     * تنزيل ملف التقرير بحسب النوع (outputs أو report).
     */
    public function download(Auction $auction, string $type): StreamedResponse|RedirectResponse
    {
        return match ($type) {
            'outputs' => $this->downloadOutputs($auction),
            'report' => $this->downloadReport($auction),
            default => abort(404, 'نوع التقرير غير مدعوم.'),
        };
    }

    /**
     * تنزيل مخرجات المزاد (التصميم الأول).
     */
    public function downloadOutputs(Auction $auction): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $auction);

        $generation = $auction->publishedReportGeneration;

        abort_unless(
            $generation?->isReady() && filled($generation->outputs_path),
            404,
            'ملف مخرجات المزاد غير متوفر بعد أو لم يكتمل الإعداد.',
        );

        abort_unless(
            Storage::disk('tenant_private')->exists($generation->outputs_path),
            404,
            'الملف غير موجود على القرص.',
        );

        $fileName = 'مخرجات-مزاد-'.$auction->id.'.pdf';

        return Storage::disk('tenant_private')->download($generation->outputs_path, $fileName);
    }

    /**
     * تنزيل تقرير المزاد (التصميم الثاني).
     */
    public function downloadReport(Auction $auction): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $auction);

        $generation = $auction->publishedReportGeneration;

        abort_unless(
            $generation?->isReady() && filled($generation->report_path),
            404,
            'ملف تقرير المزاد غير متوفر بعد أو لم يكتمل الإعداد.',
        );

        abort_unless(
            Storage::disk('tenant_private')->exists($generation->report_path),
            404,
            'الملف غير موجود على القرص.',
        );

        $fileName = 'تقرير-مزاد-'.$auction->id.'.pdf';

        return Storage::disk('tenant_private')->download($generation->report_path, $fileName);
    }

    /**
     * إعادة محاولة / بدء توليد ملفات المزاد.
     */
    public function retry(RetryAuctionReportsRequest $request, Auction $auction): JsonResponse
    {
        $tenantId = (string) (tenant('id') ?: tenant()?->id);

        try {
            $generation = $this->reportService->retryLatest($auction, $tenantId);
            if ($generation->status === 'queued') {
                $generation = $this->reportService->startPendingGeneration($auction, $tenantId) ?? $generation;
            }
            $published = ($auction->exists ? $auction->fresh()?->publishedReportGeneration : null)
                ?? $auction->publishedReportGeneration;
            $payload = $this->reportStatePayload($auction, $generation, $published);

            $isReady = $generation->isReady();
            $isFailed = $generation->isFailed();

            if ($isFailed) {
                return response()->json(array_replace($payload, [
                    'success' => false,
                    'status' => 'failed',
                    'message' => $generation->publicErrorMessage() ?: 'تعذر تجهيز ملفات المزاد.',
                ]), 422);
            }

            return response()->json(array_replace($payload, [
                'success' => true,
                'message' => $isReady
                    ? 'تم تجهيز ملفات المزاد بنجاح.'
                    : 'تمت جدولة إعادة تجهيز ملفات المزاد.',
            ]), $isReady ? 200 : 202);
        } catch (\Throwable $e) {
            Log::error('Auction report retry failed.', ['auction_id' => $auction->id, 'exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'تعذر تجهيز ملفات المزاد. يُرجى المحاولة مرة أخرى.',
            ], 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function reportStatePayload(Auction $auction, AuctionReportGeneration $generation, ?AuctionReportGeneration $published): array
    {
        $hasOutputs = (bool) ($published?->isReady() && filled($published->outputs_path));
        $hasReport = (bool) ($published?->isReady() && filled($published->report_path));
        $canBuildUrls = $auction->id !== null;

        return [
            'status' => $generation->status,
            'progress' => (int) ($generation->progress ?? ($generation->isReady() ? 100 : 0)),
            'generation_id' => $generation->id,
            'has_outputs' => $hasOutputs,
            'has_report' => $hasReport,
            'outputs_download_url' => ($hasOutputs && $canBuildUrls)
                ? tenant_route_name('auctions.reports.download', ['auction' => $auction->id, 'type' => 'outputs'])
                : null,
            'report_download_url' => ($hasReport && $canBuildUrls)
                ? tenant_route_name('auctions.reports.download', ['auction' => $auction->id, 'type' => 'report'])
                : null,
            'error_message' => $generation->publicErrorMessage(),
            'updated_at' => $generation->updated_at?->toIso8601String(),
        ];
    }
}
