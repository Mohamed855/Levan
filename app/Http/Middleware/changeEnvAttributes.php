<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class changeEnvAttributes
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if ($request->hasHeader('levan')) {
            config([
                'logging.default' => 'errorlog',
            ]);
            config(['cache.default', 'array']);
        }

        return $next($request);
    }
}
