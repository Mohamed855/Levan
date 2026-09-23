<?php

namespace App\Http\Middleware\Tenancy;

use App\Models\Tenants\Settings\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureTenantTwoFactorIsEnforced
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('tenant')->user();

        if ($user && is_null($user->two_factor_confirmed_at) && Settings::first()?->force_two_factor_auth) {
            return redirect(tenant_route_name('tenants.two-factor.index'))
                ->with('warning', 'سياسة النظام تفرض تفعيل المصادقة الثنائية. يرجى إعدادها للمتابعة.');
        }

        return $next($request);
    }
}
