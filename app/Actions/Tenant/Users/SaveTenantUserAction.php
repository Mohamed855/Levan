<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Users;

use App\Models\Access\Role;
use App\Models\Tenant\User;
use App\Services\Tenant\Shared\CentralLicenseGateway;
use Illuminate\Support\Arr;

final class SaveTenantUserAction
{
    public function __construct(private readonly CentralLicenseGateway $licenses) {}

    /** @param array<string, mixed> $data */
    public function create(array $data): User
    {
        $this->licenses->assertCanCreateUser();

        $user = User::query()->create(Arr::only($data, [
            'name', 'email', 'phone', 'password', 'status',
        ]));

        try {
            $this->syncRoles($user, $data['role_ids'] ?? []);
            $this->licenses->assignCurrentTenantUser((int) $user->getKey());

            return $user->load('roles');
        } catch (\Throwable $exception) {
            // The databases cannot share a transaction; compensate the tenant write on entitlement failure.
            $user->forceDelete();
            throw $exception;
        }
    }

    /** @param array<string, mixed> $data */
    public function update(User $user, array $data): User
    {
        $attributes = Arr::only($data, ['name', 'email', 'phone', 'status']);
        if (filled($data['password'] ?? null)) {
            $attributes['password'] = $data['password'];
        }
        $user->update($attributes);
        $this->syncRoles($user, $data['role_ids'] ?? []);

        return $user->fresh('roles');
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
