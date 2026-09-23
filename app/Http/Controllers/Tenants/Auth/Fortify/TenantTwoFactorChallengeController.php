<?php

namespace App\Http\Controllers\Tenants\Auth\Fortify;

use App\Actions\Auth\ResolveTwoFactorLogin;
use App\Http\Controllers\Controller;
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

    public function store(Request $request, ResolveTwoFactorLogin $resolver): RedirectResponse
    {
        $user = $resolver->verify('tenant', $request);

        $request->session()->forget(['login.id', 'login.remember']);

        Auth::guard('tenant')->login($user, $request->session()->get('login.remember', false));

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
