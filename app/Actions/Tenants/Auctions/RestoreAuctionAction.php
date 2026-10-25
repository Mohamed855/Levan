<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Auctions;

use App\Models\Tenants\Auctions\Auction;

final class RestoreAuctionAction
{
    public function execute(Auction $auction): Auction
    {
        $auction->restore();

        return $auction->refresh();
    }
}
