<?php

declare(strict_types=1);

namespace App\Http\Middleware\Tenancy;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCustomerSubresourceOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->route('customer');

        if (! $customer instanceof Customer) {
            abort(404);
        }

        foreach ($request->route()->parameters() as $name => $parameter) {
            if ($name === 'customer' || ! $parameter instanceof Model) {
                continue;
            }

            if ($parameter->getAttribute('customer_id') !== null
                && (int) $parameter->getAttribute('customer_id') !== (int) $customer->getKey()) {
                abort(404);
            }
        }

        return $next($request);
    }
}
