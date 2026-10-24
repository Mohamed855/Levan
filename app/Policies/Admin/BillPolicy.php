<?php

declare(strict_types=1);

namespace App\Policies\Admin;

use App\Models\Admin\AdminUser;
use App\Models\Admin\Bill;

final class BillPolicy
{
    public function view(AdminUser $user, Bill $bill): bool
    {
        return $user->hasPermissionTo('bills.view');
    }

    public function update(AdminUser $user, Bill $bill): bool
    {
        return $user->hasPermissionTo('bills.update');
    }

    public function delete(AdminUser $user, Bill $bill): bool
    {
        return $user->hasPermissionTo('bills.delete');
    }

    public function restore(AdminUser $user, Bill $bill): bool
    {
        return $user->hasPermissionTo('archives.restore');
    }

    public function forceDelete(AdminUser $user, Bill $bill): bool
    {
        return $user->hasPermissionTo('archives.force_delete');
    }
}
