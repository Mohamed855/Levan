<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        if (tenant()) {
            $currentHost = $request->getHost();
            $centralDomains = array_map(function ($domain) {
                return str_replace(['http://', 'https://', 'www.'], '', $domain);
            }, config('tenancy.central_domains', []));

            $isCentralDomain = in_array($currentHost, $centralDomains) ||
                in_array('www.'.$currentHost, $centralDomains);

            if ($isCentralDomain) {
                session()->put('tenant_login_context', tenant('id'));
                cookie()->queue(cookie('tenant_login_hint', tenant('id'), 60 * 24 * 30));

                return '/admin/login';
            }

            return '/login';
        }

        if ($request->is('superadmin*')) {
            return route('admin.login');
        }

        if ($request->is('admin*')) {
            return '/admin/login';
        }

        return '/';
    }
}
