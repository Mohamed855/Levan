<?php

namespace App\Services\Shared\Coupons;

use App\Models\Admin\Coupons\Coupon;
use Illuminate\Support\Facades\Log;

class CouponService
{
    public function processCoupon(?string $couponCode, $total): array
    {
        $result = [
            'discount_amount' => 0,
            'final_total' => $total,

            'coupon_id' => null,
            'coupon_code' => null,
            'coupon_value' => null,
            'coupon_type' => null,
        ];

        if (empty($couponCode)) {
            return $result;
        }

        $couponResult = $this->validateAndCalculate($couponCode, $total);

        // في حالة فشل التحقق
        if (! $couponResult['success']) {
            return ['error' => $couponResult['message']];
        }

        return [
            'discount_amount' => $couponResult['raw_discount'],
            'final_total' => $couponResult['raw_total_after_discount'],

            'coupon_id' => $couponResult['coupon']['id'],
            'coupon_code' => $couponCode,
            'coupon_value' => $couponResult['coupon']['discount_value'],
            'coupon_type' => $couponResult['coupon']['discount_type'],
        ];
    }

    // التحقق من صحة الكوبون
    public function validateAndCalculate(string $couponCode, float $total): array
    {
        try {
            // البحث عن الكوبون
            $coupon = $this->findActiveCoupon($couponCode);

            if (! $coupon) {
                return $this->errorResponse('كود الخصم غير صحيح ');
            }

            // التحقق من صلاحية الكوبون
            $validationResult = $this->validateCoupon($coupon);

            if (! $validationResult['valid']) {
                return $this->errorResponse($validationResult['message']);
            }

            // حساب الخصم
            $calculationResult = $this->calculateDiscount($coupon, $total);

            if ($calculationResult['discount'] <= 0) {
                return $this->errorResponse('لا يمكن تطبيق هذا الكوبون على طلبك الحالي');
            }

            return $this->successResponse($coupon, $calculationResult);
            //
        } catch (\Exception $e) {
            Log::info($e);

            return $this->errorResponse('حدث خطأ أثناء التحقق من الكوبون');
        }
    }

    public function findActiveCoupon($code): ?Coupon
    {
        return Coupon::where('code', $code)->active()->first();
    }

    public function applyCoupon(Coupon $coupon): bool
    {
        try {
            $coupon->incrementUsage();

            if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
                $coupon->update(['is_active' => false]);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /*
    |============================================================================
    |============================================================================
    |                            Private function
    |============================================================================
    |============================================================================
    */
    private function validateCoupon(Coupon $coupon): array
    {
        // التحقق من تاريخ الانتهاء
        if ($coupon->isExpired()) {
            return [
                'valid' => false,
                'message' => 'انتهت صلاحية كود الخصم في تاريخ '.$coupon->expiry_date->format('Y/m/d'),
            ];
        }

        // التحقق من حد الاستخدام
        if ($coupon->hasReachedLimit()) {
            return [
                'valid' => false,
                'message' => 'تم استنفاذ عدد مرات استخدام هذا الكود',
            ];
        }

        return [
            'valid' => true,
            'message' => 'الكوبون صالح',
        ];
    }

    public function calculateDiscount(Coupon $coupon, float $total): array
    {
        // حساب قيمة الخصم
        $discount = $coupon->calculateDiscount($total);

        // حساب المبالغ النهائية
        $totalAfterDiscount = $total - $discount;

        return [
            'discount' => $discount,
            'total_after_discount' => $totalAfterDiscount,

        ];
    }

    // ارجاع رسالة النجاح
    private function successResponse(Coupon $coupon, array $calculation): array
    {
        return [
            'success' => true,
            'message' => 'تم تطبيق كود الخصم بنجاح',

            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type->value,
                'discount_value' => (float) $coupon->discount_value,
            ],

            'raw_discount' => number_format($calculation['discount'], 2, '.', ''),
            'raw_total_after_discount' => number_format($calculation['total_after_discount'], 2, '.', ''),
        ];
    }

    private function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
        ];
    }
}
