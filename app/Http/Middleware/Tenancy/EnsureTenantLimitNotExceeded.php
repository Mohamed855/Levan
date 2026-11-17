<?php

declare(strict_types=1);

namespace App\Http\Middleware\Tenancy;

use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Shared\CentralSubscriptionContextGateway;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantLimitNotExceeded
{
    public function __construct(
        private readonly CentralSubscriptionContextGateway $gateway
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $resource): Response
    {
        if ($resource === 'properties') {
            $currentCount = Property::query()->count();
            if (! $this->gateway->canCreateProperty($currentCount)) {
                $limitInfo = $this->gateway->getPropertyLimitInfo();
                $limit = $limitInfo['limit'] ?? 0;
                $message = "لقد وصلت إلى الحد الأقصى المسموح به للعقارات في باقتك ({$limit} عقار). يرجى ترقية باقتك لإضافة المزيد.";

                return $this->respondExceeded($request, $message, tenant_route_name('properties.index'));
            }
        } elseif ($resource === 'auctions') {
            $currentCount = Auction::open()->count();
            if (! $this->gateway->canCreateAuction($currentCount)) {
                $limitInfo = $this->gateway->getAuctionLimitInfo();
                $limit = $limitInfo['limit'] ?? 0;
                $message = "لقد وصلت إلى الحد الأقصى المسموح به للمزادات النشطة في باقتك ({$limit} مزاد). يرجى ترقية باقتك لإضافة المزيد.";

                return $this->respondExceeded($request, $message, tenant_route_name('auctions.index'));
            }
        }

        return $next($request);
    }

    private function respondExceeded(Request $request, string $message, string $redirectUrl): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        return redirect()->to($redirectUrl)->with('error', $message);
    }
}
