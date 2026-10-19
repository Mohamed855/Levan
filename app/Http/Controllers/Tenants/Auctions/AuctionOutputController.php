<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Auctions;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\AuctionOutputService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

final class AuctionOutputController extends Controller
{
    public function __construct(
        private readonly AuctionOutputService $service
    ) {
        $this->middleware('auth:tenant');
        $this->middleware('can:auctions.view');
    }

    public function brochure(Auction $auction): BinaryFileResponse
    {
        $this->authorize('view', $auction);

        try {
            $path = $this->service->generateBrochure($auction);
            $fileName = "بروشور-مزاد-{$auction->id}.pdf";

            return response()->download($path, $fileName)->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }
    }

    public function billboard(Auction $auction): BinaryFileResponse
    {
        $this->authorize('view', $auction);

        try {
            $path = $this->service->generateBillboard($auction);
            $fileName = "لوحة-إعلانية-مزاد-{$auction->id}.pdf";

            return response()->download($path, $fileName)->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }
    }

    public function social(Auction $auction): BinaryFileResponse
    {
        $this->authorize('view', $auction);

        try {
            $path = $this->service->generateSocial($auction);
            $fileName = "سوشيال-ميديا-مزاد-{$auction->id}.pdf";

            return response()->download($path, $fileName)->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            abort(500, $e->getMessage());
        }
    }
}
