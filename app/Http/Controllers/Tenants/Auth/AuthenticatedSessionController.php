<?php

namespace App\Http\Controllers\Tenants\Auth;

use App\Actions\Auth\PersistAuthenticationStateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\LoginRequest;
use App\Http\Requests\Tenants\Auth\TenantAuthenticatedRequest;
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

    public function create(): View|RedirectResponse
    {
        $currentHost = request()->getHost();
        $centralDomains = array_map(function ($domain) {
            return str_replace(['http://', 'https://', 'www.'], '', $domain);
        }, config('tenancy.central_domains', []));

        $isCentralDomain = in_array($currentHost, $centralDomains) || in_array('www.'.$currentHost, $centralDomains);

        // If accessed on central domain via legacy /{tenant_id}/login, set context and redirect cleanly to /admin/login
        if ($isCentralDomain && request()->route('tenant')) {
            $tenantId = tenant('id') ?? (string) request()->route('tenant');
            session()->put('tenant_login_context', $tenantId);
            cookie()->queue(cookie('tenant_login_hint', $tenantId, 60 * 24 * 30));

            return redirect('/admin/login');
        }

        return view('tenant.auth.login');
    }

    public function store(LoginRequest $request, PersistAuthenticationStateAction $authenticationState): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::guard('tenant')->getLastAttempted();

        // لو مفعّل عنده 2FA ومؤكد، نوقف الجلسة مؤقتًا وننتقل لصفحة التحقق
        if ($user->two_factor_secret && ! is_null($user->two_factor_confirmed_at)) {
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $request->boolean('remember'),
            ]);

            return redirect(tenant_route_name('tenants.two-factor.login'));
        }

        Auth::guard('tenant')->login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        $tenantId = tenant('id');
        $request->session()->put('auth_tenant_id', $tenantId);
        $request->session()->put('tenant_login_context', $tenantId);
        cookie()->queue(cookie('tenant_login_hint', $tenantId, 60 * 24 * 30));

        $authenticationState->recordLogin($user, $request->ip(), $request->session()->getId());

        $intended = $request->session()->get('url.intended');
        if ($intended && (str_contains($intended, '/login') || str_ends_with(rtrim($intended, '/'), '/admin'))) {
            $request->session()->forget('url.intended');
        }

        return redirect()->intended(RouteServiceProvider::home());
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Logout Request
    |--------------------------------------------------------------------------
    | معالجة طلب تسجيل الخروج وتنظيف بيانات الجلسة
    */
    public function destroy(TenantAuthenticatedRequest $request, PersistAuthenticationStateAction $authenticationState): RedirectResponse
    {
        $user = Auth::guard('tenant')->user();
        $tenantId = tenant('id');

        if ($user) {
            $authenticationState->clearCurrentSession($user);
        }

        Auth::guard('tenant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($tenantId) {
            $request->session()->put('tenant_login_context', $tenantId);
            cookie()->queue(cookie('tenant_login_hint', $tenantId, 60 * 24 * 30));
        }

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
        if (! tenant()) {
            return redirect('/login');
        }

        $currentHost = $request->getHost();
        $centralDomains = $this->getCentralDomains();
        $isCentralDomain = in_array($currentHost, $centralDomains);

        if ($isCentralDomain) {
            return redirect('/admin/login');
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
