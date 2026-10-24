<?php

declare(strict_types=1);

namespace App\Policies\Admin;

use App\Models\Admin\AdminUser;
use App\Models\Admin\AuctionTemplate;

final class AuctionTemplatePolicy
{
    public function viewAny(AdminUser $actor): bool { return $actor->hasPermissionTo('auction_templates.view'); }
    public function view(AdminUser $actor, AuctionTemplate $template): bool { return $actor->hasPermissionTo('auction_templates.view'); }
    public function create(AdminUser $actor): bool { return $actor->hasPermissionTo('auction_templates.create'); }
    public function update(AdminUser $actor, AuctionTemplate $template): bool { return $actor->hasPermissionTo('auction_templates.edit'); }
    public function delete(AdminUser $actor, AuctionTemplate $template): bool { return $actor->hasPermissionTo('auction_templates.delete'); }
    public function publish(AdminUser $actor, AuctionTemplate $template): bool { return $actor->hasPermissionTo('auction_templates.publish'); }
}
