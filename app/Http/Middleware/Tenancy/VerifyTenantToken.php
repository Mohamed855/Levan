<?php

namespace App\Http\Middleware\Tenancy;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Api\ApiRequestLog;
use Closure;
use Illuminate\Http\Request;

class VerifyTenantToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token مفقود',
            ], 401);
        }

        $tenant = Tenant::where('api_token', hash('sha256', $token))->first();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Token غير صالح',
            ], 401);
        }

        tenancy()->initialize($tenant);

        try {
            $response = $next($request);

            ApiRequestLog::create([
                'method'      => $request->method(),
                'path'        => $request->path(),
                'status_code' => $response->getStatusCode(),
                'ip_address'  => $request->ip(),
                'user_agent'  => substr((string) $request->userAgent(), 0, 255),
            ]);

            return $response;
        } finally {
            tenancy()->end();
        }
    }
}
