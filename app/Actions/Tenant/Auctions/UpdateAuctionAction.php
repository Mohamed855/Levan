<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Auctions;

use App\Models\Tenant\Auction;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;

final class UpdateAuctionAction
{
    public function __construct(private readonly StoreAuctionAction $storeAuction) {}

    /** @param array<string, mixed> $data */
    public function execute(Auction $auction, User $user, array $data): Auction
    {
        return DB::transaction(function () use ($auction, $data): Auction {
            [$versions, $snapshots] = $this->storeAuction->snapshots($data['template_versions'] ?? []);
            $auction->update(array_merge($data, [
                'template_versions' => $versions,
                'template_snapshot' => $snapshots,
            ]));

            return $auction->refresh();
        });
    }
}