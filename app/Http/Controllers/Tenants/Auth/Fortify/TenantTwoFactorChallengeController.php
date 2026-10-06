<?php

namespace App\Http\Controllers\Tenants\Auth\Fortify;

use App\Actions\Auth\ResolveTwoFactorLogin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\TenantTwoFactorChallengeRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TenantTwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.id')) {
            return redirect(tenant_route_name('login'));
        }

        return view('tenants.auth.two-factor-challenge');
    }

    public function store(TenantTwoFactorChallengeRequest $request, ResolveTwoFactorLogin $resolver): RedirectResponse
    {
        $user = $resolver->verify('tenant', $request, $request->validated());
        $remember = $request->session()->pull('login.remember', false);

        $request->session()->forget('login.id');

        Auth::guard('tenant')->login($user, $remember);

        $request->session()->regenerate();

        $user->update([
            'last_login_at'      => now(),
            'last_login_ip'      => $request->ip(),
            'current_session_id' => session()->getId(),
        ]);

        return redirect(RouteServiceProvider::home());
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(['login.id', 'login.remember']);

        return redirect(tenant_route_name('login'));
    }
}
