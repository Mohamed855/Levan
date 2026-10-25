<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Auctions;

use App\Http\Controllers\Controller;
use App\Jobs\Tenants\Auctions\GenerateAuctionReportsJob;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionReportGeneration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AuctionReportController extends Controller
{
    public function __construct()
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

        if (! $generation) {
            return response()->json(['status' => 'none']);
        }

        return response()->json([
            'status' => $generation->status,
            'generation_id' => $generation->id,
            'has_outputs' => filled($generation->outputs_path),
            'has_report' => filled($generation->report_path),
            'error_message' => $generation->error_message,
            'updated_at' => $generation->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * تنزيل مخرجات المزاد (التصميم الأول).
     */
    public function downloadOutputs(Auction $auction): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $auction);

        $generation = $auction->latestReportGeneration;

        abort_unless(
            $generation?->isReady() && filled($generation->outputs_path),
            404,
            'ملف مخرجات المزاد غير متوفر بعد أو لم يكتمل التجهيز.',
        );

        abort_unless(
            Storage::disk('tenant_public')->exists($generation->outputs_path),
            404,
            'الملف غير موجود على القرص.',
        );

        $fileName = 'مخرجات-مزاد-' . $auction->id . '.pdf';

        return Storage::disk('tenant_public')->download($generation->outputs_path, $fileName);
    }

    /**
     * تنزيل تقرير المزاد (التصميم الثاني).
     */
    public function downloadReport(Auction $auction): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $auction);

        $generation = $auction->latestReportGeneration;

        abort_unless(
            $generation?->isReady() && filled($generation->report_path),
            404,
            'ملف تقرير المزاد غير متوفر بعد أو لم يكتمل التجهيز.',
        );

        abort_unless(
            Storage::disk('tenant_public')->exists($generation->report_path),
            404,
            'الملف غير موجود على القرص.',
        );

        $fileName = 'تقرير-مزاد-' . $auction->id . '.pdf';

        return Storage::disk('tenant_public')->download($generation->report_path, $fileName);
    }

    /**
     * إعادة محاولة توليد الملفات (عند الفشل).
     */
    public function retry(Request $request, Auction $auction): JsonResponse
    {
        $this->authorize('view', $auction);

        $generation = $auction->latestReportGeneration;

        abort_unless(
            $generation && $generation->status === 'failed',
            422,
            'لا يوجد إصدار فاشل لإعادة المحاولة.',
        );

        $tenantId = (string) tenant('id');

        GenerateAuctionReportsJob::dispatchAfterCommit($auction->load(['assignment', 'properties', 'attachments']), $tenantId);

        return response()->json([
            'success' => true,
            'message' => 'تمت جدولة إعادة التوليد بنجاح.',
        ]);
    }
}
