<?php

namespace App\Http\Middleware\Tenancy;

use App\Models\Tenants\Settings\Settings;
use App\Models\Admin\Tenants\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Facades\Tenancy;

class EnsureTenantTwoFactorIsEnforced
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('tenant')->user();

        if (!$user) {
            return $next($request);
        }

        if ($request->routeIs('*two-factor*') || $request->routeIs('*logout*')) {
            return $next($request);
        }

        $tenantId = tenant('id');
        $companyForced = false;

        if ($tenantId) {
            $companyForced = (bool) Tenancy::central(function () use ($tenantId) {
                return Tenant::find($tenantId)?->companySettings?->two_factor_enabled ?? false;
            });
        }

        $tenantSettingsForced = (bool) (Settings::first()?->force_two_factor_auth ?? false);

        if (is_null($user->two_factor_confirmed_at) && ($companyForced || $tenantSettingsForced)) {
            return redirect(tenant_route_name('tenants.two-factor.index'))
                ->with('warning', 'سياسة النظام تفرض تفعيل المصادقة الثنائية. يرجى إعدادها للمتابعة.');
        }

        return $next($request);
    }
}
