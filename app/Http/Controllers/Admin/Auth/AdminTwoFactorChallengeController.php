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

        $intended = $request->session()->pull('url.intended');
        $target = is_string($intended) ? parse_url($intended) : false;
        $path = is_array($target) ? ($target['path'] ?? '') : '';
        $isAdminPath = $path === RouteServiceProvider::ADMIN || str_starts_with($path, RouteServiceProvider::ADMIN.'/');
        $hasDotSegment = preg_match('~(?:^|/)\.{1,2}(?:/|$)~', rawurldecode($path)) === 1;
        $isSameOrigin = is_array($target)
            && ! isset($target['user']) && ! isset($target['pass'])
            && (! isset($target['host']) || strcasecmp($target['host'], $request->getHost()) === 0)
            && (! isset($target['port']) || $target['port'] === $request->getPort())
            && (! isset($target['scheme']) || in_array($target['scheme'], ['http', 'https'], true));

        if (! $isAdminPath || ! $isSameOrigin || $hasDotSegment || str_contains($intended, '\\') || preg_match('/[\r\n]/', $intended)) {
            return redirect(RouteServiceProvider::ADMIN);
        }

        // Rebuild an internal destination instead of forwarding an authority browsers may parse differently.
        $destination = $path
            .(isset($target['query']) ? '?'.$target['query'] : '')
            .(isset($target['fragment']) ? '#'.$target['fragment'] : '');

        return redirect($destination);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(['login.id', 'login.guard', 'login.tenant_id', 'login.remember']);

        return redirect()->route('admin.login');
    }
}
