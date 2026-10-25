<?php

declare(strict_types=1);

namespace App\Policies\Admin\AuctionTemplates;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\AuctionTemplates\AuctionTemplateSample;

final class AuctionTemplateSamplePolicy
{
    public function viewAny(AdminUser $user): bool
    {
        return $user->can('auction_template_samples.view');
    }

    public function view(AdminUser $user, AuctionTemplateSample $sample): bool
    {
        return $user->can('auction_template_samples.view');
    }

    public function create(AdminUser $user): bool
    {
        return $user->can('auction_template_samples.create');
    }

    public function update(AdminUser $user, AuctionTemplateSample $sample): bool
    {
        return $user->can('auction_template_samples.edit');
    }

    public function delete(AdminUser $user, AuctionTemplateSample $sample): bool
    {
        return $user->can('auction_template_samples.delete');
    }
}
