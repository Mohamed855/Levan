<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Actions\Auth\ResolveTwoFactorLogin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminTwoFactorChallengeRequest;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminTwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.two-factor-challenge');
    }

    public function store(AdminTwoFactorChallengeRequest $request, ResolveTwoFactorLogin $resolver): RedirectResponse
    {
        $admin = $resolver->verify('admin', $request, $request->validated());
        $remember = $request->session()->pull('login.remember', false);

        $request->session()->forget('login.id');

        Auth::guard('admin')->login($admin, $remember);

        $request->session()->regenerate();

        AdminUser::where('id', $admin->id)->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        return redirect()->intended(RouteServiceProvider::ADMIN);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(['login.id', 'login.remember']);

        return redirect()->route('admin.login');
    }
}
