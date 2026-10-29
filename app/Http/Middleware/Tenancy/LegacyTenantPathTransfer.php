<?php

declare(strict_types=1);

namespace App\Http\Middleware\Tenancy;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LegacyTenantPathTransfer
{
    /**
     * Intercepts legacy /{tenant_id}/* inbound requests.
     * Sets tenant context in session/cookie and transfers the user cleanly to /admin/*.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (tenant()) {
            $tenantId = tenant('id');

            // Set context in session and encrypted cookie
            session()->put('tenant_login_context', $tenantId);
            cookie()->queue(cookie('tenant_login_hint', $tenantId, 60 * 24 * 30));

            // Extract the subpath after /{tenant_id}/
            $subpath = preg_replace('#^' . preg_quote($tenantId, '#') . '(/|$)#', '', $request->path());
            $subpath = trim((string) $subpath, '/');

            // 1. If /{tenant_id}/login -> redirect cleanly to /admin/login
            if ($subpath === 'login') {
                return redirect('/admin/login');
            }

            $cleanTarget = '/admin' . ($subpath !== '' ? '/' . $subpath : '/dashboard');

            // 2. If authenticated as tenant user -> transfer directly to clean URL
            if (Auth::guard('tenant')->check()) {
                session()->put('auth_tenant_id', $tenantId);

                return redirect($cleanTarget);
            }

            // 3. If guest -> store intended URL and transfer cleanly to /admin/login
            session()->put('url.intended', url($cleanTarget));

            return redirect('/admin/login');
        }

        return $next($request);
    }
}
