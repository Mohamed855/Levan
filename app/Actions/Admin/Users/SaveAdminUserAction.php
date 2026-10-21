<?php

declare(strict_types=1);

namespace App\Actions\Admin\Users;

use App\Models\Access\Role;
use App\Models\Admin\AdminUser;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class SaveAdminUserAction
{
    public function create(array $data): AdminUser
    {
        return DB::transaction(function () use ($data): AdminUser {
            $user = AdminUser::query()->create(Arr::except($data, 'role_ids'));
            $this->syncRoles($user, $data['role_ids'] ?? []);
            return $user;
        });
    }

    public function update(AdminUser $user, array $data): AdminUser
    {
        return DB::transaction(function () use ($user, $data): AdminUser {
            $user->update(Arr::except($data, 'role_ids'));
            $this->syncRoles($user, $data['role_ids'] ?? []);
            return $user;
        });
    }

    private function syncRoles(AdminUser $user, array $roleIds): void
    {
        $user->roles()->syncWithPivotValues(Role::query()->where('guard', 'admin')->whereIn('id', $roleIds)->pluck('id')->all(), ['guard' => 'admin']);
    }
}
