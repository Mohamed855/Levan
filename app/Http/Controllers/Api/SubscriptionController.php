<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Shared\Coupons\CouponService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(private CouponService $couponService) {}

    //   التحقق من صحة الكوبون
    public function validateCoupon(Request $request)
    {
        $validated = $request->validate([
            'coupon_code' => 'required|string',
            'total' => 'required|numeric|min:0',
        ]);

        $result = $this->couponService->validateAndCalculate(
            $validated['coupon_code'],
            $validated['total']
        );

        $statusCode = $result['success'] ? 200 : 422;

        if ($result['success']) {
            $request->session()->put('checkout_coupon_code', $validated['coupon_code']);
            if ($request->session()->has('checkout_verification')) {
                $verification = $request->session()->get('checkout_verification');
                $verification['coupon_code'] = $validated['coupon_code'];
                $request->session()->put('checkout_verification', $verification);
            }
        }

        return response()->json($result, $statusCode);
    }

    public function removeCoupon(Request $request)
    {
        $request->session()->forget('checkout_coupon_code');

        if ($request->session()->has('checkout_verification')) {
            $verification = $request->session()->get('checkout_verification');
            unset($verification['coupon_code']);
            $request->session()->put('checkout_verification', $verification);
        }

        return response()->json(['success' => true]);
    }
}
