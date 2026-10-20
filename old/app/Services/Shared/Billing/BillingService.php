<?php

namespace App\Services\shared\Billing;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Subscription\Subscription;
use Stancl\Tenancy\Facades\Tenancy;

class BillingService
{
    // فاتورة معلقة قبل الدفع
    // public function createPendingBill(array $sessionData, string $tenantId = 'pending'): Bill
    // {
    //     return Bill::create([
    //         'tenant_id'       => $tenantId,
    //         'amount'          => $sessionData['final_total'],
    //         'original_amount' => $sessionData['original_total'],
    //         'discount_amount' => $sessionData['discount_amount'] ?? 0,

    //         'coupon_id'       => $sessionData['coupon_id'] ?? null,
    //         'coupon_code'     => $sessionData['coupon_code'] ?? null,
    //         'coupon_value'    => $sessionData['coupon_value'] ?? null,
    //         'coupon_type'     => $sessionData['coupon_type'] ?? null,

    //         'type'            => BillType::NEW_SUBSCRIPTION,
    //         'status'          => BillStatus::PENDING,
    //         'due_date'        => now()->addMinutes(30),
    //     ]);
    // }

    // public function createPeriodBillByAdmin($subscription, $period, $request): Bill
    // {
    //     return Tenancy::central(function () use ($subscription, $period, $request) {
    //         return Bill::create([
    //             'tenant_id'         => $subscription->tenant_id,
    //             'subscription_id'   => $subscription->id,
    //             'period_id'         => $period->id,
    //             'amount'            => $request['price'],

    //             'original_amount'   => $request['originalTotal'],
    //             'settlement_amount' => $request['discount_value'] ?? 0, // التسوية

    //             'payment_method'    => $request['payment_method'],
    //             'status'            => $request['payment_type'],
    //             'paid_at'           => now(),
    //             'type'              => BillType::NEW_SUBSCRIPTION,
    //         ]);
    //     });
    // }

    // public function updateSubscriptionBillByAdmin($subscription, $period, $request)
    // {
    //     return Tenancy::central(function () use ($subscription, $period, $request) {
    //         return Bill::create([
    //             'tenant_id'         => $subscription->tenant_id,
    //             'subscription_id'   => $subscription->id,
    //             'period_id'         => $period->id,
    //             'amount'            => $request['price'],

    //             'original_amount'   => $request['originalTotal'],
    //             'settlement_amount' => $request['discount_value'] ?? 0,

    //             'payment_method'    => $request['payment_method'],
    //             'status'            => "paid",
    //             'paid_at'           => now(),
    //             'type'              => BillType::UPDATE_SUBSCRIPTION,
    //         ]);
    //     });
    // }

    // public function createTransferBankBillByAdmin($subscription, $period, $data): Bill
    // {
    //     return Tenancy::central(function () use ($subscription, $period, $data) {
    //         return Bill::create([
    //             'tenant_id'         => $subscription->tenant_id,
    //             'subscription_id'   => $subscription->id,
    //             'period_id'         => $period->id,
    //             'amount'            => $data['price'],

    //             'original_amount'   => $data['originalTotal'],
    //             'settlement_amount' => $data['discount_value'] ?? 0, // التسوية

    //             'payment_method'    => $data['payment_method'],
    //             'status'            => $data['payment_type'],
    //             'paid_at'           => now(),
    //             'type'              => BillType::NEW_SUBSCRIPTION,
    //         ]);
    //     });
    // }

    public function createRenewalDomainBill($subscription, $period, $payment, $paidAmount, $tenant_id, $paymentMethod = PaymentMethod::ONLINE, $status = BillStatus::PAID)
    {
        return Tenancy::central(function () use ($subscription, $period, $payment, $paidAmount, $tenant_id, $paymentMethod, $status) {
            // $sessionData = session('subscription_data');

            return Bill::create([
                'gateway_payment_id' => data_get($payment, 'id'),
                'tenant_id' => $tenant_id,
                'subscription_id' => $subscription->id,
                'period_id' => $period->id,
                'amount' => $paidAmount / 100,

                'original_amount' => $paidAmount / 100,
                'discount_amount' => 0,

                // الكوبون
                'coupon_id' => $sessionData['coupon_id'] ?? null,
                'coupon_code' => $sessionData['coupon_code'] ?? null,
                'coupon_value' => $sessionData['coupon_value'] ?? null,
                'coupon_type' => $sessionData['coupon_type'] ?? null,

                'payment_method' => $paymentMethod,
                'status' => $status,
                'paid_at' => now(),
                'type' => BillType::RENEWAL_DOMAIN,
            ]);
        });
    }

    public function createNewDomainBill($subscription, $period, $payment, $paidAmount, $tenant_id, $paymentMethod = PaymentMethod::ONLINE, $status = BillStatus::PAID)
    {
        return Tenancy::central(function () use ($subscription, $period, $payment, $paidAmount, $tenant_id, $paymentMethod, $status) {
            // $sessionData = session('subscription_data');

            return Bill::create([
                'gateway_payment_id' => data_get($payment, 'id'),
                'tenant_id' => $tenant_id,
                'subscription_id' => $subscription->id,
                'period_id' => $period->id,
                'amount' => $paidAmount / 100,

                'original_amount' => $paidAmount / 100,
                'discount_amount' => 0,

                // الكوبون
                'coupon_id' => $sessionData['coupon_id'] ?? null,
                'coupon_code' => $sessionData['coupon_code'] ?? null,
                'coupon_value' => $sessionData['coupon_value'] ?? null,
                'coupon_type' => $sessionData['coupon_type'] ?? null,

                'payment_method' => $paymentMethod,
                'status' => $status,
                'paid_at' => now(),
                'type' => BillType::NEW_DOMAIN,
            ]);
        });
    }

