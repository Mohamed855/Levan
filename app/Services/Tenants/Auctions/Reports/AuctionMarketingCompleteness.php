<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use App\Http\Requests\Tenants\MarketingCampaigns\SaveMarketingCampaignRequest;
use App\Models\Tenants\Auctions\Auction;
use Illuminate\Support\Facades\Validator;

final class AuctionMarketingCompleteness
{
    public function isComplete(array $campaigns, ?array $propertyIds = null): bool
    {
        foreach (array_keys(config('auction-marketing-campaigns')) as $channel) {
            $valid = false;
            foreach ($campaigns as $campaign) {
                $data = is_array($campaign) ? $campaign : $campaign->toArray();
                if (($data['channel'] ?? null) !== $channel) {
                    continue;
                }
                $request = new SaveMarketingCampaignRequest;
                $request->merge($data);
                $rules = $request->rules();
                unset($rules['auction_id'], $rules['metrics.property_id']);
                if ($channel === 'board_before') {
                    $rules['metrics.property_id'] = ['required', 'integer', 'min:1'];
                    if ($propertyIds !== null && ! in_array((int) ($data['metrics']['property_id'] ?? 0), $propertyIds, true)) {
                        continue;
                    }
                }
                if (Validator::make($data, $rules)->fails()) {
                    continue;
                }
                if (in_array($channel, config('auction-marketing-fields.links'), true) && app(AuctionReportLinks::class)->safeUrl($data['url'] ?? null) === null) {
                    continue;
                }
                $metrics = $data['metrics'] ?? [];
                if ($channel === 'whatsapp' && (float) $metrics['total'] !== (float) $metrics['delivered'] + (float) $metrics['failed'] + (float) $metrics['pending']) {
                    continue;
                }
                $valid = true;
                break;
            }
            if (! $valid) {
                return false;
            }
        }

        return config('auction-marketing-campaigns') !== [];
    }

    public function forAuction(Auction $auction): bool
    {
        $auction->loadMissing(['marketingCampaigns', 'properties']);

        return $this->isComplete($auction->marketingCampaigns->all(), array_map('intval', $auction->properties->modelKeys()));
    }

    public function completeAuctionIds(): array
    {
        $ids = [];
        Auction::query()->with(['marketingCampaigns', 'properties:id'])->select('id')->chunkById(200, function ($auctions) use (&$ids): void {
            foreach ($auctions as $auction) {
                if ($this->forAuction($auction)) {
                    $ids[] = $auction->id;
                }
            }
        });

        return $ids;
    }
}
