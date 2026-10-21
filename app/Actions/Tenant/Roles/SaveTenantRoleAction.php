<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Roles;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class SaveTenantRoleAction
{
    /** @param array<string, mixed> $data */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data): Role {
            $role = Role::query()->create(Arr::only($data, ['name', 'display_name']) + ['guard' => 'tenant']);
            $this->syncPermissions($role, $data['permission_ids'] ?? []);

            return $role->load('permissions');
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data): Role {
            $role->update(Arr::only($data, ['name', 'display_name']));
            $this->syncPermissions($role, $data['permission_ids'] ?? []);

            return $role->fresh('permissions');
        });
    }

    /** @param array<int, int|string> $permissionIds */
    private function syncPermissions(Role $role, array $permissionIds): void
    {
        $role->permissions()->sync(
            Permission::query()->where('guard', 'tenant')->whereIn('id', $permissionIds)->pluck('id')->all(),
        );
    }
}
