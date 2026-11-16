<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeMonetaryInputs
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        if (! empty($input)) {
            $normalized = $this->cleanMonetaryValues($input);
            $request->replace($normalized);
        }

        return $next($request);
    }

    private function cleanMonetaryValues(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([$this, 'cleanMonetaryValues'], $value);
        }

        if (is_string($value) && preg_match('/^-?\d{1,3}(,\d{3})+(\.\d+)?$/', $value)) {
            return str_replace(',', '', $value);
        }

        return $value;
    }
}
