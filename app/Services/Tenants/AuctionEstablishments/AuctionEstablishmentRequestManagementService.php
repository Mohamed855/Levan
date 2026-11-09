<?php

declare(strict_types=1);

namespace App\Services\Tenants\AuctionEstablishments;

use App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest;
use App\Models\Tenants\Auctions\Auction;

final class AuctionEstablishmentRequestManagementService
{
    public function statistics(): array
    {
        $total = Auction::query()->count();
        $submitted = AuctionEstablishmentRequest::query()->whereHas('auction')->count();
        $approved = AuctionEstablishmentRequest::query()->whereHas('auction')->whereNotNull('approved_at')->whereRaw("TRIM(COALESCE(approval_number, '')) <> ''")->count();

        return ['total' => $total, 'not_started' => $total - $submitted, 'under_review' => $submitted - $approved, 'approved' => $approved];
    }
}
