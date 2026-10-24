<?php

declare(strict_types=1);

namespace App\Policies\Admin\Subscriptions;

use App\Models\Admin\AdminUser;
use App\Models\Admin\Subscriptions\Subscription;

final class SubscriptionPolicy
{
    public function view(AdminUser $user, Subscription $subscription): bool { return $user->hasPermissionTo('subscriptions.view'); }
    public function update(AdminUser $user, Subscription $subscription): bool { return $user->hasPermissionTo('subscriptions.update'); }
    public function delete(AdminUser $user, Subscription $subscription): bool { return $user->hasPermissionTo('subscriptions.delete'); }
}
