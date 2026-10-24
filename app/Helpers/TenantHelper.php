<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

if (! function_exists('tasset')) {
    function tasset(?string $path, ?string $fallback = '/static/brand/logos/logo.svg'): ?string
    {
        if (! $path) {
            return asset($fallback);
        }

        if (preg_match('#^(https?:)?//#', $path) || str_starts_with($path, 'data:')) {
            return $path;
        }

        $normalized = ltrim($path, '/');

        if (Storage::disk('public')->exists($normalized)) {
            return Storage::disk('public')->url($normalized);
        }

        if (Storage::disk('tenant_public')->exists($normalized)) {
            return Storage::disk('tenant_public')->url($normalized);
        }

        return asset($normalized);
    }
}

if (! function_exists('tenant_route_name')) {
    function tenant_route_name(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        if (! is_array($parameters)) {
            $parameters = $parameters ? [$parameters] : [];
        }

        $tenantCandidate = 'tenant.' . $name;
        if (Route::has($tenantCandidate)) {
            return route($tenantCandidate, $parameters, $absolute);
        }

        if (Route::has($name)) {
            return route($name, $parameters, $absolute);
        }

        throw new \LogicException("Unknown tenant route [{$name}].");
    }
}

if (! function_exists('current_tenant_route_name')) {
    function current_tenant_route_name(): ?string
    {
        $current = request()->route()?->getName();
        if (! $current) {
            return null;
        }

        return preg_replace('/^tenant\./', '', $current);
    }
}
