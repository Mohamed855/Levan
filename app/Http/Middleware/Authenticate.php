<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{

    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        if (tenant()) {
            $currentHost = $request->getHost();
            $centralDomains = array_map(function ($domain) {
                return str_replace(['http://', 'https://', 'www.'], '', $domain);
            }, config('tenancy.central_domains', []));

            $isCentralDomain = in_array($currentHost, $centralDomains) ||
                in_array('www.' . $currentHost, $centralDomains);

            return $isCentralDomain ? '/' . tenant('id') . '/login' : '/login';
        }

        return "/";
    }
}
