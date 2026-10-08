<?php

declare(strict_types=1);

namespace App\Policies\Tenants\Auctions;

use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\UserMangment\User\User;

class AuctionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('auctions.view');
    }

    public function view(User $user, Auction $auction): bool
    {
        return $user->can('auctions.view') && tenancy()->initialized;
    }

    public function create(User $user): bool
    {
        return $user->can('auctions.create');
    }

    public function update(User $user, Auction $auction): bool
    {
        return $user->can('auctions.edit') && tenancy()->initialized;
    }

    public function delete(User $user, Auction $auction): bool
    {
        return $user->can('auctions.delete') && tenancy()->initialized;
    }

    public function restore(User $user, Auction $auction): bool
    {
        return $user->can('auctions_archive.restore') && tenancy()->initialized;
    }

    public function forceDelete(User $user, Auction $auction): bool
    {
        return $user->can('auctions_archive.force_delete') && tenancy()->initialized;
    }
}
