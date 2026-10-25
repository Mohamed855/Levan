<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PersistAuthenticationStateAction
{
    public function recordLogin(Authenticatable $user, string $ipAddress, ?string $sessionId = null): void
    {
        $attributes = [
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ];

        if ($sessionId !== null) {
            $attributes['current_session_id'] = $sessionId;
        }

        $user->update($attributes);
    }

    public function clearCurrentSession(Authenticatable $user): void
    {
        $user->update(['current_session_id' => null]);
    }

    public function replaceRecoveryCodes(Authenticatable $user, array $codes): void
    {
        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($codes, JSON_THROW_ON_ERROR)),
        ])->save();
    }

    public function updatePassword(Authenticatable $user, string $password, bool $clearPasswordChangeRequirement = false): void
    {
        $attributes = ['password' => Hash::make($password)];

        if ($clearPasswordChangeRequirement) {
            $attributes['must_change_password'] = false;
        }

        $user->update($attributes);
    }

    public function resetPassword(Authenticatable $user, string $password): void
    {
        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
            'must_change_password' => false,
        ])->save();
    }
}
