<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Auctions;

use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\AuctionService;

final class ForceDeleteAuctionAction
{
    public function __construct(private readonly AuctionService $auctionService) {}

    public function execute(Auction $auction): bool
    {
        return $this->auctionService->forceDeleteAuction($auction);
    }
}
