<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Actions\Auth\PersistAuthenticationStateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Http\Requests\Admin\Auth\AdminLoginRequest;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('admin.auth.login');
    }

    public function login(AdminLoginRequest $request, PersistAuthenticationStateAction $authenticationState): RedirectResponse
    {
        $credentials = $request->validated();

        if (! Auth::guard('admin')->validate($credentials)) {
            $this->logFailedLogin($request, Auth::guard('admin')->getLastAttempted(), 'بيانات الدخول غير صحيحة');

            return back()->withErrors([
                'email' => 'بيانات الدخول غير صحيحة.',
            ])->onlyInput('email');
        }

        $admin = Auth::guard('admin')->getLastAttempted();

        $statusValue = $admin && $admin->status instanceof \BackedEnum ? $admin->status->value : (string) ($admin->status ?? '');
        if ($statusValue !== 'active') {
            $this->logFailedLogin($request, $admin, 'الحساب معطل');

            return back()->withErrors([
                'email' => 'هذا الحساب معطل. يرجى التواصل مع الإدارة.',
            ])->onlyInput('email');
        }

        // لو مفعّل عنده 2FA ومؤكد
        if ($admin->two_factor_secret && ! is_null($admin->two_factor_confirmed_at)) {
            $request->session()->forget(['login.id', 'login.guard', 'login.tenant_id', 'login.remember']);
            $request->session()->put([
                'login.id' => $admin->getKey(),
                'login.remember' => $request->boolean('remember'),
                'login.guard' => 'admin',
            ]);

            return redirect()->route('admin.two-factor.login');
        }

        Auth::guard('admin')->login($admin, $request->boolean('remember'));
        $request->session()->regenerate();

        $authenticationState->recordLogin($admin, $request->ip());

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
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'reason' => $reason,
            ]);

        if ($targetAdmin) {
            $log->performedOn($targetAdmin);
        }

        $log->log('محاولة تسجيل دخول فاشلة: '.$request->input('email').' ('.$reason.')');
    }
}
