<?php

namespace App\Http\Middleware\Tenancy;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SingleSessionPerUser
{
    /*
    |--------------------------------------------------------------------------
    | Prevent Concurrent User Sessions
    |--------------------------------------------------------------------------
    | يمنع تسجيل الدخول المتزامن - يسمح بجلسة واحدة فقط لكل مستخدم
    | عند محاولة تسجيل الدخول من جهاز جديد، يتم تسجيل خروج الجلسة القديمة
    */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('tenant')->check()) {
            $user = Auth::guard('tenant')->user();
            $currentSessionId = session()->getId();

            if ($user->current_session_id && $user->current_session_id !== $currentSessionId) {
                Auth::guard('tenant')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return $this->redirectToLogin($request)
                    ->with('warning', 'تم تسجيل دخولك من جهاز آخر. يرجى تسجيل الدخول مرة أخرى.');
            }
        }

        return $next($request);
    }

    /*
    |--------------------------------------------------------------------------
    | Redirect to Appropriate Login Page
    |--------------------------------------------------------------------------
    | توجيه المستخدم إلى صفحة تسجيل الدخول المناسبة حسب نوع الـ URL
    */
    protected function redirectToLogin(Request $request)
    {
        if (!tenant()) {
            return redirect('/login');
        }

        $currentHost = $request->getHost();
        $centralDomains = $this->getCentralDomains();
        $isCentralDomain = in_array($currentHost, $centralDomains);

        if ($isCentralDomain) {
            return redirect('/' . tenant('id') . '/login');
        }

        return redirect('/login');
    }

    /*
    |--------------------------------------------------------------------------
    | Get Cleaned Central Domains
    |--------------------------------------------------------------------------
    | الحصول على قائمة النطاقات المركزية بعد تنظيفها
    */
    protected function getCentralDomains(): array
    {
        return array_map(function ($domain) {
            return str_replace(['http://', 'https://', 'www.'], '', $domain);
        }, config('tenancy.central_domains', []));
    }
}