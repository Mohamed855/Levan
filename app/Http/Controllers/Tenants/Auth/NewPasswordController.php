<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Auth;

use App\Actions\Tenants\Auth\ResetTenantPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\ResetTenantPasswordRequest;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $verification = $request->session()->get('password_reset_verification');

        if (is_array($verification) && ! empty($verification['verified'])) {
            if ((int) ($verification['expires_at'] ?? 0) < now()->timestamp) {
                $request->session()->forget('password_reset_verification');

                return redirect()->to(tenant_route_name('password.request'))
                    ->withErrors(['email' => 'انتهت مهلة تعيين كلمة المرور، يرجى طلب رمز تحقق جديد.']);
            }

            return view('tenant.auth.reset-password', [
                'email' => $verification['email'] ?? '',
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
            ->withErrors(['email' => 'يرجى إدخال بريدك الإلكتروني والتحقق من الرمز أولاً.']);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(ResetTenantPasswordRequest $request, ResetTenantPasswordAction $resetPassword): RedirectResponse
    {
        $verification = $request->session()->get('password_reset_verification');

        if (! is_array($verification) || empty($verification['verified'])) {
            return redirect()->to(tenant_route_name('password.request'))
                ->withErrors(['email' => 'جلسة التحقق غير صالحة، يرجى البدء من جديد.']);
        }

        if ((int) ($verification['expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget('password_reset_verification');

            return redirect()->to(tenant_route_name('password.request'))
                ->withErrors(['email' => 'انتهت مهلة تعيين كلمة المرور، يرجى إعادة طلب رمز التحقق.']);
        }

        $email = (string) ($verification['email'] ?? $request->validated('email'));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $request->session()->forget('password_reset_verification');

            return redirect()->to(tenant_route_name('password.request'))
                ->withErrors(['email' => 'المستخدم المرتبط بهذا البريد لم يعد موجوداً.']);
        }

        $resetPassword->execute($user, (string) $request->validated('password'));
        event(new PasswordReset($user));
        $request->session()->forget('password_reset_verification');

        return redirect()->to(tenant_route_name('login'))
            ->with('status', 'تم إعادة تعيين كلمة المرور بنجاح، يمكنك الآن تسجيل الدخول.');
    }

    public function updatePassword(ResetTenantPasswordRequest $request, ResetTenantPasswordAction $resetPassword)
    {
        $user = $resetPassword->executeForTenant(
            (string) $request->input('tenant_id'),
            $request->validated('email'),
            $request->validated('password'),
        );

        if ($user) {
            return redirect()->to(tenant_route_name('login'))
                ->with('success', 'تم تعديل كلمة المرور بنجاح يمكنك الاستفادة بخدماتنا');
        }

        return back()->withErrors(['email' => 'المستخدم غير موجود.']);
    }
}
