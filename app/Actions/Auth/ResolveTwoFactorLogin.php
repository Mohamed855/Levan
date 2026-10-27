<?php

namespace App\Actions\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\TwoFactorAuthenticationProvider;

class ResolveTwoFactorLogin
{
    public function __construct(
        protected TwoFactorAuthenticationProvider $provider,
        protected PersistAuthenticationStateAction $authenticationState
    ) {}

    public function verify(string $guard, Request $request, array $credentials): Authenticatable
    {
        $userId = $request->session()->get('login.id');

        abort_unless($userId, 419);

        $user = Auth::guard($guard)->getProvider()->retrieveById($userId);

        abort_unless($user, 419);

        if (! empty($credentials['code'])) {
            try {
                $secret = decrypt($user->two_factor_secret);
            } catch (\Throwable) {
                throw ValidationException::withMessages([
                    'code' => 'الكود غير صحيح.',
                ]);
            }

            if (! $this->provider->verify($secret, $credentials['code'])) {
                throw ValidationException::withMessages([
                    'code' => 'الكود غير صحيح.',
                ]);
            }
        } elseif (! empty($credentials['recovery_code'])) {
            $this->consumeRecoveryCode($user, $credentials['recovery_code']);
        } else {
            throw ValidationException::withMessages([
                'code' => 'الرجاء إدخال الكود أو كود الاسترجاع.',
            ]);
        }

        return $user;
    }

    /*
    |============================================================================
    |============================================================================
    |                       Private Methods
    |============================================================================
    |============================================================================
    */
    protected function consumeRecoveryCode(Authenticatable $user, string $code): void
    {
        try {
            $recoveryCodes = collect(json_decode(decrypt($user->two_factor_recovery_codes), true, 512, JSON_THROW_ON_ERROR) ?: []);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'recovery_code' => 'كود الاسترجاع غير صحيح.',
            ]);
        }

        if (! $recoveryCodes->contains($code)) {
            throw ValidationException::withMessages([
                'recovery_code' => 'كود الاسترجاع غير صحيح.',
            ]);
        }

        $remainingCodes = $recoveryCodes->reject(fn ($c) => $c === $code)->values()->all();
        $this->authenticationState->replaceRecoveryCodes($user, $remainingCodes);
    }
}
