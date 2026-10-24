<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

final class EmailTwoFactorChallenge
{
    private const TTL_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    public function begin(Request $request, string $guard, Authenticatable $user, bool $remember): void
    {
        $code = (string) random_int(100000, 999999);
        $key = $this->sessionKey($guard);

        $request->session()->put($key, [
            'user_id' => $user->getAuthIdentifier(),
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES)->getTimestamp(),
            'attempts' => 0,
            'remember' => $remember,
        ]);

        try {
            Mail::raw("رمز التحقق لتسجيل الدخول هو: {$code}\nتنتهي صلاحيته خلال ".self::TTL_MINUTES.' دقائق.', function ($message) use ($user): void {
                $message->to($user->getEmailForPasswordReset())
                    ->subject('رمز التحقق لتسجيل الدخول');
            });
        } catch (\Throwable $exception) {
            $request->session()->forget($key);
            report($exception);

            throw ValidationException::withMessages([
                'email' => 'تعذر إرسال رمز التحقق. يرجى المحاولة لاحقاً.',
            ]);
        }
    }

    public function complete(Request $request, string $guard, string $code): Authenticatable
    {
        $key = $this->sessionKey($guard);
        $challenge = $request->session()->get($key);

        if (! is_array($challenge) || ! isset($challenge['user_id'], $challenge['code_hash'], $challenge['expires_at'])) {
            throw ValidationException::withMessages(['code' => 'انتهت جلسة التحقق. سجّل الدخول مجدداً.']);
        }

        if ((int) $challenge['expires_at'] < now()->getTimestamp()) {
            $request->session()->forget($key);
            throw ValidationException::withMessages(['code' => 'انتهت صلاحية رمز التحقق. سجّل الدخول مجدداً.']);
        }

        if ((int) ($challenge['attempts'] ?? 0) >= self::MAX_ATTEMPTS) {
            $request->session()->forget($key);
            throw ValidationException::withMessages(['code' => 'تجاوزت عدد محاولات التحقق المسموح. سجّل الدخول مجدداً.']);
        }

        if (! Hash::check($code, (string) $challenge['code_hash'])) {
            $challenge['attempts'] = (int) ($challenge['attempts'] ?? 0) + 1;
            $request->session()->put($key, $challenge);

            throw ValidationException::withMessages(['code' => 'رمز التحقق غير صحيح.']);
        }

        $provider = Auth::guard($guard)->getProvider();
        $user = $provider->retrieveById($challenge['user_id']);

        if (! $user instanceof Authenticatable) {
            $request->session()->forget($key);
            throw ValidationException::withMessages(['code' => 'حساب التحقق غير متاح. سجّل الدخول مجدداً.']);
        }

        Auth::guard($guard)->login($user, (bool) ($challenge['remember'] ?? false));
        $request->session()->regenerate();
        $request->session()->forget($key);

        return $user;
    }

    public function pending(Request $request, string $guard): bool
    {
        return is_array($request->session()->get($this->sessionKey($guard)));
    }

    private function sessionKey(string $guard): string
    {
        return 'two_factor_challenge.'.$guard;
    }
}
