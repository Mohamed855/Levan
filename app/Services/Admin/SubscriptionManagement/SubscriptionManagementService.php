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
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubscriptionManagementService
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private BillService $billService,
    ) {}

    public function updateSubscription(Subscription $subscription, array $data, bool $autoRenew): Subscription
    {
        return DB::connection('mysql')->transaction(function () use ($subscription, $data, $autoRenew): Subscription {
            $tenant = Tenant::query()->findOrFail($data['tenant_id']);
            $plan = Plan::query()->findOrFail($data['plan_id']);

            if ($data['total_users'] < $plan->included_users) {
                throw new InvalidArgumentException('لا يمكن أن يقل عدد المستخدمين عن المستخدمين المشمولين في الباقة.');
            }

            $startsAt = Carbon::parse($data['starts_at']);
            if ($plan->subscription_duration_type === 'limited') {
                $endsAt = $plan->subscription_billing_cycle === 'monthly'
                    ? $startsAt->copy()->addMonth()
                    : $startsAt->copy()->addYear();
            } else {
                if (empty($data['ends_at'])) {
                    throw new InvalidArgumentException('تاريخ نهاية الاشتراك مطلوب للباقات غير محددة المدة');
                }
                $endsAt = Carbon::parse($data['ends_at']);
            }

            $tenant->update([
                'tax_number' => $data['tax_number'],
                'city_id' => $data['city_id'],
                'address' => $data['address'],
            ]);

            $subscription->update([
                'plan_id' => $data['plan_id'],
                'price' => $data['price'],
                'status' => $data['status'],
                'total_users' => $data['total_users'],
                'used_users' => $data['licenses_quota'] ?? $subscription->used_users,
                'starts_at' => $data['starts_at'],
                'ends_at' => $endsAt,
                'next_payment_date' => $data['next_payment_date'] ?? $subscription->next_payment_date,
                'auto_renew' => $autoRenew,
            ]);

            $period = $subscription->currentPeriod ?? $subscription->periods()->latest('period_end')->first();
            $period?->update(['period_start' => $data['starts_at'], 'period_end' => $endsAt]);

            return $subscription->refresh();
        });
    }

    public function accept(Subscription $subscription, int|string|null $adminId): void
    {
        DB::connection('mysql')->transaction(fn () => $this->acceptWithinTransaction($subscription, $adminId));

        cache()->forget('admin:pending_subscriptions_count');
    }

    public function reject(Subscription $subscription, ?string $reason): void
    {
        DB::connection('mysql')->transaction(fn () => $this->rejectWithinTransaction($subscription, $reason));

        cache()->forget('admin:pending_subscriptions_count');
    }

    public function massAccept(array $ids, int|string|null $adminId): void
    {
        DB::connection('mysql')->transaction(function () use ($ids, $adminId): void {
            Subscription::query()->whereKey($ids)->get()
                ->each(fn (Subscription $subscription) => $this->acceptWithinTransaction($subscription, $adminId));
        });

        cache()->forget('admin:pending_subscriptions_count');
    }

    public function massReject(array $ids, ?string $reason): void
    {
        DB::connection('mysql')->transaction(function () use ($ids, $reason): void {
            Subscription::query()->whereKey($ids)->get()
                ->each(fn (Subscription $subscription) => $this->rejectWithinTransaction($subscription, $reason));
        });

        cache()->forget('admin:pending_subscriptions_count');
    }

    public function delete(Subscription $subscription): void
    {
        DB::connection('mysql')->transaction(fn () => $subscription->delete());
    }

    private function acceptWithinTransaction(Subscription $subscription, int|string|null $adminId): void
    {
        $subscription->update(['status' => SubscriptionStatus::ACTIVE]);
        $subscription->periods()->update(['status' => SubscriptionPeriodStatus::ACTIVE]);
        Bill::query()->where('subscription_id', $subscription->id)
            ->whereIn('status', [BillStatus::PENDING, BillStatus::PENDING_VERIFICATION, BillStatus::UNPAID])
            ->update(['status' => BillStatus::PAID, 'paid_at' => now(), 'verified_by' => $adminId, 'verified_at' => now()]);
    }

    private function rejectWithinTransaction(Subscription $subscription, ?string $reason): void
    {
        $subscription->update([
            'status' => SubscriptionStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
        $subscription->periods()->update(['status' => SubscriptionPeriodStatus::CANCELLED]);
        Bill::query()->where('subscription_id', $subscription->id)
            ->whereIn('status', [BillStatus::PENDING, BillStatus::PENDING_VERIFICATION, BillStatus::UNPAID])
            ->update(['status' => BillStatus::CANCELLED, 'cancelled_at' => now(), 'reject_reason' => $reason]);
    }

    // Get  statistics
    public function getSubscriptionStatistics(): array
    {
        $baseQuery = Subscription::query();
        $now = now();

        return [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', SubscriptionStatus::PENDING)->count(),
            'active' => (clone $baseQuery)
                ->where('status', SubscriptionStatus::ACTIVE)
                ->where(function ($query) use ($now) {
                    $query->whereHas('currentPeriod', function ($q) use ($now) {
                        $q->where('status', SubscriptionPeriodStatus::ACTIVE)
                            ->where('period_start', '<=', $now)
                            ->where('period_end', '>=', $now);
                    })->orWhereDoesntHave('periods');
                })
                ->count(),
            'expired' => (clone $baseQuery)
                ->whereIn('status', [SubscriptionStatus::EXPIRED, SubscriptionStatus::CANCELLED])
                ->count(),
            'suspended' => (clone $baseQuery)->where('status', SubscriptionStatus::SUSPENDED)->count(),
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
            'plan_id' => $data['plan_id'],
            'billing_name' => $data['name'],
            'billing_email' => $data['email'],
            'billing_phone' => $data['phone'],
            'billing_password' => $data['password'],

            'billing_company_name' => $data['company_name'],
            'billing_tax_number' => $data['tax_number'],
            'billing_city_id' => $data['city_id'],
            'billing_address' => $data['address'],
            'billing_commercial_number' => $data['commercial_number'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'total_users' => $data['total_users'],
            'activate_immediately' => (bool) $data['activate_immediately'],
        ];

        //  إنشاء الفاتورة
        $plan = Plan::findOrFail($data['plan_id']);
        $discount = ($data['has_discount'] ?? 'no') === 'yes' ? (float) ($data['discount_value'] ?? 0) : 0;

        $amount = $data['payment_type'] === 'trial' ? 0 : max(0, (float) $plan->price_after_discount - $discount);

        $bill = $this->billService->createPendingBill(
            amount: $amount,
            paymentMethod: $data['payment_type'] === 'paid' ? PaymentMethod::from($data['payment_method']) : null,
            originalAmount: (float) $plan->price_after_discount,
            type: BillType::NEW_SUBSCRIPTION,
            couponData: [
                'discount_amount' => 0,
                'coupon_id' => null,
                'coupon_code' => null,
                'coupon_value' => null,
                'coupon_type' => null,
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
