<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                if ($guard === 'admin') {
                    return redirect(RouteServiceProvider::ADMIN);
                }

                $currentHost = $request->getHost();
                $centralDomains = array_map(function ($domain) {
                    return str_replace(['http://', 'https://', 'www.'], '', $domain);
                }, config('tenancy.central_domains', []));

                $isCentralDomain = in_array($currentHost, $centralDomains, true) ||
                    in_array('www.'.$currentHost, $centralDomains, true);

                if (tenant() || $guard === 'tenant' || session()->has('auth_tenant_id')) {
                    return $isCentralDomain ? redirect('/'.tenant('id').'/dashboard') : redirect('/dashboard');
                }

                return redirect(RouteServiceProvider::ADMIN);
            }
        }

        return $next($request);
    }
}
