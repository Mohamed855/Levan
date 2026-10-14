<?php

namespace App\Http\Controllers\Tenants\Auth;

use App\Actions\Auth\PersistAuthenticationStateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\LoginRequest;
use App\Http\Requests\Tenants\Auth\TenantAuthenticatedRequest;
use App\Models\Tenants\UserMangment\User\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
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

        $authenticationState->recordLogin($user, $request->ip(), $request->session()->getId());

        return redirect(RouteServiceProvider::home());
    }

    public function impersonate(Request $request, PersistAuthenticationStateAction $authenticationState): RedirectResponse
    {
        abort_unless($request->filled('token'), 403);

        try {
            $payload = json_decode(Crypt::decryptString($request->string('token')->toString()), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            abort(403);
        }

        abort_unless(
            ($payload['tenant_id'] ?? null) === tenant('id')
                && ($payload['expires_at'] ?? 0) >= now()->timestamp,
            403
        );

        $user = User::findOrFail($payload['user_id']);

        Auth::guard('tenant')->login($user);
        $request->session()->regenerate();

        $authenticationState->recordLogin($user, $request->ip(), $request->session()->getId());

        return redirect()->to(tenant_route_name('dashboard'));
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

        if ($user) {
            $authenticationState->clearCurrentSession($user);
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
        if (! tenant()) {
            return redirect('/login');
        }

        $currentHost = $request->getHost();
        $centralDomains = $this->getCentralDomains();
        $isCentralDomain = in_array($currentHost, $centralDomains);

        if ($isCentralDomain) {
            return redirect('/'.tenant('id').'/login');
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
