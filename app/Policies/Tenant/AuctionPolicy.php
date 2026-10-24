<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Auction;
use App\Models\Tenant\User;

final class AuctionPolicy
{
    public function view(User $user, Auction $auction): bool { return $user->hasPermissionTo('auctions.view'); }
    public function create(User $user): bool { return $user->hasPermissionTo('auctions.create'); }
    public function update(User $user, Auction $auction): bool { return $user->hasPermissionTo('auctions.edit'); }
    public function delete(User $user, Auction $auction): bool { return $user->hasPermissionTo('auctions.edit'); }
}
