<?php

declare(strict_types=1);

namespace App\Policies\Admin\AuctionTemplates;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\AuctionTemplates\AuctionTemplate;

class AuctionTemplatePolicy
{
    public function viewAny(AdminUser $user): bool
    {
        return $user->can('auction_templates.view');
    }

    public function view(AdminUser $user, AuctionTemplate $auctionTemplate): bool
    {
        return $user->can('auction_templates.view');
    }

    public function create(AdminUser $user): bool
    {
        return $user->can('auction_templates.create');
    }

    public function update(AdminUser $user, AuctionTemplate $auctionTemplate): bool
    {
        return $user->can('auction_templates.edit');
    }

    public function delete(AdminUser $user, AuctionTemplate $auctionTemplate): bool
    {
        return $user->can('auction_templates.delete');
    }

    public function restore(AdminUser $user, AuctionTemplate $auctionTemplate): bool
    {
        return $user->can('auction_templates.restore');
    }

    public function forceDelete(AdminUser $user, AuctionTemplate $auctionTemplate): bool
    {
        return $user->can('auction_templates.force_delete');
    }
}
