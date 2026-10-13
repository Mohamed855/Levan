<?php

namespace App\Http\Middleware\Tenancy;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantMaintenance
{
    public function handle(Request $request, Closure $next)
    {
        if (! tenant()) {
            return $next($request);
        }

        try {
            $maintenance = (bool) DB::connection('tenant')->table('settings')->value('maintenance_mode');

            if ($maintenance) {
                return response()->view('tenants.errors.maintenance_mode', [], 503);
            }
        } catch (\Throwable $e) {
            Log::warning('TenantMaintenance error: '.$e->getMessage());
        }

        return $next($request);
    }
}
