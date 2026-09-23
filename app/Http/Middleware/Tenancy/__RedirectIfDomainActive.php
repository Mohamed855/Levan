<?php

namespace App\Http\Middleware\Tenancy;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class __RedirectIfDomainActive
{
    /**
     * إعادة توجيه من Path إلى Domain إذا كان Domain مفعّل
     */
    public function handle(Request $request, Closure $next): Response
    {
        // إذا لم يتم تهيئة التينانت، لا تفعل شيء
        if (!tenancy()->initialized) {
            return $next($request);
        }

        $tenant = tenant();

        // تحقق من تفعيل الدومين المخصص
        if ($tenant->custom_domain_active && $tenant->access_type === 'domain') {
            $customDomain = $tenant->domains()->first();

            if ($customDomain) {
                $currentHost = $request->getHost();
                $centralDomains = config('tenancy.central_domains', []);

                // ✅ التحويل فقط إذا المستخدم حالياً على دومين مركزي (path-based)
                if (in_array($currentHost, $centralDomains, true)) {
                    $scheme = $request->getScheme();

                    // إزالة {tenant} من بداية المسار
                    $path = $request->getPathInfo();
                    $pattern = '#^/' . preg_quote($tenant->id, '#') . '/?#i';
                    $path = preg_replace($pattern, '/', $path);

                    // بناء الرابط الجديد
                    $newUrl = "{$scheme}://{$customDomain->domain}" . $path;

                    if ($request->getQueryString()) {
                        $newUrl .= '?' . $request->getQueryString();
                    }

                    return redirect()->away($newUrl, 301);
                }
            }
        }

        return $next($request);
    }
}
