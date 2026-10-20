<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetTenantUrlDefaults
{
    public function handle(Request $request, Closure $next): Response
    {
        if (tenancy()->initialized && tenant()) {
            $currentHost = $request->getHost();

            // قائمة النطاقات المركزية
            $centralDomains = array_map(function ($domain) {
                return str_replace(['http://', 'https://', 'www.'], '', $domain);
            }, config('tenancy.central_domains', []));

            // التحقق من أن الـ host الحالي هو نطاق مركزي
            $isCentralDomain = in_array($currentHost, $centralDomains) ||
                in_array('www.'.$currentHost, $centralDomains);

            if ($isCentralDomain) {
                // Path-based: أضف tenant لكل الروابط
                URL::defaults(['tenant' => tenant('id')]);
            } else {
                // Domain-based: لا تضيف tenant
                URL::defaults([]);
            }
        }

        return $next($request);
    }
}
