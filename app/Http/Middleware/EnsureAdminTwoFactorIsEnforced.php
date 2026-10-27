<?php

namespace App\Http\Middleware;

use App\Models\Admin\Settings\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAdminTwoFactorIsEnforced
{
    public function handle(Request $request, Closure $next)
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin) {
            return $next($request);
        }

        if ($request->routeIs('*two-factor*') || $request->routeIs('*logout*')) {
            return $next($request);
        }

        if (is_null($admin->two_factor_confirmed_at) && Settings::first()?->force_two_factor_auth) {
            return redirect()->route('admin.two-factor.index')
                ->with('warning', 'سياسة النظام تفرض تفعيل المصادقة الثنائية. يرجى إعدادها للمتابعة.');
        }

        return $next($request);
    }
}
