<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Auth;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetTenantPasswordAction
{
    public function execute(User $user, string $password): User
    {
        return DB::transaction(function () use ($user, $password): User {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
                'must_change_password' => false,
            ])->save();

            return $user;
        });
    }

    public function executeForTenant(string $tenantId, string $email, string $password): ?User
    {
        tenancy()->initialize($tenantId);

        try {
            $user = User::query()->where('email', $email)->first();

            return $user === null ? null : $this->execute($user, $password);
        } finally {
            tenancy()->end();
        }
    }
}