    // // للانشاء اول مرة
    // public function createPeriodBill($subscription, $period, $payment, $paidAmount, $paymentMethod = PaymentMethod::ONLINE, $status = BillStatus::PAID): Bill
    // {
    //     return Tenancy::central(function () use ($subscription, $period, $payment, $paidAmount, $paymentMethod, $status) {
    //         $sessionData = session('subscription_data');

    //         return Bill::create([
    //             'gateway_payment_id' => data_get($payment, 'id'),
    //             'tenant_id'          => $subscription->tenant_id,
    //             'subscription_id'    => $subscription->id,
    //             'period_id'          => $period->id,
    //             'amount'             => $paidAmount,

    //             'original_amount'    => $sessionData['original_total'],
    //             'discount_amount'    => $sessionData['discount_amount'] ?? 0,

    //             // الكوبون
    //             'coupon_id'          => $sessionData['coupon_id'] ?? null,
    //             'coupon_code'        => $sessionData['coupon_code'] ?? null,
    //             'coupon_value'       => $sessionData['coupon_value'] ?? null,
    //             'coupon_type'        => $sessionData['coupon_type'] ?? null,

    //             'payment_method'     => $paymentMethod,
    //             'status'             => $status,
    //             'paid_at'            => now(),
    //             'type'               => BillType::NEW_SUBSCRIPTION,
    //         ]);
    //     });
    // }

    // // التجديد
    // public function createRenewalBill(Subscription $subscription, SubscriptionPeriod $period, $payment, $paidAmount): Bill
    // {
    //     return Tenancy::central(function () use ($subscription, $period, $payment, $paidAmount) {
    //         $sessionData = session('subscription_renew_data');

    //         return Bill::create([
    //             'gateway_payment_id' => data_get($payment, 'id'),
    //             'tenant_id'          => $subscription->tenant_id,
    //             'subscription_id'    => $subscription->id,
    //             'period_id'          => $period->id,
    //             'amount'             => $paidAmount,

    //             'original_amount'    => $sessionData['original_total'],
    //             'discount_amount'    => $sessionData['discount_amount'] ?? 0,

    //             // الكوبون
    //             'coupon_id'          => $sessionData['coupon_id'] ?? null,
    //             'coupon_code'        => $sessionData['coupon_code'] ?? null,
    //             'coupon_value'       => $sessionData['coupon_value'] ?? null,
    //             'coupon_type'        => $sessionData['coupon_type'] ?? null,

    //             'payment_method'     => PaymentMethod::ONLINE,
    //             'status'             => BillStatus::PAID,
    //             'paid_at'            => now(),
    //             'type'               => BillType::RENEWAL,
    //         ]);
    //     });
    // }

    // // تسجيل التراخيص
    // public function createLicensePurchaseBillAndAdjustment(Subscription $subscription, $payment, $paidAmount): array
    // {
    //     return Tenancy::central(function () use ($subscription, $payment, $paidAmount) {
    //         $sessionData = session('new_licenses_data');

    //         $bill = Bill::create([
    //             'gateway_payment_id' => data_get($payment, 'id'),
    //             'tenant_id'          => $subscription->tenant_id,
    //             'subscription_id'    => $subscription->id,
    //             'period_id'          => $subscription->currentPeriod->id,
    //             'amount'             => $paidAmount,

    //             'original_amount'    => $sessionData['original_total'],
    //             'discount_amount'    => $sessionData['discount_amount'] ?? 0,

    //             // الكوبون
    //             'coupon_id'          => $sessionData['coupon_id'] ?? null,
    //             'coupon_code'        => $sessionData['coupon_code'] ?? null,
    //             'coupon_value'       => $sessionData['coupon_value'] ?? null,
    //             'coupon_type'        => $sessionData['coupon_type'] ?? null,

    //             'payment_method'     => PaymentMethod::ONLINE,
    //             'status'             => BillStatus::PAID,
    //             'paid_at'            => now(),
    //             'type'               => BillType::LICENSE_PURCHASE,
    //         ]);

    //         SubscriptionLicenseAdjustment::create([
    //             'period_id'                         => $subscription->currentPeriod->id,
    //             'bill_id'                           => $bill->id,

    //             'new_user_license'                  => $sessionData['new_user_license'],
    //             'old_user_license_snapshot'         => $sessionData['old_user_license_snapshot'],
    //             'extra_user_license_price_snapshot' => $sessionData['extra_user_license_price_snapshot'],
    //         ]);

    //         return [$bill];
    //     });
    // }

    // // تم دفع الفاتورة
    // public function changBillToPaid($bill_id, $payment, $payment_method = PaymentMethod::ONLINE)
    // {
    //     Tenancy::central(function () use ($bill_id, $payment, $payment_method) {
    //         $bill = Bill::findOrfail($bill_id);

    //         $bill->update([
    //             'gateway_payment_id' => data_get($payment, 'id'),

    //             'status'             => BillStatus::PAID,
    //             'payment_method'     => $payment_method,
    //             'paid_at'            => now(),

    //         ]);
    //     });
    // }
}
