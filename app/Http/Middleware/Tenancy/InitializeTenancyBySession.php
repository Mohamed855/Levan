<?php

declare(strict_types=1);

namespace App\Http\Middleware\Tenancy;

use App\Models\Admin\Tenants\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyBySession
{
    /**
     * Handle shared login and tenant-scoped administration on central domains.
     * Identifies the tenant via session, authenticated user context, POST payload, or encrypted cookie.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Resolve tenant ID by priority (without querying uninitialized tenant DB)
        $tenantId = tenant('id')
            ?? session('auth_tenant_id')
            ?? $request->input('tenant_id')
            ?? session('tenant_login_context')
            ?? $request->cookie('tenant_login_hint');

        // If no tenant context found -> fail with 404
        if (! $tenantId) {
            abort(404, 'المستأجر غير موجود');
        }

        // 2. Validate tenant existence in central DB
        /** @var Tenant|null $tenant */
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            session()->forget(['tenant_login_context', 'auth_tenant_id']);
            cookie()->queue(cookie()->forget('tenant_login_hint'));
            abort(404, 'المستأجر غير موجود');
        }

        // 3. Initialize tenancy if not already initialized
        if (! tenant() || tenant('id') !== $tenant->id) {
            tenancy()->initialize($tenant);
        }

        // 4. Enforce tenant binding before resolving a user ID in the tenant database.
        $guard = Auth::guard('tenant');
        if ($request->session()->has($guard->getName()) || $guard->hasUser()) {
            $authTenantId = session('auth_tenant_id') ?? session('tenant_login_context');
            if ($authTenantId && $authTenantId !== $tenant->id) {
                // Identity confusion detected: user logged into a different tenant!
                $guard->forgetUser();
                $request->cookies->remove($guard->getRecallerName());
                cookie()->queue(cookie()->forget($guard->getRecallerName()));
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                abort(404, 'المستأجر غير موجود');
            }
        }

        if ($guard->check()) {
            $request->session()->put('auth_tenant_id', $tenant->id);
        }

        // 5. Keep active tenant context in session and cookie
        session()->put('tenant_login_context', $tenant->id);
        cookie()->queue(cookie('tenant_login_hint', $tenant->id, 60 * 24 * 30));

        return $next($request);
    }
}
