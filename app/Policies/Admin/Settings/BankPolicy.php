<?php

declare(strict_types=1);

namespace App\Policies\Admin\Settings;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bank\Bank;

class BankPolicy
{
    public function viewAny(AdminUser $user): bool
    {
        return $user->can('bank_settings.view');
    }

    public function view(AdminUser $user, Bank $bank): bool
    {
        return $user->can('bank_settings.view');
    }

    public function create(AdminUser $user): bool
    {
        return $user->can('bank_settings.view');
    }

    public function update(AdminUser $user, Bank $bank): bool
    {
        return $user->can('bank_settings.view');
    }

    public function delete(AdminUser $user, Bank $bank): bool
    {
        return $user->can('bank_settings.view');
    }
}
