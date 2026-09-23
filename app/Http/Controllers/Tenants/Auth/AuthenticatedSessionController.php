<?php

namespace App\Http\Controllers\Tenants\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:tenant')->except('destroy');
    }

    public function create(): View
    {
        return view('tenants.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::guard('tenant')->getLastAttempted();

        // لو مفعّل عنده 2FA ومؤكد، نوقف الجلسة مؤقتًا وننتقل لصفحة التحقق
        if ($user->two_factor_secret && ! is_null($user->two_factor_confirmed_at)) {
            $request->session()->put([
                'login.id'       => $user->getKey(),
                'login.remember' => $request->boolean('remember'),
            ]);

            return redirect(tenant_route_name('tenants.two-factor.login'));
        }

        Auth::guard('tenant')->login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        $user->update([
            'last_login_at'      => now(),
            'last_login_ip'      => $request->ip(),
            'current_session_id' => session()->getId(),
        ]);

        return redirect(RouteServiceProvider::home());
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Logout Request
    |--------------------------------------------------------------------------
    | معالجة طلب تسجيل الخروج وتنظيف بيانات الجلسة
    */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::guard('tenant')->user();

        if ($user) {
            $user->update(['current_session_id' => null]);
        }

        Auth::guard('tenant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->redirectAfterLogout($request);
    }

    /*
    |--------------------------------------------------------------------------
    | Redirect After Logout
    |--------------------------------------------------------------------------
    | توجيه المستخدم إلى الصفحة المناسبة بعد تسجيل الخروج
    */
    protected function redirectAfterLogout(Request $request): RedirectResponse
    {
        if (!tenant()) {
            return redirect('/login');
        }

        $currentHost = $request->getHost();
        $centralDomains = $this->getCentralDomains();
        $isCentralDomain = in_array($currentHost, $centralDomains);

        if ($isCentralDomain) {
            return redirect('/' . tenant('id') . '/login');
        }

        return redirect('/login');
    }

    /*
    |--------------------------------------------------------------------------
    | Get Cleaned Central Domains
    |--------------------------------------------------------------------------
    | الحصول على قائمة النطاقات المركزية بعد تنظيفها
    */
    protected function getCentralDomains(): array
    {
        return array_map(function ($domain) {
            return str_replace(['http://', 'https://', 'www.'], '', $domain);
        }, config('tenancy.central_domains', []));
    }
}
