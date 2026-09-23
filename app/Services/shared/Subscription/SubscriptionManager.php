<?php

namespace App\Services\shared\Subscription;

use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Models\Admin\Tenants\Tenant;
use App\Services\shared\Subscription\SubscriptionPeriod\SubscriptionPeriodService;
use Carbon\Carbon;
use Stancl\Tenancy\Facades\Tenancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionManager
{
    public function __construct(private SubscriptionPeriodService $subscriptionPeriodService) {}

    // جلب وتنسيق بيانات الاشتراك
    public function getTenantSubscription(string $tenantId)
    {
        return Tenancy::central(fn() => Tenant::find($tenantId)?->lastSubscription);
    }

    //  إنشاء اشتراك جديد
    public function createSubscription(Tenant $tenant, Plan $plan, float  $paidAmount, SubscriptionStatus  $subscriptionStatus = SubscriptionStatus::ACTIVE, SubscriptionPeriodStatus $subscriptionPeriodStatus = SubscriptionPeriodStatus::ACTIVE, ?string $trialEndDate = null): Subscription
    {
        return Tenancy::central(function () use ($tenant, $plan, $paidAmount, $subscriptionStatus, $subscriptionPeriodStatus, $trialEndDate) {
            return DB::connection('mysql')->transaction(function () use ($tenant, $plan, $paidAmount, $subscriptionStatus, $subscriptionPeriodStatus, $trialEndDate) {
                $startDate  = now();

                $endDate = $trialEndDate ? Carbon::parse($trialEndDate) : $this->calculatePeriodEnd($startDate);

                // إنشاء الاشتراك
                $subscription = Subscription::create([
                    'tenant_id'         => $tenant->id,
                    'plan_id'           => $plan->id,

                    'price'             => $paidAmount,
                    'status'            => $subscriptionStatus,

                    'total_users'       => $plan->included_users,
                    'used_users'        => 1,

                    'starts_at'         => $startDate,
                    'next_payment_date' => $endDate,

                    'auto_renew'        => false,

                    'trial_end_date'    => $trialEndDate, //

                ]);

                $this->subscriptionPeriodService->createNewPeriod($subscription, $startDate, $endDate, $subscriptionPeriodStatus, true);

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
                $subscription   = Subscription::where('tenant_id', $tenant->id)->latest('id')->lockForUpdate()->firstOrFail();

                $anchorStart    = $this->computeNextPeriodStart($subscription);
                $isDueNow       = now()->greaterThanOrEqualTo($anchorStart);
                $newStart       = $anchorStart->copy();
                $newEnd         = $this->calculatePeriodEnd($newStart);

                //  تحديث الاشتراك
                $subscription->update([
                    'status'            => SubscriptionStatus::ACTIVE,
                    'price'             => $paidAmount,
                    'next_payment_date' => $newEnd,
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

                if (!$newPlan) {
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
                $endDate   = $this->calculatePeriodEnd($startDate);


                $subscription->update([
                    'status'            => SubscriptionStatus::ACTIVE,
                    'starts_at'         => $startDate,
                    'next_payment_date' => $endDate,
                ]);

                if ($subscription->currentPeriod) {
                    $subscription->currentPeriod->update([
                        'status'       => SubscriptionPeriodStatus::ACTIVE,
                        'is_current'   => true,
                        'period_start' => $startDate,
                        'period_end'   => $endDate,
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
        if (!$period || !$period->period_end) {
            return null;
        }

        $today = now();
        $endDate = $period->period_end->copy();

        // إذا تاريخ الانتهاء أقل من اليوم
        if ($endDate->lt($today)) {
            return 0; // منتهي
        }

        return $today->diffInDays($endDate, false) + 1;
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
        return  $startDate->copy()->addYear();
    }

    // مسح كاش الاشتراك
    private function forgetTenantSubscriptionCache(string $tenantId): void
    {
        cache()->forget("tenant:{$tenantId}:subscription");
    }
}