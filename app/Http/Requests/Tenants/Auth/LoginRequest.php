<?php

namespace App\Http\Requests\Tenants\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'البريد الإلكتروني أو رقم الهاتف مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'البريد الإلكتروني أو رقم الهاتف',
            'password' => 'كلمة المرور',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // تحديد الحقل الذي سيتم استخدامه: email أو phone
        $credentials = filter_var($this->input('email'), FILTER_VALIDATE_EMAIL) ? ['email' => $this->input('email')] : ['phone' => $this->input('email')];
        $credentials['password'] = $this->input('password');

        // التحقق من صحة بيانات الدخول دون تسجيل الدخول فعليًا
        // (يسمح للـ Controller بالتحقق من المصادقة الثنائية قبل إنشاء الجلسة)
        if (!Auth::guard('tenant')->validate($credentials)) {
            RateLimiter::hit($this->throttleKey());

            $this->logFailedLogin(Auth::guard('tenant')->getLastAttempted(), 'بيانات الدخول غير صحيحة');

            throw ValidationException::withMessages([
                'email' => 'بيانات الدخول غير صحيحة.',
            ]);
        }

        // التحقق من حالة المستخدم
        $user = Auth::guard('tenant')->getLastAttempted();
        if ($user->status !== 'active') {
            $this->logFailedLogin($user, 'الحساب غير نشط');

            throw ValidationException::withMessages([
                'email' => 'حسابك غير نشط. الرجاء التواصل مع الإدارة.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * تسجيل محاولة الدخول الفاشلة في سجل النشاطات.
     */
    protected function logFailedLogin(?\Illuminate\Database\Eloquent\Model $targetUser, string $reason): void
    {
        $log = activity('auth')
            ->withProperties([
                'identifier' => $this->input('email'),
                'ip'         => $this->ip(),
                'user_agent' => $this->userAgent(),
                'reason'     => $reason,
            ]);

        if ($targetUser) {
            $log->performedOn($targetUser);
        }

        $log->log('محاولة تسجيل دخول فاشلة: ' . $this->input('email') . ' (' . $reason . ')');
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => 'لقد قمت بمحاولات كثيرة. يرجى المحاولة بعد ' . ceil($seconds / 60) . ' دقيقة.',
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
