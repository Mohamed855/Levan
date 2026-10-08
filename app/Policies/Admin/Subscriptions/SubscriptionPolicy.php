<?php

declare(strict_types=1);

namespace App\Policies\Admin\Subscriptions;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Subscription\Subscription;

class SubscriptionPolicy
{
    public function viewAny(AdminUser $user): bool { return $user->can('subscriptions.view'); }
    public function view(AdminUser $user, Subscription $subscription): bool { return $user->can('subscriptions.view'); }
    public function create(AdminUser $user): bool { return $user->can('subscriptions.create'); }
    public function update(AdminUser $user, Subscription $subscription): bool { return $user->can('subscriptions.toggle_status'); }
    public function delete(AdminUser $user, Subscription $subscription): bool { return $user->can('subscriptions.delete'); }
}
