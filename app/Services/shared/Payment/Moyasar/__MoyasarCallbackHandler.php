<?php

namespace App\Services\shared\Payment\Moyasar;

use App\Enums\Admin\Billing\BillStatus;
use App\Jobs\Admin\Mail\SendSubscriptionMailJob;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Plans\Plan;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Admin\Tenant\TenantCreator;
use App\Services\shared\Billing\BillingService;
use App\Services\shared\Coupons\CouponService;
use App\Services\shared\Payment\Contracts\PaymentGatewayInterface;
use App\Services\shared\Subscription\SubscriptionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;

class __MoyasarCallbackHandler
{
    public function __construct(
        private PaymentGatewayInterface $gateway,
        private TenantCreator               $tenantCreator,
        private SubscriptionManager         $subscriptionManager,
        private BillingService              $billingService,
        private CouponService                   $couponService,

    ) {}



    public function verify(Request $request): array
    {
        $paymentId  = $request->get('id');
        $billNumber = $request->get('bill');

        if (!$paymentId || !$billNumber) {
            return $this->errorResponse('بيانات الطلب غير مكتملة.');
        }

        $bill = Bill::where('bill_number', $billNumber)->where('status', BillStatus::PENDING)->first();

        if (!$bill) {
            return $this->errorResponse('العملية غير موجودة أو تمت معالجتها مسبقاً.');
        }

        if ($bill->due_date && now()->isAfter($bill->due_date)) {
            $bill->update(['status' => BillStatus::CANCELLED]);
            return $this->errorResponse('انتهت صلاحية هذه العملية.');
        }

        $payment = $this->gateway->getPayment($paymentId);

        if (!isset($payment['status']) || $payment['status'] !== 'paid') {
            return $this->errorResponse("فشل الدفع. الحالة: " . ($payment['status'] ?? 'unknown'));
        }

        $paidAmount = (float) $payment['amount'] / 100;

        if (abs($paidAmount - (float) $bill->amount) > 0.01) {
            Log::critical('مبلغ الدفع لا يطابق المتوقع', [
                'paid'        => $paidAmount,
                'expected'    => $bill->amount,
                'bill_number' => $billNumber,
            ]);
            return $this->errorResponse('مبلغ الدفع غير متطابق.');
        }

        return [
            'success' => true,
            'bill'    => $bill,
            'payment' => $payment,
        ];
    }


