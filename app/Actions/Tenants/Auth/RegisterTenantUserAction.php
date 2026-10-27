<?php

declare(strict_types=1);

namespace App\Actions\Tenants\Auth;

use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Shared\CentralTrialAccessGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterTenantUserAction
{
    public function execute(array $data): User
    {
        app(CentralTrialAccessGateway::class)->assertUserCreationAllowed();

        return DB::transaction(fn (): User => User::create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'global_uid' => (string) Str::uuid(),
            'status' => 'active',
        ]));
    }
}
