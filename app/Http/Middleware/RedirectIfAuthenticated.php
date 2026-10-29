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
                // ✅ إذا المستخدم مسجل دخول، وجّهه للصفحة الصحيحة
                if (tenant()) {
                    $currentHost = $request->getHost();
                    $centralDomains = array_map(function ($domain) {
                        return str_replace(['http://', 'https://', 'www.'], '', $domain);
                    }, config('tenancy.central_domains', []));

                    $isCentralDomain = in_array($currentHost, $centralDomains) ||
                        in_array('www.'.$currentHost, $centralDomains);

                    if ($isCentralDomain) {
                        return redirect('/admin/dashboard');
                    } else {
                        return redirect('/dashboard');
                    }
                }

                return redirect(RouteServiceProvider::ADMIN);
            }
        }

        return $next($request);
    }
}
