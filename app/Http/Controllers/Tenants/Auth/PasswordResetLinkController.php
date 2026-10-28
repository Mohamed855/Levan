<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\ResendPasswordResetOtpRequest;
use App\Http\Requests\Tenants\Auth\SendPasswordResetLinkRequest;
use App\Http\Requests\Tenants\Auth\VerifyPasswordResetOtpRequest;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Shared\Auth\PasswordResetOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link or OTP verification view.
     */
    public function create(Request $request): View
    {
        $verification = $request->session()->get('password_reset_verification');

        if (is_array($verification) && ! empty($verification)) {
            $sessionExpires = (int) ($verification['session_expires_at'] ?? 0);
            if ($sessionExpires < now()->timestamp) {
                $request->session()->forget('password_reset_verification');
                $verification = [];
            }
        }

        $otpStep = is_array($verification) && isset($verification['otp_hash']) && empty($verification['verified']);

        return view('tenant.auth.forgot-password', [
            'otpStep' => $otpStep,
            'otpExpiresAt' => $verification['expires_at'] ?? null,
            'resendCount' => (int) ($verification['resend_count'] ?? 0),
            'maxResends' => 3,
            'email' => $verification['email'] ?? old('email', ''),
        ]);
    }

    /**
     * Issue and send a password reset OTP to the user's email.
     */
    public function store(SendPasswordResetLinkRequest $request, PasswordResetOtpService $otpService): RedirectResponse
    {
        $email = (string) $request->validated('email');

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'البريد الإلكتروني المدخل غير مسجل لدينا.']);
        }

        if (isset($user->status) && $user->status === 'inactive') {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'حسابك غير نشط حالياً، يرجى التواصل مع إدارة النظام.']);
        }

        $otpHash = $otpService->issue($user->email, $user->name);

        $request->session()->put('password_reset_verification', [
            'email' => $user->email,
            'user_id' => $user->id,
            'otp_hash' => $otpHash,
            'expires_at' => now()->addMinutes(2)->timestamp,
            'session_expires_at' => now()->addMinutes(15)->timestamp,
            'resend_count' => 0,
            'attempts' => 0,
            'verified' => false,
        ]);

        return redirect()->to(tenant_route_name('password.request'))
            ->with('status', 'تم إرسال رمز التحقق المكون من 6 أرقام إلى بريدك الإلكتروني بنجاح.');
    }

    /**
     * Verify the submitted OTP code.
     */
    public function verifyOtp(VerifyPasswordResetOtpRequest $request): RedirectResponse
    {
        $verification = $request->session()->get('password_reset_verification');

        if (! is_array($verification) || ! isset($verification['otp_hash']) || (int) ($verification['session_expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget('password_reset_verification');

            return redirect()->to(tenant_route_name('password.request'))
                ->withErrors(['otp' => 'انتهت صلاحية جلسة التحقق، يرجى إعادة طلب رمز التحقق.']);
        }

        if ((int) ($verification['expires_at'] ?? 0) < now()->timestamp) {
            return back()->withErrors(['otp' => 'انتهت صلاحية رمز التحقق، يرجى النقر على إعادة إرسال الرمز.']);
        }

        if ((int) ($verification['attempts'] ?? 0) >= 5) {
            $request->session()->forget('password_reset_verification');

            return redirect()->to(tenant_route_name('password.request'))
                ->withErrors(['otp' => 'تم استنفاد الحد الأقصى للمحاولات الخاطئة (5 محاولات). يرجى البدء من جديد.']);
        }

        $inputOtp = (string) $request->validated('otp');

        if (! hash_equals($verification['otp_hash'], hash('sha256', $inputOtp))) {
            $verification['attempts'] = ((int) ($verification['attempts'] ?? 0)) + 1;
            $request->session()->put('password_reset_verification', $verification);

            return back()->withErrors(['otp' => 'رمز التحقق غير صحيح، يرجى التأكد وإعادة المحاولة.']);
        }

        $verification['verified'] = true;
        $verification['verified_at'] = now()->timestamp;
        $verification['expires_at'] = now()->addMinutes(15)->timestamp;
        $request->session()->put('password_reset_verification', $verification);

        return redirect()->to(tenant_route_name('password.reset'));
    }

    /**
     * Resend the password reset OTP code.
     */
    public function resendOtp(ResendPasswordResetOtpRequest $request, PasswordResetOtpService $otpService)
    {
        $verification = $request->session()->get('password_reset_verification');

        if (! is_array($verification) || ! isset($verification['otp_hash'])) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'لا توجد جلسة تحقق نشطة.'], 422);
            }

            return redirect()->to(tenant_route_name('password.request'))
                ->withErrors(['otp' => 'لا توجد جلسة تحقق نشطة.']);
        }

        $resendCount = (int) ($verification['resend_count'] ?? 0);
        if ($resendCount >= 3) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لقد استنفدت الحد الأقصى لمحاولات إعادة الإرسال (3 مرات). يمكنك العودة لتعديل البريد.',
                    'resend_count' => $resendCount,
                ], 422);
            }

            return redirect()->to(tenant_route_name('password.request'))
                ->withErrors(['otp' => 'لقد استنفدت الحد الأقصى لمحاولات إعادة الإرسال (3 مرات). يمكنك العودة لتعديل البريد.']);
        }

        $otpHash = $otpService->issue($verification['email']);
        $verification['otp_hash'] = $otpHash;
        $verification['expires_at'] = now()->addMinutes(2)->timestamp;
        $verification['attempts'] = 0;
        $verification['resend_count'] = $resendCount + 1;
        $request->session()->put('password_reset_verification', $verification);

        $message = 'تم إعادة إرسال رمز التحقق بنجاح إلى بريدك الإلكتروني.';
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'expires_at' => $verification['expires_at'],
                'resend_count' => $verification['resend_count'],
                'remaining_resends' => max(0, 3 - $verification['resend_count']),
            ]);
        }

        return redirect()->to(tenant_route_name('password.request'))->with('status', $message);
    }

    /**
     * Clear the OTP session and return to editing the email.
     */
    public function editEmail(Request $request): RedirectResponse
    {
        $verification = $request->session()->get('password_reset_verification', []);
        $email = is_array($verification) ? ($verification['email'] ?? '') : '';
        $request->session()->forget('password_reset_verification');

        return redirect()->to(tenant_route_name('password.request'))->withInput(['email' => $email]);
    }
}
