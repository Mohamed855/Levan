<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Auth;

use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetPasswordAction
{
    public function execute(User $user, string $password): User
    {
        return DB::transaction(function () use ($user, $password): User {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            return $user;
        });
    }
}
