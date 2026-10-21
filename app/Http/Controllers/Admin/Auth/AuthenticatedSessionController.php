<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
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

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => __('validation.credentials')]);
        }

        $admin = $request->user('admin');

        if ($admin->status !== 'active') {
            Auth::guard('admin')->logout();

            throw ValidationException::withMessages(['email' => 'حسابك غير نشط. يرجى التواصل مع الإدارة.']);
        }

        $request->session()->regenerate();
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

