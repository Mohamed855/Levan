<?php

declare(strict_types=1);

namespace App\Policies\Admin;

use App\Models\Admin\AdminUser;
use App\Models\Admin\Bank;

final class BankPolicy
{
    public function view(AdminUser $user, Bank $bank): bool { return $user->hasPermissionTo('banks.view'); }
    public function create(AdminUser $user): bool { return $user->hasPermissionTo('banks.create'); }
    public function update(AdminUser $user, Bank $bank): bool { return $user->hasPermissionTo('banks.update'); }
    public function delete(AdminUser $user, Bank $bank): bool { return $user->hasPermissionTo('banks.update'); }
}
