<?php

namespace App\Services\Admin\SubscriptionManagement;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Jobs\Admin\Mail\SendSubscriptionMailJob;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Services\shared\Bills\BillService;
use App\Services\shared\Subscription\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SubscriptionManagementService
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private BillService         $billService,
    ) {}

    // Get  statistics
    public function getSubscriptionStatistics(): array
    {
        $baseQuery = Subscription::query();

        return [
            'total'         => (clone $baseQuery)->count(),
            'active'        => (clone $baseQuery)->where('status', SubscriptionStatus::ACTIVE)->count(),
            'expired'       => (clone $baseQuery)->where('status', SubscriptionStatus::EXPIRED)->count(),
            'suspended'     => (clone $baseQuery)->where('status', SubscriptionStatus::SUSPENDED)->count(),
            'expiring_soon' => $this->getExpiringSoonCount(),
        ];
    }

    public function getExpiringSoonCount(): int
    {
        return Subscription::where('status', SubscriptionStatus::ACTIVE)
            ->whereHas('currentPeriod', function ($q) {
                $q->where('status', SubscriptionPeriodStatus::ACTIVE)
                    ->where('period_end', '<=', now()->addDays(30))
                    ->where('period_end', '>=', now()->startOfDay());
            })
            ->count();
    }

    public function create(array $data): Tenant
    {
        $metadata = [
            'plan_id'                   => $data['plan_id'],
            'billing_name'              => $data['name'],
            'billing_email'             => $data['email'],
            'billing_phone'             => $data['phone'],
            'billing_password'          => $data['password'],

            'billing_company_name'      => $data['company_name'],
            'billing_commercial_number' => $data['commercial_number'],
        ];

        //  إنشاء الفاتورة
        $plan     = Plan::findOrFail($data['plan_id']);
        $discount = ($data['has_discount'] ?? 'no') === 'yes' ? (float)($data['discount_value'] ?? 0) : 0;

        $amount   = $data['payment_type'] === 'trial' ? 0 : max(0, (float)$plan->price_after_discount - $discount);

        $bill = $this->billService->createPendingBill(
            amount: $amount,
            paymentMethod: $data['payment_type'] === 'paid' ? PaymentMethod::from($data['payment_method']) : null,
            originalAmount: (float) $plan->price_after_discount,
            type: BillType::NEW_SUBSCRIPTION,
            couponData: [
                'discount_amount' => 0,
                'coupon_id'       => null,
                'coupon_code'     => null,
                'coupon_value'    => null,
                'coupon_type'     => null,
            ],
            metadata: $metadata,
        );

        $result = $this->subscriptionService->completeAdminSubscription($bill, $data);

        return Tenant::findOrFail($result['tenant_id']);
    }

    // Create tenant
    // public function create__(array $data)
    // {
    //     $metadata = [
    //         'plan_id'                   => $data['plan_id'],
    //         'billing_name'              => $data['name'],
    //         'billing_email'             => $data['email'],
    //         'billing_phone'             => $data['phone'],
    //         'billing_password'          => $data['password'],

    //         'billing_company_name'      => $data['company_name'],
    //         'billing_commercial_number' => $data['commercial_number'],
    //     ];

    //     $plan = Plan::findOrFail($data['plan_id']);

    //     [$tenant, $user] = $this->tenantCreator->createTenantEnvironment($metadata);

    //     [$subscriptionStatus, $periodStatus] = $this->resolveSubscriptionStatus($data['payment_type']);

    //     $subscription = $this->subscriptionManager->createSubscription(
    //         $tenant,
    //         $plan,
    //         $this->resolveAmount($data, $plan),
    //         $subscriptionStatus,
    //         $periodStatus
    //     );

    //     //  ربط المستخدم بالاشتراك
    //     $this->tenantInitializer->createCoreSeat($tenant, $subscription, $user);

    //     //  إنشاء الفاتورة
    //     $this->createBill($data, $plan, $subscription, $tenant);

    //     // إرسال الإيميل
    //     SendSubscriptionMailJob::dispatch($data['email'], $subscription, 'new')->onQueue('email');

    //     return $tenant;
    // }

    /*
    |============================================================================
    |============================================================================
    |                           Private Methods
    |============================================================================
    |============================================================================
    */


    // public function createSubscriptionByAdmin($tenant, Request $request)
    // {
    //     // $subscriptionStatus       = $request['payment_type'] == 'paid' ? SubscriptionStatus::ACTIVE : SubscriptionStatus::SUSPENDED;
    //     // $subscriptionPeriodStatus = $request['payment_type'] == 'paid' ? SubscriptionPeriodStatus::ACTIVE : SubscriptionPeriodStatus::PENDING_PAYMENT;

    //     switch ($request['payment_type']) {
    //         case 'paid':
    //             $subscriptionStatus = SubscriptionStatus::ACTIVE;
    //             $subscriptionPeriodStatus = SubscriptionPeriodStatus::ACTIVE;
    //             break;

    //         case 'unpaid':
    //             $subscriptionStatus = SubscriptionStatus::SUSPENDED;
    //             $subscriptionPeriodStatus = SubscriptionPeriodStatus::PENDING_PAYMENT;
    //             break;

    //         case 'trial':
    //             $subscriptionStatus = SubscriptionStatus::TRIAL;
    //             $subscriptionPeriodStatus = SubscriptionPeriodStatus::TRIAL;
    //             break;

    //         default:
    //             throw new \InvalidArgumentException('نوع حالة الدفع غير صالح');
    //     }

    //     $plan = Plan::findOrfail($request['plan_id']);
    //     $this->subscriptionManager->createSubscription($tenant, $plan, $request['price'], $subscriptionStatus, $subscriptionPeriodStatus, null, $request['trial_end_date']);

    //     $subscription = $this->subscriptionManager->getTenantSubscription($tenant->id);

    //     if ($subscription && $subscription->currentPeriod) {
    //         $this->billingService->createPeriodBillByAdmin($subscription, $subscription->currentPeriod, $request);
    //     }

    //     SendSubscriptionMailJob::dispatch($tenant->first_user->email, $subscription, 'new')->onQueue('email');
    // }

    // public function updateSubscriptionByAdmin($tenant, $subscription, Request $request)
    // {
    //     // حساب إجمالي المستخدمين (المستخدمين المضمنين + التراخيص الإضافية)
    //     $plan = Plan::findOrFail($request->plan_id);
    //     $totalUsers = $plan->included_users + ($request->licenses_quota ?? 0);

    //     // التحقق من أن عدد المستخدمين المطلوب لا يقل عن المستخدمين المستهلكين حالياً
    //     if ($request->total_users < $subscription->used_users) {
    //         throw new \Exception("لا يمكن تقليل عدد المستخدمين إلى أقل من العدد المستخدم حالياً ({$subscription->used_users} مستخدم)");
    //     }

    //     // تحديث بيانات الاشتراك
    //     $updateData = [
    //         'plan_id'           => $request->plan_id,
    //         'status'            => $request->status,
    //         'payment_status'    => $request->payment_status,
    //         'price'             => $request->price,
    //         'total_users'       => $request->total_users,
    //         'user_users'        => $request->licenses_quota ?? 0,
    //         'starts_at'         => $request->starts_at,
    //         'next_payment_date' => $request->next_payment_date,
    //         'auto_renew'        => $request->has('auto_renew') ? true : false,
    //     ];

    //     $subscription->update($updateData);

    //     // تحديث معلومات الـ tenant بناءً على الباقة الجديدة

    //     return $subscription->fresh();
    // }
}