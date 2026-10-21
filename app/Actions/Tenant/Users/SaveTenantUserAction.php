<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Users;

use App\Models\Access\Role;
use App\Models\Tenant\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class SaveTenantUserAction
{
    /** @param array<string, mixed> $data */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::query()->create(Arr::only($data, [
                'name', 'email', 'phone', 'password', 'status',
            ]));
            $this->syncRoles($user, $data['role_ids'] ?? []);

            return $user->load('roles');
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $attributes = Arr::only($data, ['name', 'email', 'phone', 'status']);
            if (filled($data['password'] ?? null)) {
                $attributes['password'] = $data['password'];
            }
            $user->update($attributes);
            $this->syncRoles($user, $data['role_ids'] ?? []);

            return $user->fresh('roles');
        });
    }

    /** @param array<int, int|string> $roleIds */
    private function syncRoles(User $user, array $roleIds): void
    {
        $ids = Role::query()
            ->where('guard', 'tenant')
            ->whereIn('id', $roleIds)
            ->pluck('id')
            ->all();

        $user->roles()->syncWithPivotValues($ids, ['guard' => 'tenant']);
    }
}
