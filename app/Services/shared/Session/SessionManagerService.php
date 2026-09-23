<?php

namespace App\Services\shared\Session;

use App\Models\Admin\Plans\Plan;
use Illuminate\Support\Facades\Log;

class SessionManagerService
{
    private const SESSION_TIMEOUT = 30;

    // حفظ بيانات الاشتراك الجديد
    public function storeSubscriptionData(Plan $plan): void
    {
        session([
            'subscription_data' => [
                'plan_id'         => $plan->id,

                'original_total'  => null,
                'discount_amount' => 0,
                'final_total'     => null,

                'coupon_id'       => null,
                'coupon_code'     => null,
                'coupon_type'     => null,
                'coupon_value'    => null,

                //
                'created_at'      => now(),
            ]
        ]);
    }


    //حفظ بيانات الفوترة
    public function storeBillingData(array $data): void
    {
        session([
            'billing_data' => [
                'billing_name'              => $data['billing_name'],
                'billing_email'             => $data['billing_email'],
                'billing_password'          => $data['billing_password'],
                'billing_phone'             => $data['billing_phone'],
                'billing_company_name'      => $data['billing_company_name'],
                'billing_commercial_number' => $data['billing_commercial_number'],
            ]
        ]);
    }

    //حفظ بيانات تجديد الاشتراك
    public function storeRenewalData(): void
    {
        session([
            'subscription_renew_data' => [
                // بعد الكوبون
                'original_total'  => null,
                'discount_amount' => 0,
                'final_total'     => null,

                'coupon_id'       => null,
                'coupon_code'     => null,
                'coupon_type'     => null,
                'coupon_value'    => null,

                'created_at'      => now(),
            ]
        ]);
    }

    //حفظ بيانات الدفع المعلق (للكوبون)
    public function storePendingPaymentData($coupon_code): void
    {
        session([
            'pending_payment' => [
                'coupon_code'     => $coupon_code ?? null,
                'created_at'      => now(),
            ]
        ]);
    }

    // شراء تراخيص
    public function storeNewLicensesData($newUserLicense, $oldUserLicense, $user_license_price): void
    {
        session([
            'new_licenses_data' => [
                'new_user_license'                   => $newUserLicense,
                'old_user_license_snapshot'          => $oldUserLicense,
                'extra_user_license_price_snapshot'  => $user_license_price,

                // بعد الكوبون
                'original_total'  => null,
                'discount_amount' => 0,
                'final_total'     => null,

                'coupon_id'       => null,
                'coupon_code'     => null,
                'coupon_type'     => null,
                'coupon_value'    => null,

                'created_at'      => now(),
            ]
        ]);
    }


    // بيانات دفع فاتورة
    public function storePaymentData($bill): void
    {
        session([
            'payment_data' => [
                'bill_id'           => $bill->id,
                'final_total'       => $bill->amount,
                'created_at'        => now(),
            ]
        ]);
    }

    // التحقق من صحة بيانات الجلسة
    public function validateSession(string $key = 'subscription_data'): ?array
    {
        $data = session($key);

        if (!$data) {
            return null;
        }

        // التحقق من انتهاء الصلاحية
        if (
            isset($data['created_at']) &&
            now()->diffInMinutes($data['created_at']) > self::SESSION_TIMEOUT
        ) {
            Log::info("⏰ انتهت صلاحية الجلسة: {$key}");
            $this->clear($key);
            return null;
        }

        return $data;
    }


    //مسح بيانات الجلسة
    public function clear(?string $key = null): void
    {
        if ($key) {
            session()->forget($key);
        } else {
            session()->forget([
                'subscription_data',
                'subscription_renew_data',
                'change_billing_data',
                'billing_data',
                'pending_payment',
                'new_licenses_data',
                'payment_data',
            ]);
        }
    }

    //  الحصول على بيانات من الجلسة
    public function get(string $key, $default = null)
    {
        return session($key, $default);
    }

    // التحقق من وجود بيانات
    public function has(string $key): bool
    {
        return session()->has($key);
    }

    //  تحديث بيانات معينة في الجلسة
    public function update(string $key, array $data): void
    {
        $existing = session($key, []);
        session([$key => array_merge($existing, $data)]);
    }
}