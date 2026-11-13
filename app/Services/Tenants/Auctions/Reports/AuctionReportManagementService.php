<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use App\Models\Tenants\MarketingCampaigns\MarketingCampaign;

final class AuctionReportManagementService
{
    public function auctionOptions(): array
    {
        return Auction::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function campaignStatistics(): array
    {
        return ['total' => MarketingCampaign::count(), 'auctions' => MarketingCampaign::distinct()->count('auction_id'), 'evidence' => AuctionAttachment::whereNotNull('marketing_campaign_id')->count()];
    }

    public function closingStatistics(): array
    {
        $total = Auction::count();
        $saved = Auction::has('closing')->open()->count();
        $ended = Auction::closed()->count();

        return ['total' => $total, 'not_started' => $total - $saved - $ended, 'saved' => $saved, 'ended' => $ended];
    }

    public function editableAuctions(): array
    {
        return Auction::query()->open()->orderByDesc('id')->get(['id', 'name'])->map(fn ($auction) => ['value' => $auction->id, 'label' => $auction->name])->all();
    }

    public function campaignPropertyOptions(): array
    {
        return Auction::query()->open()
            ->with('properties:id,name,property_number')->get(['id'])->mapWithKeys(fn ($auction) => [$auction->id => $auction->properties->map(fn ($property) => ['value' => $property->id, 'label' => $property->name.' — '.$property->property_number])->all()])->all();
    }
}
