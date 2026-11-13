<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Auctions;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class AuctionRegulatoryFormsController extends Controller
{
    public function __construct(private readonly AuctionRegulatoryFormService $forms)
    {
        $this->middleware('auth:tenant');
    }

    public function status(Auction $auction, string $type): JsonResponse
    {
        $this->authorizeForm($auction, $type);
        $this->forms->startPendingGeneration($auction, $type, (string) tenant('id'));

        return $this->state($auction, $type);
    }

    public function start(Auction $auction, string $type): JsonResponse
    {
        $this->authorizeForm($auction, $type);
        $this->forms->startPendingGeneration($auction, $type, (string) tenant('id'));

        return $this->state($auction, $type);
    }

    public function retry(Auction $auction, string $type): JsonResponse
    {
        $this->authorizeForm($auction, $type, 'regenerate');
        try {
            $this->forms->retryLatest($auction, $type);
            $this->forms->startPendingGeneration($auction, $type, (string) tenant('id'));

            return $this->state($auction, $type, 202);
        } catch (Throwable $exception) {
            Log::error('Regulatory form retry failed.', ['auction_id' => $auction->id, 'type' => $type, 'exception' => $exception]);

            return response()->json(['status' => 'failed', 'message' => 'تعذر تجهيز النموذج. يرجى إعادة المحاولة.'], 422);
        }
    }

    public function download(Auction $auction, string $type): StreamedResponse
    {
        $this->authorizeForm($auction, $type);
        $published = $this->forms->published($auction, $type);
        abort_unless($published?->isReady() && Storage::disk('tenant_private')->exists($published->file_path), 404);

        return Storage::disk('tenant_private')->download($published->file_path, 'auction-'.$auction->id.'-'.$type.'.pdf');
    }

    private function authorizeForm(Auction $auction, string $type, string $ability = 'view'): void
    {
        $permission = match ($type) {
            'establishment' => 'auction_establishment_requests',
            'closing' => 'auction_closings',
            default => abort(404),
        };
        Gate::authorize($permission.'.'.$ability);
        $request = $auction->establishmentRequest;
        abort_unless($request?->exists && is_array($request->data) && ! empty($request->data), 404);
        if ($type === 'closing') {
            abort_unless($request->isApproved() && $auction->isClosed() && $auction->closing?->saved_at, 404);
        }
    }

    private function state(Auction $auction, string $type, int $httpStatus = 200): JsonResponse
    {
        $generation = $this->forms->latest($auction, $type);
        $published = $this->forms->published($auction, $type);

        return response()->json([
            'status' => $generation?->status ?? 'none', 'progress' => (int) ($generation?->progress ?? 0),
            'generation_id' => $generation?->id, 'has_file' => (bool) $published?->isReady(),
            'download_url' => $published?->isReady() ? tenant_route_name('auctions.forms.download', ['auction' => $auction->id, 'type' => $type]) : null,
            'error_message' => $generation?->publicErrorMessage(),
        ], $httpStatus);
    }
}
