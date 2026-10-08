<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Http\Requests\Admin\Auth\AdminLoginRequest;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('admin.auth.login');
    }

    public function login(AdminLoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        if (!Auth::guard('admin')->validate($credentials)) {
            $this->logFailedLogin($request, Auth::guard('admin')->getLastAttempted(), 'بيانات الدخول غير صحيحة');

            return back()->withErrors([
                'email' => 'بيانات الدخول غير صحيحة.',
            ])->onlyInput('email');
        }

        $admin = Auth::guard('admin')->getLastAttempted();

        // لو مفعّل عنده 2FA ومؤكد
        if ($admin->two_factor_secret && ! is_null($admin->two_factor_confirmed_at)) {
            $request->session()->put([
                'login.id'       => $admin->getKey(),
                'login.remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('admin.two-factor.login');
        }

        Auth::guard('admin')->login($admin, $request->boolean('remember'));
        $request->session()->regenerate();

        AdminUser::where('id', $admin->id)->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        return redirect()->intended(RouteServiceProvider::ADMIN);
    }

    public function logout(AdminAuthenticatedRequest $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * تسجيل محاولة الدخول الفاشلة في سجل النشاطات.
     */
    private function logFailedLogin(Request $request, ?AdminUser $targetAdmin, string $reason): void
    {
        $log = activity('auth')
            ->withProperties([
                'identifier' => $request->input('email'),
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'reason'     => $reason,
            ]);

        if ($targetAdmin) {
            $log->performedOn($targetAdmin);
        }

        $log->log('محاولة تسجيل دخول فاشلة: ' . $request->input('email') . ' (' . $reason . ')');
    }
}
