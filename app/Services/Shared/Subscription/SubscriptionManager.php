<?php

namespace App\Services\shared\Subscription;

use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Models\Admin\Tenants\Tenant;
use App\Services\shared\Subscription\SubscriptionPeriod\SubscriptionPeriodService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Facades\Tenancy;

class SubscriptionManager
{
    public function __construct(private SubscriptionPeriodService $subscriptionPeriodService) {}

    public function createTrialSubscription(Tenant $tenant): Subscription
    {
        return Tenancy::central(function () use ($tenant): Subscription {
            return DB::connection('mysql')->transaction(function () use ($tenant): Subscription {
                $existing = Subscription::where('tenant_id', $tenant->id)->whereNull('plan_id')->where('status', SubscriptionStatus::TRIAL)->first();
                if ($existing) {
                    return $existing;
                }
                $start = now();
                $end = $start->copy()->addDays((int) config('trial.days', 7));
                $subscription = Subscription::create([
                    'tenant_id' => $tenant->id, 'plan_id' => null, 'billing_cycle' => 'monthly',
                    'price' => 0, 'status' => SubscriptionStatus::TRIAL, 'total_users' => 1, 'used_users' => 1,
                    'starts_at' => $start, 'ends_at' => $end, 'trial_end_date' => $end->toDateString(),
                    'next_payment_date' => null, 'auto_renew' => false,
                ]);
                $period = $this->subscriptionPeriodService->createNewPeriod($subscription, $start, $end, SubscriptionPeriodStatus::TRIAL, true);
                $period->update([
                    'plan_id_snapshot' => null, 'plan_code_snapshot' => 'trial',
                    'plan_name_snapshot' => 'تجربة مجانية لمدة 7 أيام', 'limits_mode_snapshot' => 'limited',
                    'limits_snapshot' => array_merge(config('trial.limits'), ['included_users' => 1]),
                ]);

                return $subscription->load('currentPeriod');
            });
        });
    }

    // جلب وتنسيق بيانات الاشتراك
    public function getTenantSubscription(string $tenantId)
    {
        return Tenancy::central(fn () => Tenant::find($tenantId)?->lastSubscription);
    }

    //  إنشاء اشتراك جديد
    public function createSubscription(Tenant $tenant, Plan $plan, float $paidAmount, SubscriptionStatus $subscriptionStatus = SubscriptionStatus::ACTIVE, SubscriptionPeriodStatus $subscriptionPeriodStatus = SubscriptionPeriodStatus::ACTIVE, ?string $trialEndDate = null, array $options = []): Subscription
    {
        return Tenancy::central(function () use ($tenant, $plan, $paidAmount, $subscriptionStatus, $subscriptionPeriodStatus, $trialEndDate, $options) {
            return DB::connection('mysql')->transaction(function () use ($tenant, $plan, $paidAmount, $subscriptionStatus, $subscriptionPeriodStatus, $trialEndDate, $options) {
                $billingCycle = $options['billing_cycle'] ?? $plan->subscription_billing_cycle?->value ?? (string) $plan->subscription_billing_cycle ?: 'monthly';
                $startDate = isset($options['start_date']) ? Carbon::parse($options['start_date']) : now();
                $endDate = isset($options['end_date']) ? Carbon::parse($options['end_date']) : ($trialEndDate ? Carbon::parse($trialEndDate) : $this->calculatePeriodEndForPlan($startDate, $plan, $billingCycle));

                // إنشاء الاشتراك
                $subscription = Subscription::create([
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'billing_cycle' => $billingCycle,

                    'price' => $paidAmount,
                    'status' => $subscriptionStatus,

                    'total_users' => $options['total_users'] ?? $plan->included_users,
                    'used_users' => 1,

                    'starts_at' => $startDate,
                    'ends_at' => $endDate,
                    'next_payment_date' => $endDate,

                    'auto_renew' => false,

                    'trial_end_date' => $trialEndDate, //

                ]);

                $this->subscriptionPeriodService->createNewPeriod($subscription, $startDate, $endDate, $subscriptionPeriodStatus, true, $plan);

                $subscription->load(['plan', 'currentPeriod']);

                return $subscription;
            });
        });
    }

    // التجديد
    public function renewSubscription(Tenant $tenant, float $paidAmount)
    {
        return Tenancy::central(function () use ($tenant, $paidAmount) {
            return DB::connection('mysql')->transaction(function () use ($tenant, $paidAmount) {
                $subscription = Subscription::where('tenant_id', $tenant->id)->latest('id')->lockForUpdate()->firstOrFail();

                $anchorStart = $this->computeNextPeriodStart($subscription);
                $isDueNow = now()->greaterThanOrEqualTo($anchorStart);
                $newStart = $anchorStart->copy();
                $billingCycle = $subscription->billing_cycle?->value ?? (string) $subscription->billing_cycle;
                $newEnd = $this->calculatePeriodEndForPlan($newStart, $subscription->plan, $billingCycle);

                //  تحديث الاشتراك
                $subscription->update([
                    'status' => SubscriptionStatus::ACTIVE,
                    'price' => $paidAmount,
                    'next_payment_date' => $newEnd,
                    'ends_at' => $newEnd,
                ]);

                //  إنشاء أو تحديث الفترة
                $isDueNow
                    ? $this->subscriptionPeriodService->ClosePeriodAndcreateNewPeriod($subscription, $newStart, $newEnd, SubscriptionPeriodStatus::ACTIVE, true)
                    : $this->subscriptionPeriodService->createNewPeriod($subscription, $newStart, $newEnd, SubscriptionPeriodStatus::QUEUED, false);

                $this->forgetTenantSubscriptionCache($tenant->id);

                return [$subscription->load(['currentPeriod'])];
            });
        });
    }

