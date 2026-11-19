<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use App\Enums\Tenants\Auctions\AuctionReportContentStatus;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\AuctionCompletenessService;

final class AuctionReportState
{
    public function forAuction(Auction $auction): AuctionReportContentStatus
    {
        return app(AuctionCompletenessService::class)->isComplete($auction)
            ? AuctionReportContentStatus::COMPLETE
            : AuctionReportContentStatus::INCOMPLETE;
    }
}
