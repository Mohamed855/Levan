<?php

namespace App\Http\Controllers\Tenants\Auth;

use App\Actions\Tenants\Auth\ResetTenantPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\ResetTenantPasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('tenant.auth.reset-password', ['request' => $request]);
    }

    public function resetPasswordForm()
    {
        return view('tenant.auth.reset-password');
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(ResetTenantPasswordRequest $request, ResetTenantPasswordAction $resetPassword): RedirectResponse
    {
        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request, $resetPassword) {
                $resetPassword->execute($user, $request->validated('password'));

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
            ? redirect()->to(tenant_route_name('login'))->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }

    public function updatePassword(ResetTenantPasswordRequest $request, ResetTenantPasswordAction $resetPassword)
    {
        $user = $resetPassword->executeForTenant(
            (string) $request->input('tenant_id'),
            $request->validated('email'),
            $request->validated('password'),
        );

        if ($user) {
            return redirect('https://levan.com')->with('success', 'تم تعديل كلمة المرور بنجاح يمكنك الاستفادة بخدماتنا');
        }

        return back()->withErrors(['email' => 'المستخدم غير موجود.']);
    }
}
