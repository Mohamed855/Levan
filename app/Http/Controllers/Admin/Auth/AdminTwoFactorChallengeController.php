<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Actions\Auth\PersistAuthenticationStateAction;
use App\Actions\Auth\ResolveTwoFactorLogin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminTwoFactorChallengeRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminTwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.id') || $request->session()->get('login.guard') !== 'admin') {
            $request->session()->forget(['login.id', 'login.guard', 'login.tenant_id', 'login.remember']);
            return redirect()->route('admin.login');
        }

        $admin = Auth::guard('admin')->getProvider()->retrieveById($request->session()->get('login.id'));
        $statusValue = $admin && $admin->status instanceof \BackedEnum ? $admin->status->value : (string) ($admin->status ?? '');
        if (! $admin || $statusValue !== 'active' || empty($admin->two_factor_secret) || empty($admin->two_factor_confirmed_at)) {
            $request->session()->forget(['login.id', 'login.guard', 'login.tenant_id', 'login.remember']);
            return redirect()->route('admin.login');
        }

        return view('admin.auth.two-factor-challenge');
    }

    public function store(AdminTwoFactorChallengeRequest $request, ResolveTwoFactorLogin $resolver, PersistAuthenticationStateAction $authenticationState): RedirectResponse
    {
        $admin = $resolver->verify('admin', $request, $request->validated());
        $remember = $request->session()->pull('login.remember', false);

        $request->session()->forget(['login.id', 'login.guard', 'login.tenant_id', 'login.remember']);

        Auth::guard('admin')->login($admin, $remember);

        $request->session()->regenerate();

        $authenticationState->recordLogin($admin, $request->ip());

        return redirect()->intended(RouteServiceProvider::ADMIN);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(['login.id', 'login.guard', 'login.tenant_id', 'login.remember']);

        return redirect()->route('admin.login');
    }
}
