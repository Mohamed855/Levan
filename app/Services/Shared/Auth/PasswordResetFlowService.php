<?php

declare(strict_types=1);

namespace App\Services\Shared\Auth;

use App\Actions\Auth\PersistAuthenticationStateAction;
use App\Support\Auth\PasswordResetSessionContext;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PasswordResetFlowService
{
    public const MAX_RESENDS = 3;

    public const MAX_ATTEMPTS = 5;

    public const OTP_TTL_MINUTES = 2;

    public const SESSION_TTL_MINUTES = 15;

    public const RESET_WINDOW_MINUTES = 15;

    public function __construct(
        private readonly PasswordResetOtpService $otpService,
        private readonly PersistAuthenticationStateAction $authenticationState,
    ) {}

    /**
     * Resolve current step state and view data for the forgot-password screen.
     *
     * @return array{otpStep: bool, otpExpiresAt: int|null, resendCount: int, maxResends: int, email: string}
     */
    public function getForgotPasswordState(Request $request, string $context, mixed $oldEmail = ''): array
    {
        $verification = PasswordResetSessionContext::get($request, $context);

        if ($verification && ! PasswordResetSessionContext::matches($verification, $context)) {
            PasswordResetSessionContext::forget($request, $context);
            $verification = [];
        }

        if (is_array($verification) && ! empty($verification)) {
            $sessionExpires = (int) ($verification['session_expires_at'] ?? 0);
            if ($sessionExpires < now()->timestamp) {
                PasswordResetSessionContext::forget($request, $context);
                $verification = [];
            }
        }

        $otpStep = is_array($verification) && isset($verification['otp_hash']) && empty($verification['verified']);

        return [
            'otpStep' => $otpStep,
            'otpExpiresAt' => $verification['expires_at'] ?? null,
            'resendCount' => (int) ($verification['resend_count'] ?? 0),
            'maxResends' => self::MAX_RESENDS,
            'email' => (string) ($verification['email'] ?? (is_string($oldEmail) ? $oldEmail : '')),
        ];
    }

    public function isAccountInactive(Authenticatable $user): bool
    {
        $status = $user->status ?? null;
        $statusValue = $status instanceof \BackedEnum ? (string) $status->value : (string) ($status ?? '');

        return $statusValue !== '' && $statusValue !== 'active';
    }

    public function issueOtp(Request $request, Authenticatable $user, string $context): void
    {
        $email = (string) ($user->email ?? '');
        $name = isset($user->name) ? (string) $user->name : null;
        $otpHash = $this->otpService->issue($email, $name);

        $payload = [
            'context' => $context,
            'email' => $email,
            'user_id' => $user->getAuthIdentifier(),
            'otp_hash' => $otpHash,
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES)->timestamp,
            'session_expires_at' => now()->addMinutes(self::SESSION_TTL_MINUTES)->timestamp,
            'resend_count' => 0,
            'attempts' => 0,
            'verified' => false,
        ];

        if ($context === PasswordResetSessionContext::TENANT) {
            $payload['tenant_id'] = (string) tenant('id');
        }

        PasswordResetSessionContext::put($request, $context, $payload);
    }

    /**
     * @return array{status: string, message?: string}
     */
    public function verifyOtp(Request $request, string $inputOtp, string $context): array
    {
        $verification = PasswordResetSessionContext::get($request, $context);

        if (! PasswordResetSessionContext::matches($verification, $context)
            || ! isset($verification['otp_hash'])
            || (int) ($verification['session_expires_at'] ?? 0) < now()->timestamp) {
            PasswordResetSessionContext::forget($request, $context);

            return [
                'status' => 'invalid_session',
                'message' => 'انتهت صلاحية جلسة التحقق، يرجى إعادة طلب رمز التحقق.',
            ];
        }

        if ((int) ($verification['expires_at'] ?? 0) < now()->timestamp) {
            return [
                'status' => 'expired',
                'message' => 'انتهت صلاحية رمز التحقق، يرجى النقر على إعادة إرسال الرمز.',
            ];
        }

        if ((int) ($verification['attempts'] ?? 0) >= self::MAX_ATTEMPTS) {
            PasswordResetSessionContext::forget($request, $context);

            return [
                'status' => 'too_many_attempts',
                'message' => 'تم استنفاد الحد الأقصى للمحاولات الخاطئة (5 محاولات). يرجى البدء من جديد.',
            ];
        }

        if (! hash_equals((string) $verification['otp_hash'], hash('sha256', $inputOtp))) {
            $verification['attempts'] = ((int) ($verification['attempts'] ?? 0)) + 1;
            PasswordResetSessionContext::put($request, $context, $verification);

            return [
                'status' => 'invalid_code',
                'message' => 'رمز التحقق غير صحيح، يرجى التأكد وإعادة المحاولة.',
            ];
        }

        $verification['verified'] = true;
        $verification['verified_at'] = now()->timestamp;
        $verification['expires_at'] = now()->addMinutes(self::RESET_WINDOW_MINUTES)->timestamp;
        PasswordResetSessionContext::put($request, $context, $verification);

        return ['status' => 'verified'];
    }

    /**
     * @return array{success: bool, reason?: string, message: string, expires_at?: int, resend_count?: int, remaining_resends?: int}
     */
    public function resendOtp(Request $request, string $context): array
    {
        $verification = PasswordResetSessionContext::get($request, $context);

        if (! PasswordResetSessionContext::matches($verification, $context)
            || ! isset($verification['otp_hash'])
            || (isset($verification['session_expires_at']) && (int) $verification['session_expires_at'] < now()->timestamp)) {
            PasswordResetSessionContext::forget($request, $context);

            return [
                'success' => false,
                'reason' => 'no_session',
                'message' => 'لا توجد جلسة تحقق نشطة.',
            ];
        }

        $resendCount = (int) ($verification['resend_count'] ?? 0);
        if ($resendCount >= self::MAX_RESENDS) {
            return [
                'success' => false,
                'reason' => 'max_resends',
                'message' => 'لقد استنفدت الحد الأقصى لمحاولات إعادة الإرسال (3 مرات). يمكنك العودة لتعديل البريد.',
                'resend_count' => $resendCount,
            ];
        }

        $otpHash = $this->otpService->issue((string) $verification['email']);
        $verification['otp_hash'] = $otpHash;
        $verification['expires_at'] = now()->addMinutes(self::OTP_TTL_MINUTES)->timestamp;
        $verification['attempts'] = 0;
        $verification['resend_count'] = $resendCount + 1;
        PasswordResetSessionContext::put($request, $context, $verification);

        return [
            'success' => true,
            'message' => 'تم إعادة إرسال رمز التحقق بنجاح إلى بريدك الإلكتروني.',
            'expires_at' => $verification['expires_at'],
            'resend_count' => $verification['resend_count'],
            'remaining_resends' => max(0, self::MAX_RESENDS - $verification['resend_count']),
        ];
    }

    public function clearAndRememberEmail(Request $request, string $context): string
    {
        $verification = PasswordResetSessionContext::get($request, $context);
        $email = is_array($verification) ? (string) ($verification['email'] ?? '') : '';
        PasswordResetSessionContext::forget($request, $context);

        return $email;
    }

    /**
     * @return array{valid: bool, verification?: array, message?: string}
     */
    public function validateVerifiedSession(Request $request, string $context, string $invalidMessage): array
    {
        $verification = PasswordResetSessionContext::get($request, $context);

        if (! PasswordResetSessionContext::matches($verification, $context) || empty($verification['verified'])) {
            PasswordResetSessionContext::forget($request, $context);

            return [
                'valid' => false,
                'message' => $invalidMessage,
            ];
        }

        if ((int) ($verification['expires_at'] ?? 0) < now()->timestamp) {
            PasswordResetSessionContext::forget($request, $context);

            return [
                'valid' => false,
                'message' => 'انتهت مهلة تعيين كلمة المرور، يرجى إعادة طلب رمز التحقق.',
            ];
        }

        return [
            'valid' => true,
            'verification' => $verification,
        ];
    }

    public function completeReset(
        Request $request,
        Authenticatable $user,
        string $password,
        string $context,
        ?callable $persister = null,
    ): void {
        if ($persister !== null) {
            $persister($user, $password);
        } else {
            $connection = method_exists($user, 'getConnectionName') ? $user->getConnectionName() : null;
            DB::connection($connection)->transaction(function () use ($user, $password): void {
                $this->authenticationState->resetPassword($user, $password);
            });
        }

        event(new PasswordReset($user));
        PasswordResetSessionContext::forget($request, $context);
    }
}
