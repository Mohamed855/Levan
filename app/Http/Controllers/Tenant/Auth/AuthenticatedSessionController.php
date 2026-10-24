<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Auth\LoginRequest;
use App\Http\Requests\Security\VerifyEmailTwoFactorRequest;
use App\Models\Tenant\Setting;
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
        return view('tenant.auth.login');
    }

    public function store(LoginRequest $request, EmailTwoFactorChallenge $twoFactor): RedirectResponse
    {
        $identifier = $request->string('email')->toString();
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (! Auth::guard('tenant')->attempt([$field => $identifier, 'password' => $request->string('password')->toString()], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => __('validation.credentials')]);
        }

        $user = $request->user('tenant');

        if ($user->status !== 'active') {
            Auth::guard('tenant')->logout();

            throw ValidationException::withMessages(['email' => 'حسابك غير نشط. يرجى التواصل مع الإدارة.']);
        }

        $tenantSettings = Setting::query()->first();
        if ((bool) $tenantSettings?->getValue('force_two_factor_auth', false)) {
            Auth::guard('tenant')->logout();
            $request->session()->regenerate();
            $twoFactor->begin($request, 'tenant', $user, $request->boolean('remember'));

            return to_route('tenant.two-factor.create');
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();

        return redirect()->intended(route('tenant.dashboard'));
    }

    public function twoFactorCreate(Request $request, EmailTwoFactorChallenge $twoFactor): View
    {
        abort_unless($twoFactor->pending($request, 'tenant'), 404);

        return view('auth.two-factor', [
            'title' => 'التحقق بخطوتين',
            'verificationRoute' => route('tenant.two-factor.store'),
        ]);
    }

    public function twoFactorStore(VerifyEmailTwoFactorRequest $request, EmailTwoFactorChallenge $twoFactor): RedirectResponse
    {
        $user = $twoFactor->complete($request, 'tenant', (string) $request->validated('code'));

        if ($user->status !== 'active') {
            Auth::guard('tenant')->logout();
            throw ValidationException::withMessages(['code' => 'حسابك غير نشط. يرجى التواصل مع الإدارة.']);
        }

        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();

        return redirect()->intended(route('tenant.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('tenant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tenant.login');
    }
}