    // شراء تراخيص اضافية
    public function upgradeSubscription(Tenant $tenant, int $NewlicensesCount)
    {
        Log::info($NewlicensesCount);

        return Tenancy::central(function () use ($tenant, $NewlicensesCount) {
            return DB::connection('mysql')->transaction(function () use ($tenant, $NewlicensesCount) {
                $subscription = Subscription::where('tenant_id', $tenant->id)->latest('id')->lockForUpdate()->with(['plan'])->firstOrFail();
                if ($subscription->is_trial && $subscription->plan_id === null) {
                    throw ValidationException::withMessages(['licenses' => 'لا يمكن إضافة مستخدمين أثناء التجربة. يرجى اختيار اشتراك مدفوع أولًا.']);
                }

                $oldTotalUsers = $subscription->total_users;            // العدد القديم
                $newTotalUsers = $oldTotalUsers + $NewlicensesCount;    // العدد الجديد

                Log::info($newTotalUsers);

                $plans = Plan::where('is_active', true)->orderBy('included_users', 'asc')->get();

                $newPlan = null;

                foreach ($plans as $plan) {
                    if ($newTotalUsers <= $plan->included_users) {
                        $newPlan = $plan;
                        break;
                    }
                }

                if (! $newPlan) {
                    $newPlan = $plans->last(); // لا يوجد باقة مناسبة ناخذ اخر باقة
                }

                $updateData = [
                    'total_users' => $newTotalUsers,
                ];

                // إذا تغيرت الباقة، نحدثها
                if ($newPlan && $newPlan->id !== $subscription->plan_id) {
                    $updateData['plan_id'] = $newPlan->id;
                }

                $subscription->update($updateData);

                $this->forgetTenantSubscriptionCache($tenant->id);

                return $subscription->load(['currentPeriod', 'plan']);
            });
        });
    }

    // بعد دفع فاتورة معلقة
    public function changeSubscriptionToActive(Tenant $tenant)
    {
        return Tenancy::central(function () use ($tenant) {
            return DB::connection('mysql')->transaction(function () use ($tenant) {
                $subscription = Subscription::where('tenant_id', $tenant->id)->latest('id')->lockForUpdate()->firstOrFail();

                $startDate = now();
                $billingCycle = $subscription->billing_cycle?->value ?? (string) $subscription->billing_cycle;
                $endDate = $this->calculatePeriodEndForPlan($startDate, $subscription->plan, $billingCycle);

                $subscription->update([
                    'status' => SubscriptionStatus::ACTIVE,
                    'starts_at' => $startDate,
                    'ends_at' => $endDate,
                    'next_payment_date' => $endDate,
                ]);

                if ($subscription->currentPeriod) {
                    $subscription->currentPeriod->update([
                        'status' => SubscriptionPeriodStatus::ACTIVE,
                        'is_current' => true,
                        'period_start' => $startDate,
                        'period_end' => $endDate,
                    ]);
                }

                $this->forgetTenantSubscriptionCache($tenant->id);

                return $subscription->fresh(['currentPeriod']);
            });
        });
    }

    // public function deleteSubscription($tenant)
    // {
    //     return Tenancy::central(function () use ($tenant) {
    //         return DB::connection('mysql')->transaction(function () use ($tenant) {
    //             $subscription = Subscription::where('tenant_id', $tenant->id)->latest('id')->lockForUpdate()->firstOrFail();

    //             $subscription->forceDelete();

    //             $this->forgetTenantSubscriptionCache($tenant->id);
    //         } );
    //     } );
    // }

    // TODO
    // يجب ان تنقل من هنا
    public function calculateDaysRemaining(?SubscriptionPeriod $period): ?int
    {
        if (! $period || ! $period->period_end) {
            return null;
        }

        $today = now();
        $endDate = $period->period_end->copy();

        // إذا تاريخ الانتهاء أقل من اليوم
        if ($endDate->lt($today)) {
            return 0; // منتهي
        }

        return (int) $today->diffInDays($endDate, false) + 1;
    }

    /*
    |============================================================================
    |============================================================================
    |                           Private Methods
    |============================================================================
    |============================================================================
    */
    private function computeNextPeriodStart(Subscription $subscription): Carbon
    {
        $lastPeriodEnd = SubscriptionPeriod::where('subscription_id', $subscription->id)->max('period_end');

        $now = now();

        if ($lastPeriodEnd) {
            $lastEnd = Carbon::parse($lastPeriodEnd);

            return $lastEnd->greaterThan($now) ? $lastEnd : $now;
        }

        return $now;
    }

    // حساب نهاية الفترة
    private function calculatePeriodEnd(Carbon $startDate): Carbon
    {
        return $startDate->copy()->addYear();
    }

    private function calculatePeriodEndForPlan(Carbon $startDate, Plan $plan, ?string $billingCycle = null): Carbon
    {
        if ($plan->isTrial()) {
            return $startDate->copy()->addDays((int) config('trial.days', 7));
        }

        $cycle = $billingCycle ?? $plan->subscription_billing_cycle?->value ?? (string) $plan->subscription_billing_cycle;

        return match ($cycle) {
            'monthly' => $startDate->copy()->addMonth(),
            'quarterly' => $startDate->copy()->addMonths(3),
            default => $startDate->copy()->addYear(),
        };
    }

    // مسح كاش الاشتراك
    private function forgetTenantSubscriptionCache(string $tenantId): void
    {
        cache()->forget("tenant:{$tenantId}:subscription");
    }
}
