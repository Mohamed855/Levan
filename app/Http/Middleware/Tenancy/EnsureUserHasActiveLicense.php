<?php

namespace App\Http\Middleware\Tenancy;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserHasActiveLicense
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        if (! $user->license_seat_id) {

            // تسجيل الخروج
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->to(tenant_route_name('login'))->withErrors([
                'email' => 'حسابك غير مرتبط بترخيص فعّال. يرجى التواصل مع الإدارة لتفعيل حسابك',
            ]);
        }

        return $next($request);
    }
}
