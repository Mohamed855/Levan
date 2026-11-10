<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\Auth\ResendPasswordResetOtpRequest;
use App\Http\Requests\Shared\Auth\ResetPasswordRequest;
use App\Http\Requests\Shared\Auth\SendPasswordResetOtpRequest;
use App\Http\Requests\Shared\Auth\VerifyPasswordResetOtpRequest;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Services\Shared\Auth\PasswordResetFlowService;
use App\Support\Auth\PasswordResetSessionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class AdminPasswordResetController extends Controller
{
    public function __construct(
        private readonly PasswordResetFlowService $flow,
    ) {}

    public function create(Request $request): View
    {
        return view(
            'admin.auth.forgot-password',
            $this->flow->getForgotPasswordState($request, PasswordResetSessionContext::ADMIN, old('email', '')),
        );
    }

    public function store(SendPasswordResetOtpRequest $request): RedirectResponse
    {
        $email = (string) $request->validated('email');
        $admin = AdminUser::query()->where('email', $email)->first();

        if (! $admin) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'البريد الإلكتروني المدخل غير مسجل لدينا.']);
        }

        if ($this->flow->isAccountInactive($admin)) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'حسابك غير نشط حالياً، يرجى التواصل مع إدارة النظام.']);
        }

        $this->flow->issueOtp($request, $admin, PasswordResetSessionContext::ADMIN);

        return redirect()->route('admin.password.request')
            ->with('status', 'تم إرسال رمز التحقق المكون من 6 أرقام إلى بريدك الإلكتروني بنجاح.');
    }

    public function verifyOtp(VerifyPasswordResetOtpRequest $request): RedirectResponse
    {
        $result = $this->flow->verifyOtp(
            $request,
            (string) $request->validated('otp'),
            PasswordResetSessionContext::ADMIN,
        );

        return match ($result['status']) {
            'verified' => redirect()->route('admin.password.reset'),
            'expired', 'invalid_code' => back()->withErrors(['otp' => $result['message']]),
            default => redirect()->route('admin.password.request')->withErrors(['otp' => $result['message']]),
        };
    }

    public function resendOtp(ResendPasswordResetOtpRequest $request): JsonResponse|RedirectResponse
    {
        $result = $this->flow->resendOtp($request, PasswordResetSessionContext::ADMIN);

        if (! $result['success']) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(Arr::except($result, ['reason']), 422);
            }

            return redirect()->route('admin.password.request')
                ->withErrors(['otp' => $result['message']]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return redirect()->route('admin.password.request')->with('status', $result['message']);
    }

    public function editEmail(Request $request): RedirectResponse
    {
        $email = $this->flow->clearAndRememberEmail($request, PasswordResetSessionContext::ADMIN);

        return redirect()->route('admin.password.request')->withInput(['email' => $email]);
    }

    public function showResetForm(Request $request): View|RedirectResponse
    {
        $check = $this->flow->validateVerifiedSession(
            $request,
            PasswordResetSessionContext::ADMIN,
            'يرجى إدخال بريدك الإلكتروني والتحقق من الرمز أولاً.',
        );

        if (! $check['valid']) {
            return redirect()->route('admin.password.request')
                ->withErrors(['email' => $check['message']]);
        }

        return view('admin.auth.reset-password', [
            'email' => $check['verification']['email'] ?? '',
            'request' => $request,
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $check = $this->flow->validateVerifiedSession(
            $request,
            PasswordResetSessionContext::ADMIN,
            'جلسة التحقق غير صالحة، يرجى البدء من جديد.',
        );

        if (! $check['valid']) {
            return redirect()->route('admin.password.request')
                ->withErrors(['email' => $check['message']]);
        }

        $verification = $check['verification'];
        $email = (string) ($verification['email'] ?? $request->validated('email'));
        $admin = AdminUser::query()->whereKey($verification['user_id'] ?? null)->where('email', $email)->first();

        if (! $admin) {
            $this->flow->clearAndRememberEmail($request, PasswordResetSessionContext::ADMIN);

            return redirect()->route('admin.password.request')
                ->withErrors(['email' => 'المستخدم المرتبط بهذا البريد لم يعد موجوداً.']);
        }

        $this->flow->completeReset(
            $request,
            $admin,
            (string) $request->validated('password'),
            PasswordResetSessionContext::ADMIN,
        );

        return redirect()->route('admin.login')
            ->with('status', 'تم إعادة تعيين كلمة المرور بنجاح، يمكنك الآن تسجيل الدخول.');
    }
}
