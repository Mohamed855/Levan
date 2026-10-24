<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Enums\Admin\Users\AdminUserStatus;
use App\Http\Requests\Security\VerifyEmailTwoFactorRequest;
use App\Models\Admin\Setting;
use App\Services\Security\EmailTwoFactorChallenge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(LoginRequest $request, EmailTwoFactorChallenge $twoFactor): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => __('validation.credentials')]);
        }

        $admin = $request->user('admin');

        if ($admin->status !== AdminUserStatus::ACTIVE) {
            Auth::guard('admin')->logout();

            throw ValidationException::withMessages(['email' => 'حسابك غير نشط. يرجى التواصل مع الإدارة.']);
        }

        if ((bool) data_get(Setting::get('security', []), 'force_two_factor_auth', false)) {
            Auth::guard('admin')->logout();
            $request->session()->regenerate();
            $twoFactor->begin($request, 'admin', $admin, $request->boolean('remember'));

            return to_route('admin.two-factor.create');
        }

        $request->session()->regenerate();
        $admin->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function twoFactorCreate(Request $request, EmailTwoFactorChallenge $twoFactor): View
    {
        abort_unless($twoFactor->pending($request, 'admin'), 404);

        return view('auth.two-factor', [
            'title' => 'التحقق بخطوتين للإدارة',
            'verificationRoute' => route('admin.two-factor.store'),
        ]);
    }

    public function twoFactorStore(VerifyEmailTwoFactorRequest $request, EmailTwoFactorChallenge $twoFactor): RedirectResponse
    {
        $admin = $twoFactor->complete($request, 'admin', (string) $request->validated('code'));

        if ($admin->status !== AdminUserStatus::ACTIVE) {
            Auth::guard('admin')->logout();
            throw ValidationException::withMessages(['code' => 'حسابك غير نشط. يرجى التواصل مع الإدارة.']);
        }

        $admin->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

