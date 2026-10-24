<?php

declare(strict_types=1);

namespace App\Actions\Admin\Users;

use App\Models\Access\Role;
use App\Models\Admin\AdminUser;
use App\Enums\Admin\Users\AdminUserStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

final class SaveAdminUserAction
{
    private const SYSTEM_ROLES = ['SuperAdmin', 'manager'];

    public function create(array $data, AdminUser $actor): AdminUser
    {
        return DB::transaction(function () use ($data, $actor): AdminUser {
            $roles = $this->roles($data['role_ids'] ?? []);
            $this->assertActorCanAssignSystemRoles($actor, $roles);

            $user = AdminUser::query()->create(Arr::except($data, 'role_ids'));
            $this->syncRoles($user, $roles);

            return $user;
        });
    }

    public function update(AdminUser $user, array $data, AdminUser $actor): AdminUser
    {
        return DB::transaction(function () use ($user, $data, $actor): AdminUser {
            $lockedUser = AdminUser::query()->with('roles')->lockForUpdate()->findOrFail($user->getKey());
            $roles = $this->roles($data['role_ids'] ?? []);
            $this->assertActorCanAssignSystemRoles($actor, $roles);
            $this->assertActorKeepsOwnAccountActive($lockedUser, $actor, (string) $data['status']);
            $this->assertActiveSuperAdminRemains($lockedUser, (string) $data['status'], $roles);

            $lockedUser->update(Arr::except($data, 'role_ids'));
            $this->syncRoles($lockedUser, $roles);

            return $lockedUser->fresh('roles');
        });
    }

    public function updateStatus(AdminUser $user, string $status): AdminUser
    {
        return DB::transaction(function () use ($user, $status): AdminUser {
            $lockedUser = AdminUser::query()->with('roles')->lockForUpdate()->findOrFail($user->getKey());
            $this->assertActiveSuperAdminRemains($lockedUser, $status, $lockedUser->roles);
            $lockedUser->update(['status' => $status]);

            return $lockedUser->fresh();
        });
    }

    /** @param array<int, int|string> $roleIds */
    private function roles(array $roleIds): \Illuminate\Support\Collection
    {
        return Role::query()
            ->where('guard', 'admin')
            ->whereIn('id', $roleIds)
            ->get();
    }

    private function assertActorCanAssignSystemRoles(AdminUser $actor, \Illuminate\Support\Collection $roles): void
    {
        if ($roles->whereIn('name', self::SYSTEM_ROLES)->isNotEmpty() && ! $actor->isSuperAdmin()) {
            throw new AuthorizationException('لا يحق إلا لـSuperAdmin إسناد أدوار النظام المحمية.');
        }
    }

    private function assertActiveSuperAdminRemains(AdminUser $user, string $newStatus, \Illuminate\Support\Collection $roles): void
    {
        $isActiveSuperAdmin = $user->status === AdminUserStatus::ACTIVE
            && $user->roles->contains('name', 'SuperAdmin');
        $willRemainActiveSuperAdmin = $newStatus === AdminUserStatus::ACTIVE->value
            && $roles->contains('name', 'SuperAdmin');

        if (! $isActiveSuperAdmin || $willRemainActiveSuperAdmin) {
            return;
        }

        $anotherActiveSuperAdminExists = AdminUser::query()
            ->whereKeyNot($user->getKey())
            ->where('status', AdminUserStatus::ACTIVE->value)
            ->whereHas('roles', fn ($query) => $query->where('name', 'SuperAdmin'))
            ->exists();

        if (! $anotherActiveSuperAdminExists) {
            throw ValidationException::withMessages([
                'status' => 'لا يمكن تعطيل أو إزالة آخر SuperAdmin نشط.',
            ]);
        }
    }

    private function assertActorKeepsOwnAccountActive(AdminUser $user, AdminUser $actor, string $newStatus): void
    {
        if ($user->is($actor) && $newStatus !== AdminUserStatus::ACTIVE->value) {
            throw ValidationException::withMessages([
                'status' => 'لا يمكنك تعطيل حسابك الشخصي.',
            ]);
        }
    }

    private function syncRoles(AdminUser $user, \Illuminate\Support\Collection $roles): void
    {
        $user->roles()->syncWithPivotValues($roles->modelKeys(), ['guard' => 'admin']);
    }
}
