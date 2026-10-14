<?php

declare(strict_types=1);

namespace App\Policies\Tenants\GeneralSetting;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Model;

class LookupDeletePolicy
{
    protected const PERMISSION = '';

    protected const ALLOW_SYSTEM_SETTINGS = false;

    public function delete(User $user, Model $model): bool
    {
        return $this->allowed($user);
    }

    public function viewAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->allowed($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->allowed($user);
    }

    public function toggleStatus(User $user, Model $model): bool
    {
        return $this->update($user, $model);
    }

    private function allowed(User $user): bool
    {
        return static::PERMISSION !== ''
            && ($user->can(static::PERMISSION)
                || (static::ALLOW_SYSTEM_SETTINGS && $user->can('system.settings')));
    }
}