    private function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'message' => $message
        ];
    }














    // اشتراك لعميل موجود مسبقا
    public function handleSubscriptionToTenant(Request $request): array
    {
        try {
            $result = $this->verifyPaymentAndGetData($request, 'subscription_data');

            if (!$result['success']) return $result;

            $payment     = $result['payment'];
            $sessionData = $result['sessionData'];
            $paidAmount  = $result['paidAmount'];
            $tenant      = tenant();

            // لانشاء الاشتراك
            $plan = Tenancy::central(fn() => Plan::findOrfail($sessionData['plan_id']));

            $this->subscriptionManager->createSubscription($tenant, $plan, $paidAmount);

            $subscription = $this->subscriptionManager->getTenantSubscription($tenant->id);

            if ($subscription && $subscription->currentPeriod) {
                $this->billingService->createPeriodBill($subscription, $subscription->currentPeriod, $payment, $paidAmount);
            }

            // ارسال الايميل بعد النجاح
            SendSubscriptionMailJob::dispatch(auth()->user()->email, $subscription, 'new')->onQueue('email');

            return [
                'success'   => true,
                'tenant_id' => $tenant->id,
                'message'   => 'تم الدفع بنجاح! يمكنك الان التمتع بكامل خدمات نظام مسند',
            ];
        } catch (\Exception $e) {
            Log::error($e);
            return $this->errorResponse('حدث خطأ أثناء معالجة عملية الدفع');
        }
    }


    // الاشتارك او لمرة
    // public function handleNewSubscription(Request $request): array
    // {
    //     try {
    //         $result = $this->verifyPaymentAndGetData($request);

    //         if (!$result['success']) return $result;

    //         $bill     = $result['bill'];
    //         $payment  = $result['payment'];
    //         $metadata = $bill->metadata;


    //         if (empty($metadata['plan_id']) || empty($metadata['billing_email'])) {
    //             return $this->errorResponse('بيانات الاشتراك غير مكتملة.');
    //         }


    //         // Create tenant and subscription
    //         $tenant       = $this->tenantCreator->initializeTenantEnvironment($metadata['plan_id'], $bill->amount, $metadata);
    //         $subscription = $this->subscriptionManager->getTenantSubscription($tenant->id);

    //         if (!$subscription) {
    //             Log::critical('تم إنشاء الـ tenant لكن الاشتراك غير موجود', [
    //                 'tenant_id'   => $tenant->id,
    //                 'bill_number' => $bill->bill_number,
    //             ]);
    //             return $this->errorResponse('حدث خطأ أثناء إنشاء الاشتراك.');
    //         }

    //         $bill->update([
    //             'tenant_id'          => $tenant->id,
    //             'subscription_id'    => $subscription->id,
    //             'gateway_payment_id' => $payment['id'],
    //             'status'             => BillStatus::PAID,
    //             'payment_method'     => PaymentMethod::ONLINE,
    //             'paid_at'            => now(),
    //         ]);

    //         if ($bill->coupon_code) {
    //             $coupon = $this->couponService->findActiveCoupon($bill->coupon_code);
    //             if ($coupon) {
    //                 $this->couponService->applyCoupon($coupon);
    //             }
    //         }

    //         // if ($subscription && $subscription->currentPeriod) {
    //         //     $this->billingService->createPeriodBill($subscription, $subscription->currentPeriod, $payment, $paidAmount);
    //         // }

    //         SendSubscriptionMailJob::dispatch($metadata['billing_email'], $subscription, 'new')->onQueue('email');
    //         // ارسال الايميل بعد النجاح
    //         // SendSubscriptionMailJob::dispatch($billingData['billing_email'], $subscription, 'new')->onQueue('email');

    //         return [
    //             'success'   => true,
    //             'tenant_id' => $tenant->id,
    //             'message'   => 'تم الدفع وإنشاء الحساب بنجاح! يمكنك تسجيل الدخول الآن.',
    //         ];
    //     } catch (\Exception $e) {
    //         Log::error($e);
    //         return $this->errorResponse('حدث خطأ أثناء معالجة عملية الدفع');
    //     }
    // }


    // التجديد
    public function handleRenewSubscription(Request $request): array
    {
        try {
            $result = $this->verifyPaymentAndGetData($request, 'subscription_renew_data');

            if (!$result['success']) return $result;

            $payment      = $result['payment'];
            $paidAmount   = $result['paidAmount'];

            $tenant = tenant();

            if (!$tenant) {
                return $this->errorResponse('تعذر تحديد العميل الحالي أثناء التجديد.');
            }

            [$subscription, $period] = $this->subscriptionManager->renewSubscription($tenant, $paidAmount);

            $this->billingService->createRenewalBill($subscription, $period, $payment, $paidAmount);

            SendSubscriptionMailJob::dispatch(Auth::user()->email, $subscription, 'renew')->onQueue('email');

            return [
                'success'   => true,
                'tenant_id' => $tenant->id,
                'message'   => 'تم تجديد الاشتراك بنجاح',
            ];
        } catch (\Exception $e) {
            Log::error($e);
            return $this->errorResponse('حدث خطأ أثناء معالجة عملية التجديد');
        }
    }


    // شراء تراخيض اضافية
    public function handleUpgradeSubscription(Request $request): array
    {
        try {
            $result = $this->verifyPaymentAndGetData($request, 'new_licenses_data');

            if (!$result['success']) return $result;

            $payment     = $result['payment'];
            $sessionData = $result['sessionData'];
            $paidAmount  = $result['paidAmount'];

            $tenant = tenant();

            if (!$tenant) {
                return $this->errorResponse('تعذر تحديد التينانت الحالي أثناء شراء التراخيص الاضافية .');
            }

            $subscription  = $this->subscriptionManager->upgradeSubscription($tenant,  $sessionData['new_user_license']);

            $this->billingService->createLicensePurchaseBillAndAdjustment($subscription, $payment, $paidAmount);

            SendSubscriptionMailJob::dispatch(Auth::user()->email, $subscription, 'upgrade')->onQueue('email');

            return [
                'success'   => true,
                'tenant_id' => $tenant->id,
                'message'   => 'تم شراء التراخيص بنجاح وإضافة الفاتورة.',
            ];
        } catch (\Exception $e) {
            //
            Log::error($e);
            return $this->errorResponse('حدث خطأ أثناء معالجة عملية الترقية');
        }
    }


    // دفع فاتورة
    public function handlePaymetn(Request $request): array
    {
        try {
            $result = $this->verifyPaymentAndGetData($request, 'payment_data');

            if (!$result['success']) return $result;

            $payment     = $result['payment'];
            $sessionData = $result['sessionData'];
            $tenant      = tenant();

            // اجلب الاشتراك الحالي
            $subscription = $this->subscriptionManager->getTenantSubscription($tenant->id);

            if (!$subscription) {
                return $this->errorResponse('لا يوجد اشتراك  ');
            }

            $this->subscriptionManager->changeSubscriptionToActive($tenant);

            $this->billingService->changBillToPaid($sessionData['bill_id'], $payment);

            cache()->forget("tenant:{$tenant->id}:subscription");

            return [
                'success'   => true,
                'message'   => 'تم دفع الفاتورة بنجاح',
            ];
        } catch (\Exception $e) {
            Log::error($e);
            return $this->errorResponse('حدث خطأ أثناء معالجة عملية  الدفع');
        }
    }



    /*
    |============================================================================
    |============================================================================
    |                            Private Methods
    |============================================================================
    |============================================================================
    */
    private function verifyPaymentAndGetData(Request $request): array
    {
        $paymentId  = $request->get('id');
        $billNumber = $request->get('bill');

        if (!$paymentId || !$billNumber) {
            return $this->errorResponse('بيانات الطلب غير مكتملة.');
        }

        $bill = Bill::where('bill_number', $billNumber)->where('status', BillStatus::PENDING)->first();

        if (!$bill) {
            return $this->errorResponse('العملية غير موجودة أو تمت معالجتها مسبقاً.');
        }

        //  نتحقق من الصلاحية
        if ($bill->due_date && now()->isAfter($bill->due_date)) {
            $bill->update(['status' => BillStatus::CANCELLED]);
            return $this->errorResponse('انتهت صلاحية هذه العملية.');
        }

        $payment = $this->moyasarGateway->getPayment($paymentId);

        if (!isset($payment['status']) || $payment['status'] !== 'paid') {
            return $this->errorResponse("فشل الدفع. الحالة: " . ($payment['status'] ?? 'unknown'));
        }


        //  نتحقق من المبلغ
        $paidAmount = (float) $payment['amount'] / 100;

        if (abs($paidAmount - (float) $bill->amount) > 0.01) {
            Log::critical('مبلغ الدفع لا يطابق المتوقع', [
                'paid'        => $paidAmount,
                'expected'    => $bill->amount,
                'bill_number' => $billNumber,
            ]);
            return $this->errorResponse('مبلغ الدفع غير متطابق.');
        }

        return [
            'success' => true,
            'payment' => $payment,
            'bill'    => $bill,
        ];
    }
}
