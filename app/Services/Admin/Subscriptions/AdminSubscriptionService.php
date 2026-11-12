<?php

declare(strict_types=1);

namespace App\Services\Admin\Subscriptions;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Licenses\LicenseSeat;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Models\Admin\Tenants\Tenant;
use App\Services\Shared\Bills\BillService;
use App\Services\Shared\Coupons\CouponService;
use App\Services\Shared\Subscription\SubscriptionPeriod\SubscriptionPeriodService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminSubscriptionService
{
    public function __construct(
        private readonly BillService $billService,
        private readonly SubscriptionPeriodService $subscriptionPeriodService,
        private readonly ?CouponService $couponService = null,
    ) {}

    public function calculatePeriodBasePrice(Plan $plan, ?int $periodMonths = null, ?string $billingCycle = null): float
    {
        if ($plan->isTrial()) {
            return 0.00;
        }

        $cycle = $billingCycle ?? ($plan->subscription_billing_cycle?->value ?? (string) $plan->subscription_billing_cycle ?: 'monthly');
        $cycleMonths = match ($cycle) {
            'quarterly' => 3,
            'yearly' => 12,
            default => 1,
        };

        $months = ($periodMonths !== null && $periodMonths > 0) ? $periodMonths : $cycleMonths;
        $unitPrice = (float) ($plan->price_after_discount ?? $plan->price ?? 0);

        return max(0.00, round($unitPrice * ($months / $cycleMonths), 2));
    }

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
            ->where(function ($query) {
                $query->whereHas('currentPeriod', function ($q) {
                    $q->where('status', SubscriptionPeriodStatus::ACTIVE)
                        ->where('period_end', '<=', now()->addDays(30))
                        ->where('period_end', '>=', now()->startOfDay());
                })->orWhere(function ($q) {
                    $q->whereDoesntHave('periods')
                        ->where('ends_at', '<=', now()->addDays(30))
                        ->where('ends_at', '>=', now()->startOfDay());
                });
            })
            ->count();
    }

    public function createSubscription(Tenant $company, array $data): Subscription
    {
        // Rule: Can only create a new subscription if last subscription is expired (or no subscription exists)
        $lastSubscription = $company->lastSubscription;
        if ($lastSubscription && $lastSubscription->status !== SubscriptionStatus::EXPIRED) {
            throw ValidationException::withMessages([
                'company_id' => 'لا يمكن إنشاء اشتراك جديد للشركة إلا إذا كان آخر اشتراك لها منتهياً (expired).',
            ]);
        }

        return DB::connection('mysql')->transaction(function () use ($company, $data) {
            $plan = Plan::findOrFail($data['plan_id']);

            $billingCycle = $data['billing_cycle'] ?? $plan->subscription_billing_cycle?->value ?? (string) $plan->subscription_billing_cycle ?: 'monthly';
            $periodMonths = ! empty($data['period_months']) ? (int) $data['period_months'] : null;
            $startDate = ! empty($data['start_date']) ? Carbon::parse($data['start_date']) : now();
            $endDate = ! empty($data['end_date'])
                ? Carbon::parse($data['end_date'])
                : (! empty($data['trial_end_date'])
                    ? Carbon::parse($data['trial_end_date'])
                    : ($periodMonths && ! $plan->isTrial()
                        ? $startDate->copy()->addMonthsNoOverflow($periodMonths)
                        : $this->calculatePeriodEndForPlan($startDate, $plan, $billingCycle)));

            $paymentStatusInput = $data['payment_status'] ?? null;
            $paymentType = $data['payment_type'] ?? $paymentStatusInput ?? 'paid';
            $isTrial = $paymentType === 'trial'
                || $paymentStatusInput === 'trial'
                || $plan->isTrial();
            $isPendingPayment = ! $isTrial
                && in_array($paymentStatusInput ?? $paymentType, ['pending', 'unpaid'], true);

            if ($isTrial) {
                $subStatus = SubscriptionStatus::TRIAL;
                $periodStatus = SubscriptionPeriodStatus::ACTIVE;
            } elseif ($isPendingPayment) {
                $subStatus = SubscriptionStatus::SUSPENDED;
                $periodStatus = SubscriptionPeriodStatus::PENDING_PAYMENT;
            } else {
                $subStatus = SubscriptionStatus::ACTIVE;
                $periodStatus = SubscriptionPeriodStatus::ACTIVE;
            }

            $basePrice = $this->calculatePeriodBasePrice($plan, $periodMonths, $billingCycle);
            $couponService = $this->couponService ?? app(CouponService::class);
            $couponCode = trim((string) ($data['coupon_code'] ?? ''));
            $appliedCoupon = null;
            $couponData = [
                'discount_amount' => 0.0,
                'coupon_id' => null,
                'coupon_code' => null,
                'coupon_value' => null,
                'coupon_type' => null,
            ];

            if (! $isTrial && $couponCode !== '') {
                $couponResult = $couponService->processCoupon($couponCode, $basePrice);
                if (isset($couponResult['error'])) {
                    throw ValidationException::withMessages([
                        'coupon_code' => $couponResult['error'],
                    ]);
                }

                $couponData = [
                    'discount_amount' => (float) ($couponResult['discount_amount'] ?? 0),
                    'coupon_id' => $couponResult['coupon_id'] ?? null,
                    'coupon_code' => $couponResult['coupon_code'] ?? $couponCode,
                    'coupon_value' => $couponResult['coupon_value'] ?? null,
                    'coupon_type' => $couponResult['coupon_type'] ?? null,
                ];

                if (! empty($couponData['coupon_id'])) {
                    $appliedCoupon = Coupon::find($couponData['coupon_id']);
                }
            }

            $manualDiscount = (! $isTrial && ($data['has_discount'] ?? 'no') === 'yes')
                ? (float) ($data['discount_value'] ?? 0)
                : 0.0;
            $discount = min($basePrice, (float) $couponData['discount_amount'] + $manualDiscount);
            $couponData['discount_amount'] = round($discount, 2);
            $amount = $isTrial ? 0.00 : max(0.00, round($basePrice - $discount, 2));

            $subscription = Subscription::create([
                'tenant_id' => $company->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'price' => $amount,
                'status' => $subStatus,
                'total_users' => $plan->included_users,
                'used_users' => 0,
                'starts_at' => $startDate,
                'ends_at' => $endDate,
                'next_payment_date' => $endDate,
                'auto_renew' => false,
                'trial_end_date' => ! empty($data['trial_end_date']) ? $data['trial_end_date'] : ($plan->isTrial() ? $endDate->toDateString() : null),
            ]);

            // Handle payment proof / receipt file if uploaded
            if (isset($data['payment_proof']) && $data['payment_proof'] instanceof UploadedFile) {
                $receiptPath = $data['payment_proof']->store('receipts', 'public');
                $subscription->update(['transfer_bank_attachment' => $receiptPath]);
            } elseif (isset($data['receipt_upload']) && $data['receipt_upload'] instanceof UploadedFile) {
                $receiptPath = $data['receipt_upload']->store('receipts', 'public');
                $subscription->update(['transfer_bank_attachment' => $receiptPath]);
            }

            // Create subscription period with full snapshots
            $this->subscriptionPeriodService->createNewPeriod($subscription, $startDate, $endDate, $periodStatus, true, $plan);

            // Handle bill
            $paymentMethod = null;
            $rawMethod = $data['payment_method'] ?? null;
            if (! empty($rawMethod)) {
                $paymentMethod = PaymentMethod::tryFrom($rawMethod);
            }

            $bill = $this->billService->createPendingBill(
                amount: $amount,
                paymentMethod: $paymentMethod,
                originalAmount: $basePrice,
                type: BillType::NEW_SUBSCRIPTION,
                couponData: $couponData,
                metadata: array_filter([
                    'tenant_id' => $company->id,
                    'subscription_id' => $subscription->id,
                    'plan_id' => $plan->id,
                    'period_months' => $periodMonths,
                    'transaction_id' => ! empty($data['transaction_id']) ? (string) $data['transaction_id'] : null,
                    'admin_notes' => ! empty($data['admin_notes']) ? (string) $data['admin_notes'] : null,
                ], fn ($value): bool => $value !== null)
            );

            $billStatus = $isTrial
                ? BillStatus::TRIAL
                : ($isPendingPayment ? BillStatus::UNPAID : BillStatus::PAID);

            if ($bill) {
                $billUpdates = [
                    'tenant_id' => $company->id,
                    'subscription_id' => $subscription->id,
                    'status' => $billStatus,
                    'paid_at' => $billStatus === BillStatus::PAID ? now() : null,
                ];

                if (! empty($data['admin_notes'])) {
                    $billUpdates['transfer_notes'] = (string) $data['admin_notes'];
                }

                $bill->update($billUpdates);
            }

            if ($appliedCoupon) {
                $couponService->applyCoupon($appliedCoupon);
            }

            // Provision license seats for the plan quota
            $totalSeats = (int) ($subscription->total_users ?: $plan->included_users ?: 1);
            for ($i = 0; $i < $totalSeats; $i++) {
                LicenseSeat::create([
                    'id' => (string) Str::uuid(),
                    'tenant_id' => $company->id,
                    'subscription_id' => $subscription->id,
                    'status' => 'free',
                    'is_core' => $i === 0,
                ]);
            }

            $this->clearSubscriptionCache($company->id);

            return $subscription;
        });
    }

    public function toggleStatus(Subscription $subscription): Subscription
    {
        if (! in_array($subscription->status, [SubscriptionStatus::ACTIVE, SubscriptionStatus::SUSPENDED], true)) {
            throw new \DomainException('لا يمكن تبديل حالة اشتراك منتهي أو ملغي أو قيد المراجعة.');
        }

        $newStatus = $subscription->status === SubscriptionStatus::ACTIVE
            ? SubscriptionStatus::SUSPENDED
            : SubscriptionStatus::ACTIVE;

        if ($newStatus === SubscriptionStatus::ACTIVE) {
            $period = $subscription->currentPeriod ?? $subscription->periods()->latest('period_end')->first();
            if ($period && $period->status === SubscriptionPeriodStatus::PENDING_PAYMENT) {
                throw new \DomainException('لا يمكن تفعيل الاشتراك مباشرة لوجود فترة معلقة بانتظار الدفع. يرجى تفعيل الاشتراك من صفحة إدارة الاشتراكات بعد التحقق من سداد الفاتورة.');
            }
        }

        $subscription->update(['status' => $newStatus]);

        $this->clearSubscriptionCache($subscription->tenant_id);

        return $subscription;
    }

    public function deleteSubscription(Subscription $subscription): bool
    {
        $tenantId = $subscription->tenant_id;

        return DB::connection('mysql')->transaction(function () use ($subscription, $tenantId) {
            // Nullify subscription_id on associated bills
            Bill::where('subscription_id', $subscription->id)->update(['subscription_id' => null]);

            // Delete license seats and periods
            LicenseSeat::where('subscription_id', $subscription->id)->delete();
            SubscriptionPeriod::where('subscription_id', $subscription->id)->delete();

            // Permanently hard-delete the subscription
            $deleted = (bool) $subscription->delete();

            $this->clearSubscriptionCache($tenantId);

            return $deleted;
        });
    }

    public function clearSubscriptionCache(string $tenantId): void
    {
        Cache::forget("tenant:{$tenantId}:subscription");
        Cache::forget('admin:expiring_subscriptions_count');
        Cache::forget('admin:expired_subscriptions_count');
    }

    private function calculatePeriodEndForPlan(Carbon $startDate, Plan $plan, ?string $billingCycle = null): Carbon
    {
        if ($plan->isTrial()) {
            return $startDate->copy()->addDays((int) config('trial.days', 7));
        }

        $cycle = $billingCycle ?? ($plan->subscription_billing_cycle?->value ?? (string) $plan->subscription_billing_cycle);

        if ($cycle === 'monthly') {
            return $startDate->copy()->addMonth();
        }

        if ($cycle === 'quarterly') {
            return $startDate->copy()->addMonths(3);
        }

        return $startDate->copy()->addYear();
    }
}
