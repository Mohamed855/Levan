<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Auth;

use App\Actions\Admin\Clients\ResetClientUserPasswordAction;
use App\Actions\Tenants\Auth\ResetTenantPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Clients\ResetClientUserPasswordRequest;
use App\Http\Requests\Tenants\Auth\ResetTenantPasswordRequest;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Shared\Auth\PasswordResetFlowService;
use App\Support\Auth\PasswordResetSessionContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function __construct(
        private readonly PasswordResetFlowService $flow,
    ) {}

    /**
     * Display the password reset view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $check = $this->flow->validateVerifiedSession(
            $request,
            PasswordResetSessionContext::TENANT,
            'يرجى إدخال بريدك الإلكتروني والتحقق من الرمز أولاً.',
        );

        if ($check['valid']) {
            return view('tenant.auth.reset-password', [
                'email' => $check['verification']['email'] ?? '',
                'request' => $request,
            ]);
        }

        // Support admin central token route if tenant is not active
        if (! tenant() && $request->route('token')) {
            return view('tenant.auth.reset-password', [
                'email' => $request->email ?? '',
                'request' => $request,
            ]);
        }

        return redirect()->to(tenant_route_name('password.request'))
            ->withErrors(['email' => $check['message']]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(ResetTenantPasswordRequest $request, ResetTenantPasswordAction $resetPassword): RedirectResponse
    {
        $check = $this->flow->validateVerifiedSession(
            $request,
            PasswordResetSessionContext::TENANT,
            'جلسة التحقق غير صالحة، يرجى البدء من جديد.',
        );

        if (! $check['valid']) {
            return redirect()->to(tenant_route_name('password.request'))
                ->withErrors(['email' => $check['message']]);
        }

        $verification = $check['verification'];
        $email = (string) ($verification['email'] ?? $request->validated('email'));
        $user = User::query()->whereKey($verification['user_id'] ?? null)->where('email', $email)->first();

        if (! $user) {
            $this->flow->clearAndRememberEmail($request, PasswordResetSessionContext::TENANT);

            return redirect()->to(tenant_route_name('password.request'))
                ->withErrors(['email' => 'المستخدم المرتبط بهذا البريد لم يعد موجوداً.']);
        }

        $this->flow->completeReset(
            $request,
            $user,
            (string) $request->validated('password'),
            PasswordResetSessionContext::TENANT,
            static fn (User $target, string $password): User => $resetPassword->execute($target, $password),
        );

        return redirect()->to(tenant_route_name('login'))
            ->with('status', 'تم إعادة تعيين كلمة المرور بنجاح، يمكنك الآن تسجيل الدخول.');
    }

    public function updatePassword(ResetClientUserPasswordRequest $request, ResetClientUserPasswordAction $resetPassword): RedirectResponse
    {
        $reset = $resetPassword->execute(
            $request->user('admin'),
            $request->validated('tenant_id'),
            $request->validated('email'),
            $request->validated('password'),
        );

        if ($reset) {
            return back()
                ->with('success', 'تم تعديل كلمة المرور بنجاح يمكنك الاستفادة بخدماتنا');
        }

        throw ValidationException::withMessages(['email' => 'المستخدم غير موجود.']);
    }
}
