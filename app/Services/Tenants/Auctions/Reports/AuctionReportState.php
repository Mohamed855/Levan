<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use App\Enums\Tenants\Auctions\AuctionReportContentStatus;
use App\Models\Tenants\Auctions\Auction;

final class AuctionReportState
{
    public function fromData(array $campaigns, bool $closingSaved, array $results): AuctionReportContentStatus
    {
        return app(AuctionMarketingCompleteness::class)->isComplete($campaigns) ? AuctionReportContentStatus::COMPLETE : AuctionReportContentStatus::INCOMPLETE;
    }

    public function forAuction(Auction $auction): AuctionReportContentStatus
    {
        return app(AuctionMarketingCompleteness::class)->forAuction($auction) ? AuctionReportContentStatus::COMPLETE : AuctionReportContentStatus::INCOMPLETE;
    }
}
