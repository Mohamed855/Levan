<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\ResendPasswordResetOtpRequest;
use App\Http\Requests\Tenants\Auth\SendPasswordResetLinkRequest;
use App\Http\Requests\Tenants\Auth\VerifyPasswordResetOtpRequest;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Shared\Auth\PasswordResetFlowService;
use App\Support\Auth\PasswordResetSessionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function __construct(
        private readonly PasswordResetFlowService $flow,
    ) {}

    /**
     * Display the password reset link or OTP verification view.
     */
    public function create(Request $request): View
    {
        return view(
            'tenant.auth.forgot-password',
            $this->flow->getForgotPasswordState($request, PasswordResetSessionContext::TENANT, old('email', '')),
        );
    }

    /**
     * Issue and send a password reset OTP to the user's email.
     */
    public function store(SendPasswordResetLinkRequest $request): RedirectResponse
    {
        $email = (string) $request->validated('email');
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'البريد الإلكتروني المدخل غير مسجل لدينا.']);
        }

        if ($this->flow->isAccountInactive($user)) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'حسابك غير نشط حالياً، يرجى التواصل مع إدارة النظام.']);
        }

        $this->flow->issueOtp($request, $user, PasswordResetSessionContext::TENANT);

        return redirect()->to(tenant_route_name('password.request'))
            ->with('status', 'تم إرسال رمز التحقق المكون من 6 أرقام إلى بريدك الإلكتروني بنجاح.');
    }

    /**
     * Verify the submitted OTP code.
     */
    public function verifyOtp(VerifyPasswordResetOtpRequest $request): RedirectResponse
    {
        $result = $this->flow->verifyOtp(
            $request,
            (string) $request->validated('otp'),
            PasswordResetSessionContext::TENANT,
        );

        return match ($result['status']) {
            'verified' => redirect()->to(tenant_route_name('password.reset')),
            'expired', 'invalid_code' => back()->withErrors(['otp' => $result['message']]),
            default => redirect()->to(tenant_route_name('password.request'))->withErrors(['otp' => $result['message']]),
        };
    }

    /**
     * Resend the password reset OTP code.
     */
    public function resendOtp(ResendPasswordResetOtpRequest $request): JsonResponse|RedirectResponse
    {
        $result = $this->flow->resendOtp($request, PasswordResetSessionContext::TENANT);

        if (! $result['success']) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(Arr::except($result, ['reason']), 422);
            }

            return redirect()->to(tenant_route_name('password.request'))
                ->withErrors(['otp' => $result['message']]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return redirect()->to(tenant_route_name('password.request'))->with('status', $result['message']);
    }

    /**
     * Clear the OTP session and return to editing the email.
     */
    public function editEmail(Request $request): RedirectResponse
    {
        $email = $this->flow->clearAndRememberEmail($request, PasswordResetSessionContext::TENANT);

        return redirect()->to(tenant_route_name('password.request'))->withInput(['email' => $email]);
    }
}
