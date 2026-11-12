<?php

declare(strict_types=1);

namespace App\Http\Middleware\Tenancy;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

final class RedirectTenantAuthToShared
{
    public function handle(Request $request, Closure $next): Response
    {
        // Existing submissions remain compatible; new forms use shared actions.
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $parameters = array_merge(
            Arr::except($request->route()->parameters(), ['tenant']),
            Arr::except($request->query(), ['tenant_id']),
        );

        if (in_array('signed', $request->route()->gatherMiddleware(), true)) {
            abort_unless($request->hasValidSignature(), 403);
            $parameters = Arr::except($parameters, ['signature', 'expires']);
            $parameters['tenant_id'] = tenant('id');
            $routeName = preg_replace('/(^|\.)path\./', '$1tenant.', $request->route()->getName());
            $expiration = $request->has('expires') ? now()->setTimestamp((int) $request->query('expires')) : null;
            $destination = URL::signedRoute($routeName, $parameters, $expiration);
        } else {
            $destination = tenant_route_name(current_tenant_route_name(), $parameters, false);
        }

        $request->session()->reflash();

        return redirect()->to($destination);
    }
}
