<?php

namespace App\Http\Middleware\Tenancy;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserHasActiveLicense
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
