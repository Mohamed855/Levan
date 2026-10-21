<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Auth\LoginRequest;
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

    public function store(LoginRequest $request): RedirectResponse
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

        $request->session()->regenerate();
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

