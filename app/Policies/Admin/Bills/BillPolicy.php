<?php

declare(strict_types=1);

namespace App\Policies\Admin\Bills;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;

class BillPolicy
{
    public function viewAny(AdminUser $user): bool { return $user->can('financial_transactions.view'); }
    public function view(AdminUser $user, Bill $bill): bool { return $user->can('financial_transactions.view'); }
    public function create(AdminUser $user): bool { return false; }
    public function update(AdminUser $user, Bill $bill): bool { return false; }
    public function delete(AdminUser $user, Bill $bill): bool { return false; }
    public function verify(AdminUser $user, Bill $bill): bool { return $user->can('subscriptions.pending.approve'); }
    public function reject(AdminUser $user, Bill $bill): bool { return $user->can('subscriptions.pending.reject'); }
    public function reactivate(AdminUser $user, Bill $bill): bool { return $user->can('subscriptions.pending.approve'); }
}
