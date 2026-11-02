<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\PromotionalAdvertisements;

use App\Actions\Tenants\PromotionalAdvertisements\CreatePromotionalAdvertisementAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\PromotionalAdvertisements\StorePromotionalAdvertisementRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\PromotionalAdvertisements\PromotionalAdvertisement;
use App\Services\Tenants\Shared\CentralAuctionTemplatesGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PromotionalAdvertisementsController extends Controller
{
    public function __construct(
        private readonly CentralAuctionTemplatesGateway $centralGateway,
    ) {
        $this->middleware('auth:tenant');
    }

    /**
     * Show the creation form for a new promotional advertisement.
     */
    public function create(Request $request): View
    {
        $auctions = Auction::query()
            ->orderByDesc('id')
            ->get(['id', 'name'])
            ->map(fn (Auction $a) => [
                'value' => (string) $a->id,
                'label' => $a->name,
            ])
            ->values()
            ->toArray();

        $selectedAuctionId = $request->query('auction_id');

        $templatesGrouped = [
            'billboard' => $this->centralGateway->activeByType('billboard'),
            'social' => $this->centralGateway->activeByType('social'),
        ];

        return view('tenant.promotional-advertisements.create', [
            'auctionOptions' => $auctions,
            'selectedAuctionId' => $selectedAuctionId,
            'templatesGrouped' => $templatesGrouped,
        ]);
    }

    /**
     * Store a new promotional advertisement and redirect to auction show.
     */
    public function store(
        StorePromotionalAdvertisementRequest $request,
        CreatePromotionalAdvertisementAction $action,
    ): RedirectResponse {
        $ad = $action->execute($request->validated(), $request->user('tenant')?->id);

        return redirect()
            ->to(tenant_route_name('auctions.show', $ad->auction_id))
            ->with('success', 'تم إنشاء الإعلان الترويجي بنجاح وجارٍ إعداد الصورة.');
    }

    /**
     * Return active templates by type as JSON for Select2 dynamic population.
     */
    public function templatesByType(Request $request): JsonResponse
    {
        $type = (string) $request->query('type', 'billboard');
        if (! in_array($type, ['billboard', 'social'], true)) {
            return response()->json([]);
        }

        $templates = $this->centralGateway->activeByType($type);

        return response()->json($templates);
    }

    /**
     * Download the rendered advertisement image.
     */
    public function download(PromotionalAdvertisement $advertisement): StreamedResponse|RedirectResponse
    {
        abort_unless($advertisement->isReady() && filled($advertisement->output_path), 404, 'الصورة غير جاهزة للتحميل بعد.');

        $disk = Storage::disk('tenant_public');
        abort_unless($disk->exists($advertisement->output_path), 404, 'ملف الصورة غير موجود على القرص.');

        $filename = 'إعلان-مزاد-'.($advertisement->auction_id).'-'.$advertisement->id.'.png';

        return $disk->download($advertisement->output_path, $filename);
    }

    /**
     * Get the statuses of promotional advertisements for an auction (for live UI polling).
     */
    public function status(Auction $auction): JsonResponse
    {
        $ads = $auction->promotionalAdvertisements()
            ->get(['id', 'status', 'template_name', 'template_type', 'output_path', 'error_message'])
            ->map(fn (PromotionalAdvertisement $ad) => [
                'id' => $ad->id,
                'status' => $ad->status,
                'status_label' => $ad->status_label,
                'status_badge' => $ad->status_badge,
                'is_ready' => $ad->isReady(),
                'is_processing' => $ad->isProcessing(),
                'is_failed' => $ad->isFailed(),
                'download_url' => $ad->isReady() ? tenant_route_name('promotional-advertisements.download', $ad->id) : null,
                'error_message' => $ad->error_message,
            ]);

        return response()->json([
            'advertisements' => $ads,
            'all_ready' => $ads->every(fn ($a) => ! $a['is_processing']),
        ]);
    }
}
