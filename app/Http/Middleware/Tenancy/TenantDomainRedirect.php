<?php

namespace App\Http\Middleware\Tenancy;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantDomainRedirect
{
    public function handle(Request $request, Closure $next)
    {
        if (!tenancy()->initialized) {
            return $next($request);
        }

        $tenant      = tenant();
        $currentHost = $request->getHost();
        $isPathBased = in_array($currentHost, config('tenancy.central_domains'));


        // إذا كان يدخل من Path ولديه Domain مخصص
        if ($isPathBased  && $tenant->access_type === 'domain' && $tenant->custom_domain_active == 1) {

            $customDomain = $tenant->domains()->first();

            if ($customDomain) {
                // بناء URL الجديد
                $path = $request->path();

                // إزالة {tenant} من المسار
                $path = preg_replace('#^' . preg_quote($tenant->id, '#') . '/?#', '', $path);

                $newUrl = $request->getScheme() . '://' . $customDomain->domain;

                if ($path && $path !== '/') {
                    $newUrl .= '/' . ltrim($path, '/');
                }

                if ($request->getQueryString()) {
                    $newUrl .= '?' . $request->getQueryString();
                }


                return redirect()->away($newUrl, 301);
            }
        }

        return $next($request);
    }
}
