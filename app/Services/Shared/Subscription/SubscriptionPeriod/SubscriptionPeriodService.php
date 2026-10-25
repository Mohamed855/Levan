<?php

declare(strict_types=1);

namespace App\Services\shared\Subscription\SubscriptionPeriod;

use App\Enums\Admin\Plans\PlanLimitsMode;
use App\Enums\Admin\Plans\PlanType;
use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;

class SubscriptionPeriodService
{
    /**
     * Create a brand new subscription period with full snapshot captured from the plan and subscription.
     */
    public function createNewPeriod(
        Subscription $subscription,
        $startDate,
        $endDate,
        $status,
        bool $isCurrent,
        ?Plan $plan = null
    ): SubscriptionPeriod {
        $plan = $plan ?? $subscription->plan ?? ($subscription->plan_id ? Plan::find($subscription->plan_id) : null);

        $contractedCycle = $subscription->billing_cycle instanceof SubscriptionBillingCycle
            ? $subscription->billing_cycle->value
            : ($subscription->billing_cycle ?: ($plan?->subscription_billing_cycle instanceof SubscriptionBillingCycle
                ? $plan->subscription_billing_cycle->value
                : ($plan?->subscription_billing_cycle ?: 'monthly')));

        $limitsMode = $plan?->limits_mode instanceof PlanLimitsMode
            ? $plan->limits_mode->value
            : ($plan?->limits_mode ?: 'limited');

        $isUnlimited = $limitsMode === PlanLimitsMode::UNLIMITED->value;

        $limitsSnapshot = $isUnlimited ? null : [
            'included_users'        => (int) ($subscription->total_users ?: ($plan?->included_users ?: 1)),
            'properties_limit'      => (int) ($plan?->properties_limit ?: 50),
            'storage_quota_gb'      => (int) ($plan?->storage_quota_gb ?: 10),
            'active_auctions_limit' => (int) ($plan?->active_auctions_limit ?: 10),
        ];

        return SubscriptionPeriod::create([
            'subscription_id'        => $subscription->id,
            'period_start'           => $startDate,
            'period_end'             => $endDate,
            'plan_id_snapshot'       => $plan?->id ?? $subscription->plan_id,
            'plan_code_snapshot'     => $plan?->code ?? ('PLAN-' . ($plan?->id ?? 1)),
            'plan_name_snapshot'     => $plan?->name ?? 'Default Plan',
            'plan_type_snapshot'     => $plan?->plan_type instanceof PlanType ? $plan->plan_type->value : ($plan?->plan_type ?? 'basic'),
            'billing_cycle_snapshot' => $contractedCycle,
            'limits_mode_snapshot'   => $limitsMode,
            'limits_snapshot'        => $limitsSnapshot,
            'features_snapshot'      => $plan?->features ?? [],
            'price_snapshot'         => $subscription->price,
            'total_users_snapshot'   => $subscription->total_users,
            'used_users_snapshot'    => $subscription->used_users,
            'status'                 => $status,
            'is_current'             => $isCurrent,
        ]);
    }

    /**
     * Close the current active period and create the next renewal period by copying contract snapshots.
     */
    public function ClosePeriodAndcreateNewPeriod(
        Subscription $subscription,
        $startDate,
        $endDate,
        $status,
        bool $isCurrent
    ): SubscriptionPeriod {
        $previousPeriod = SubscriptionPeriod::where('subscription_id', $subscription->id)
            ->where('is_current', true)
            ->latest('id')
            ->first();

        if ($previousPeriod) {
            $previousPeriod->update([
                'is_current' => false,
                'status'     => SubscriptionPeriodStatus::ENDED,
            ]);
        }

        // If previous period had full snapshot, preserve its contract snapshot for historical continuity
        if ($previousPeriod && !empty($previousPeriod->plan_code_snapshot)) {
            return SubscriptionPeriod::create([
                'subscription_id'        => $subscription->id,
                'period_start'           => $startDate,
                'period_end'             => $endDate,
                'plan_id_snapshot'       => $previousPeriod->plan_id_snapshot,
                'plan_code_snapshot'     => $previousPeriod->plan_code_snapshot,
                'plan_name_snapshot'     => $previousPeriod->plan_name_snapshot,
                'plan_type_snapshot'     => $previousPeriod->plan_type_snapshot,
                'billing_cycle_snapshot' => $previousPeriod->billing_cycle_snapshot,
                'limits_mode_snapshot'   => $previousPeriod->limits_mode_snapshot,
                'limits_snapshot'        => $previousPeriod->limits_snapshot,
                'features_snapshot'      => $previousPeriod->features_snapshot,
                'price_snapshot'         => $subscription->price ?: $previousPeriod->price_snapshot,
                'total_users_snapshot'   => $subscription->total_users ?: $previousPeriod->total_users_snapshot,
                'used_users_snapshot'    => $subscription->used_users,
                'status'                 => $status,
                'is_current'             => $isCurrent,
            ]);
        }

        // Fallback: create fresh snapshot
        return $this->createNewPeriod($subscription, $startDate, $endDate, $status, $isCurrent);
    }
}
