<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\QuoteCouponRequest;
use App\Models\Admin\Plans\Plan;
use App\Services\Web\CouponQuoteService;
use Illuminate\Http\JsonResponse;

final class CouponQuoteController extends Controller
{
    public function __invoke(QuoteCouponRequest $request, Plan $plan, CouponQuoteService $quotes): JsonResponse
    {
        abort_unless($plan->publiclyAvailable()->whereKey($plan)->exists(), 404);
        $quote = $quotes->quote($plan, $request->validated('coupon_code'));

        return response()->json([
            'success' => true,
            'message' => 'تم تطبيق كود الخصم.',
            'coupon' => ['code' => $quote['coupon_code']],
            'quote' => $quote,
            'raw_discount' => $quote['discount_amount'],
            'raw_total_after_discount' => $quote['total_amount'],
        ]);
    }
}
