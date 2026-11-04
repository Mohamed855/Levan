<?php

namespace App\Http\Controllers\Tenants\Auth\Fortify;

use App\Actions\Auth\PersistAuthenticationStateAction;
use App\Actions\Auth\ResolveTwoFactorLogin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\TenantTwoFactorChallengeRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TenantTwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $hasLoginId = $request->session()->has('login.id');
        $guard = $request->session()->get('login.guard');
        $tenantId = $request->session()->get('login.tenant_id');
        $currentTenantId = function_exists('tenant') ? tenant('id') : null;

        if (! $hasLoginId || $guard !== 'tenant' || $tenantId === null || $currentTenantId === null || (string) $tenantId !== (string) $currentTenantId) {
            $request->session()->forget(['login.id', 'login.guard', 'login.tenant_id', 'login.remember']);
            return redirect(tenant_route_name('login'));
        }

        $user = Auth::guard('tenant')->getProvider()->retrieveById($request->session()->get('login.id'));
        $statusValue = $user && $user->status instanceof \BackedEnum ? $user->status->value : (string) ($user->status ?? '');
        if (! $user || $statusValue !== 'active' || empty($user->two_factor_secret) || empty($user->two_factor_confirmed_at)) {
            $request->session()->forget(['login.id', 'login.guard', 'login.tenant_id', 'login.remember']);
            return redirect(tenant_route_name('login'));
        }

        return view('tenant.auth.two-factor-challenge');
    }

    public function store(TenantTwoFactorChallengeRequest $request, ResolveTwoFactorLogin $resolver, PersistAuthenticationStateAction $authenticationState): RedirectResponse
    {
        $user = $resolver->verify('tenant', $request, $request->validated());
        $remember = $request->session()->pull('login.remember', false);

        $request->session()->forget(['login.id', 'login.guard', 'login.tenant_id', 'login.remember']);

        Auth::guard('tenant')->login($user, $remember);

        $request->session()->regenerate();

        $request->session()->put('auth_tenant_id', tenant('id'));

        $authenticationState->recordLogin($user, $request->ip(), $request->session()->getId());

        return redirect(RouteServiceProvider::home());
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(['login.id', 'login.guard', 'login.tenant_id', 'login.remember']);

        return redirect(tenant_route_name('login'));
    }
}
