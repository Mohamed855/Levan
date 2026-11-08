<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use App\Enums\Tenants\Auctions\AuctionReportContentStatus;
use App\Models\Tenants\Auctions\Auction;

final class AuctionReportState
{
    public function fromData(array $campaigns, bool $closingSaved, array $results): AuctionReportContentStatus
    {
        if ($closingSaved && $results !== [] && count(array_filter($results, static fn ($status) => $status === 'sold')) === count($results)) {
            return AuctionReportContentStatus::ENDED;
        }

        return $campaigns === [] ? AuctionReportContentStatus::INCOMPLETE : AuctionReportContentStatus::COMPLETE;
    }

    public function forAuction(Auction $auction): AuctionReportContentStatus
    {
        $auction->loadMissing(['closing', 'marketingCampaigns', 'properties']);
        if ($auction->closing?->is_finalized || $auction->closing?->finalized_snapshot !== null) {
            return AuctionReportContentStatus::ENDED;
        }

        return $this->fromData($auction->marketingCampaigns->all(), $auction->closing !== null, $auction->properties->map(fn ($property) => $property->pivot->result_status)->all());
    }
}
