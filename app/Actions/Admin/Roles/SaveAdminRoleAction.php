<?php

declare(strict_types=1);

namespace App\Actions\Admin\Roles;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class SaveAdminRoleAction
{
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data): Role {
            $role = Role::query()->create(Arr::only($data, ['name', 'display_name']) + ['guard' => 'admin']);
            $this->syncPermissions($role, $data['permission_ids'] ?? []);

            return $role->load('permissions');
        });
    }

    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data): Role {
            $role->update(Arr::only($data, ['name', 'display_name']));
            $this->syncPermissions($role, $data['permission_ids'] ?? []);

            return $role->fresh('permissions');
        });
    }

    private function syncPermissions(Role $role, array $permissionIds): void
    {
        $role->permissions()->sync(Permission::query()->where('guard', 'admin')->whereIn('id', $permissionIds)->pluck('id')->all());
    }
}
