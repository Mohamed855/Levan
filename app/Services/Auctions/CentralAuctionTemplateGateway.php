<?php

declare(strict_types=1);

namespace App\Services\Auctions;

use App\Models\Admin\AuctionTemplateVersion;
use Illuminate\Database\Eloquent\Collection;

/**
 * Central boundary for tenant consumers. It will keep the same public contract
 * when auction data moves to a separate tenant connection during provisioning.
 */
final class CentralAuctionTemplateGateway
{
    /** @return Collection<int, AuctionTemplateVersion> */
    public function publishedByType(string $type): Collection
    {
        return AuctionTemplateVersion::query()
            ->with('template:id,name,type,is_active,status')
            ->where('status', 'published')
            ->whereHas('template', fn ($query) => $query->published()->where('type', $type))
            ->latest('published_at')
            ->get();
    }

    public function findPublished(int $versionId, string $type): ?AuctionTemplateVersion
    {
        return AuctionTemplateVersion::query()
            ->with('template:id,name,type,is_active,status')
            ->whereKey($versionId)
            ->where('status', 'published')
            ->whereHas('template', fn ($query) => $query->published()->where('type', $type))
            ->first();
    }
}