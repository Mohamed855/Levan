<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

if (! function_exists('tasset')) {
    function tasset(?string $path, ?string $fallback = '/static/brand/logos/logo.svg'): ?string
    {
        if (! $path) {
            return asset($fallback);
        }

        // إذا المسار كامل (URL)
        if (preg_match('#^(https?:)?//#', $path) || str_starts_with($path, 'data:')) {
            return $path;
        }

        $normalized = ltrim($path, '/');

        // إذا المسار داخل tenant_public
        if (Storage::disk('tenant_public')->exists($normalized)) {
            return Storage::disk('tenant_public')->url($normalized);
        }

        // إذا المسار داخل public المشترك
        if (Storage::disk('public')->exists($normalized)) {
            return Storage::disk('public')->url($normalized);
        }

        // فشل - استخدم البديل
        return asset($fallback);
    }
}

if (! function_exists('tenant_route_name')) {

    function tenant_route_name($name, $parameters = [], $absolute = true)
    {
        // Convert non-array parameters to array
        if (! is_array($parameters)) {
            $parameters = $parameters ? [$parameters] : [];
        }

        // If no tenant, use regular route if it exists
        if (! tenant()) {
            return Route::has($name) ? route($name, $parameters, $absolute) : '#';
        }

        $currentHost = request()->getHost();

        // Get central domains
        $centralDomains = array_values(array_map(function ($domain) {
            return str_replace(['http://', 'https://', 'www.'], '', trim($domain));
        }, config('tenancy.central_domains', [])));

        // Check if current host is central domain
        $isCentralDomain = in_array($currentHost, $centralDomains) ||
            in_array('www.'.$currentHost, $centralDomains);

        if ($isCentralDomain) {
            $cleanHost = str_replace('www.', '', $currentHost);
            $domainIndex = array_search($cleanHost, $centralDomains, true);
            $routeScope = $name === 'login' ? 'tenant' : 'path';
            $prefix = ($domainIndex === false || $domainIndex === 0) ? $routeScope.'.' : "central-domain-{$domainIndex}.{$routeScope}.";
            $routeName = $prefix.$name;

            if ($name !== 'login' && ! isset($parameters['tenant'])) {
                $parameters = array_merge(['tenant' => tenant('id')], $parameters);
            }

            if (Route::has($routeName)) {
                return route($routeName, $parameters, $absolute);
            }
        } else {
            // Domain-based
            $routeName = 'domain.'.$name;

            if (Route::has($routeName)) {
                return route($routeName, $parameters, $absolute);
            }
        }

        // Fallback: try without prefix
        if (Route::has($name)) {
            return route($name, $parameters, $absolute);
        }

        // Route not found - log warning and return placeholder
        if (app()->environment('local')) {
            // Log::warning("Tenant route not found: {$name}");
        }

        return '#';
    }
}

if (! function_exists('current_tenant_route_name')) {
    /**
     * Get current route name without prefix
     *
     * @return string|null
     */
    function current_tenant_route_name()
    {
        $currentRoute = request()->route()?->getName();

        if (! $currentRoute) {
            return null;
        }

        return preg_replace('/^(tenant|path|domain|central-domain-\d+\.tenant|central-domain-\d+\.path)\./', '', $currentRoute);
    }
}
