<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        if (in_array('admin', $guards, true) || $request->is('superadmin*')) {
            $this->auth->shouldUse('admin');
        }

        return parent::handle($request, $next, ...$guards);
    }

    protected function unauthenticated($request, array $guards)
    {
        if (in_array('admin', $guards, true) || $request->is('superadmin*')) {
            $this->auth->shouldUse('admin');
        }

        parent::unauthenticated($request, $guards);
    }

    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        if (tenant()) {
            session()->put('tenant_login_context', tenant('id'));
            cookie()->queue(cookie('tenant_login_hint', tenant('id'), 60 * 24 * 30));

            return tenant_route_name('login', [], false);
        }

        if ($request->is('superadmin*')) {
            return route('admin.login');
        }

        if ($request->is('admin*')) {
            $tenantId = session('auth_tenant_id') ?? session('tenant_login_context');

            return $tenantId ? route('tenant.login', [], false) : '/';
        }

        return '/';
    }
}
