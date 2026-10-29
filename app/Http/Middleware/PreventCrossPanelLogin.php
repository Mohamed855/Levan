<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Http\Controllers\Tenants\Auth\AuthenticatedSessionController;
use App\Models\Admin\Tenants\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class PreventCrossPanelLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        $action = ltrim((string) $request->route()?->getActionName(), '\\');

        if (in_array($action, [AuthenticatedSessionController::class.'@create', AuthenticatedSessionController::class.'@store'], true)
            && Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        if (in_array($action, [AdminAuthController::class.'@showLoginForm', AdminAuthController::class.'@login'], true)
            && $this->hasAuthenticatedTenantSession($request)) {
            return redirect()->to(route('tenant.dashboard', [], false));
        }

        return $next($request);
    }

    private function hasAuthenticatedTenantSession(Request $request): bool
    {
        $guard = Auth::guard('tenant');
        if ($guard->hasUser()) {
            return true;
        }

        // A remembered tenant hint alone is not an authenticated session.
        if (! $request->session()->has($guard->getName()) || ! $tenantId = $request->session()->get('auth_tenant_id')) {
            return false;
        }

        $tenant = Tenant::query()->find($tenantId);
        if (! $tenant) {
            return false;
        }

        $previousTenant = tenant();
        try {
            tenancy()->initialize($tenant);
            return $guard->check();
        } finally {
            if ($previousTenant) {
                tenancy()->initialize($previousTenant);
            } else {
                tenancy()->end();
            }
        }
    }
}
