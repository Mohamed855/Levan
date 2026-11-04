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
        $sessionGuard = $request->session()->get('login.guard');
        $sessionTenantId = $request->session()->get('login.tenant_id');

        abort_unless($userId, 419);

        // Fail-closed: session guard must be present and match expected guard exactly
        if ($sessionGuard !== $guard) {
            abort(419);
        }

        // Fail-closed: for tenant guard, tenant binding must be present and match current tenant
        if ($guard === 'tenant') {
            $currentTenantId = function_exists('tenant') ? tenant('id') : null;
            if ($sessionTenantId === null || $currentTenantId === null || (string) $sessionTenantId !== (string) $currentTenantId) {
                abort(419);
            }
        }

        $user = Auth::guard($guard)->getProvider()->retrieveById($userId);

        abort_unless($user, 419);

        // التحقق من أن الحساب لا يزال نشطاً ولم يُعطل بعد خطوة كلمة المرور
        $statusValue = is_object($user->status) && enum_exists($user->status::class)
            ? $user->status->value
            : (string) ($user->status ?? 'active');

        if ($statusValue !== 'active') {
            throw ValidationException::withMessages([
                'code' => 'حسابك غير نشط.',
            ]);
        }

        // التحقق من أن 2FA لا يزال مؤكداً ومفعلاً ولم يُلغَ أو يُعطل
        if (empty($user->two_factor_secret) || empty($user->two_factor_confirmed_at)) {
            abort(403, 'التحقق بخطوتين غير مؤكد لهذا الحساب.');
        }

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
        /** @var \Illuminate\Database\Eloquent\Model $userModel */
        $userModel = $user;
        $connection = $userModel->getConnection();

        $connection->transaction(function () use ($userModel, $code): void {
            /** @var \Illuminate\Database\Eloquent\Model|null $freshUser */
            $freshUser = $userModel->newQuery()->lockForUpdate()->find($userModel->getKey());

            if (! $freshUser) {
                throw ValidationException::withMessages([
                    'recovery_code' => 'كود الاسترجاع غير صحيح.',
                ]);
            }

            $statusValue = $freshUser->status instanceof \BackedEnum ? $freshUser->status->value : (string) $freshUser->status;
            if ($statusValue !== 'active' || empty($freshUser->two_factor_secret) || empty($freshUser->two_factor_confirmed_at)) {
                throw ValidationException::withMessages([
                    'recovery_code' => 'كود الاسترجاع غير صحيح.',
                ]);
            }

            try {
                $recoveryCodes = collect(json_decode(decrypt($freshUser->two_factor_recovery_codes), true, 512, JSON_THROW_ON_ERROR) ?: []);
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
            $this->authenticationState->replaceRecoveryCodes($freshUser, $remainingCodes);
            $userModel->setRawAttributes($freshUser->getAttributes());
        });
    }
}
