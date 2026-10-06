<?php

namespace App\Actions\Auth;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\TwoFactorAuthenticationProvider;

class ResolveTwoFactorLogin
{
    public function __construct(protected TwoFactorAuthenticationProvider $provider) {}

    public function verify(string $guard, Request $request, array $credentials): Authenticatable
    {
        $userId = $request->session()->get('login.id');

        abort_unless($userId, 419);

        $user = Auth::guard($guard)->getProvider()->retrieveById($userId);

        abort_unless($user, 419);

        if (! empty($credentials['code'])) {
            if (! $this->provider->verify(decrypt($user->two_factor_secret), $credentials['code'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'code' => 'الكود غير صحيح.',
                ]);
            }
        } elseif (! empty($credentials['recovery_code'])) {
            $this->consumeRecoveryCode($user, $credentials['recovery_code']);
        } else {
            throw \Illuminate\Validation\ValidationException::withMessages([
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
        $recoveryCodes = collect(json_decode(decrypt($user->two_factor_recovery_codes), true));

        if (! $recoveryCodes->contains($code)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'recovery_code' => 'كود الاسترجاع غير صحيح.',
            ]);
        }

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(
                $recoveryCodes->reject(fn($c) => $c === $code)->values()->toJson()
            ),
        ])->save();
    }
}
