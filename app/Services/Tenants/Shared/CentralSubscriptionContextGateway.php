<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Tenants\Tenant;
use Stancl\Tenancy\Facades\Tenancy;

final class CentralSubscriptionContextGateway
{
    /** @return array{plan_name:string,subscription_status:string,next_payment_date:mixed,total_seats:int} */
    public function current(): array
    {
        $tenantId = (string) tenant('id');

        return Tenancy::central(function () use ($tenantId): array {
            $subscription = Tenant::query()
                ->with([
                    'lastSubscription.plan',
                    'lastSubscription.currentPeriod',
                    'lastSubscription.queuedPeriod',
                    'lastSubscription.periods',
                ])
                ->find($tenantId)?->lastSubscription;

            $status = $subscription?->status;
            $currentPeriod = $subscription?->currentPeriod;
            $queuedPeriod = $subscription?->queuedPeriod
                ?? $subscription?->periods->where('status', SubscriptionPeriodStatus::QUEUED)->sortBy('period_start')->first();

            return [
                'plan_name' => $subscription?->is_trial && ! $subscription->plan_id ? 'تجربة مجانية — بدون باقة' : ($subscription?->plan?->name ?? 'غير محدد'),
                'subscription_status' => $status instanceof SubscriptionStatus
                    ? $status->label()
                    : ($status ? (string) $status : 'غير نشط'),
                'subscription_status_value' => $status instanceof SubscriptionStatus ? $status->value : (string) $status,
                'next_payment_date' => $subscription?->next_payment_date,
                'total_seats' => (int) ($subscription?->total_users ?? 0),
                'starts_at' => $subscription?->starts_at,
                'current_period_start' => $currentPeriod?->period_start ?? $subscription?->starts_at,
                'current_period_end' => $currentPeriod?->period_end ?? $subscription?->ends_at,
                'has_queued_period' => (bool) $queuedPeriod,
                'queued_period_start' => $queuedPeriod?->period_start,
                'queued_period_end' => $queuedPeriod?->period_end,
                'queued_plan_name' => $queuedPeriod?->plan_name_snapshot ?? $subscription?->plan?->name,
                'periods_count' => $subscription?->periods->count() ?? 0,
            ];
        });
    }

    /**
     * @return array{
     *     is_unlimited: bool,
     *     user_limit: ?int,
     *     plan_name: string
     * }
     */
    public function getUserLimitInfo(): array
    {
        $tenant = tenant();
        if (! $tenant) {
            return [
                'is_unlimited' => false,
                'user_limit' => 1,
                'plan_name' => 'غير محدد',
            ];
        }

        $tenantId = (string) $tenant->id;

        return Tenancy::central(function () use ($tenantId): array {
            $tenant = Tenant::query()->with('lastSubscription.plan')->find($tenantId);
            $subscription = $tenant?->lastSubscription;
            $plan = $subscription?->plan;

            if (! $subscription || ! $plan) {
                return [
                    'is_unlimited' => false,
                    'user_limit' => 1,
                    'plan_name' => 'غير محدد',
                ];
            }

            if ($plan->hasUnlimitedUsers()) {
                return [
                    'is_unlimited' => true,
                    'user_limit' => null,
                    'plan_name' => $plan->name,
                ];
            }

            $limit = $plan->included_users !== null ? (int) $plan->included_users : (int) ($subscription->total_users ?? 1);

            return [
                'is_unlimited' => false,
                'user_limit' => max(1, $limit),
                'plan_name' => $plan->name,
            ];
        });
    }

    public function canAddUser(int $currentUsersCount): bool
    {
        $info = $this->getUserLimitInfo();

        if ($info['is_unlimited']) {
            return true;
        }

        return $currentUsersCount < ($info['user_limit'] ?? 1);
    }

    /**
     * @return array{
     *     is_unlimited: bool,
     *     limit: ?int,
     *     plan_name: string
     * }
     */
    public function getPropertyLimitInfo(): array
    {
        $tenant = tenant();
        if (! $tenant) {
            return [
                'is_unlimited' => false,
                'limit' => 0,
                'plan_name' => 'غير محدد',
            ];
        }

        $tenantId = (string) $tenant->id;

        return Tenancy::central(function () use ($tenantId): array {
            $tenant = Tenant::query()->with('lastSubscription.plan')->find($tenantId);
            $subscription = $tenant?->lastSubscription;
            $plan = $subscription?->plan;

            if (! $subscription) {
                return [
                    'is_unlimited' => false,
                    'limit' => 0,
                    'plan_name' => 'غير محدد',
                ];
            }

            if ($subscription->is_trial && ! $subscription->plan_id) {
                return [
                    'is_unlimited' => false,
                    'limit' => (int) config('trial.limits.properties_limit', 1),
                    'plan_name' => 'تجربة مجانية',
                ];
            }

            if (! $plan) {
                return [
                    'is_unlimited' => false,
                    'limit' => 0,
                    'plan_name' => 'غير محدد',
                ];
            }

            if ($plan->isUnlimited()) {
                return [
                    'is_unlimited' => true,
                    'limit' => null,
                    'plan_name' => $plan->name,
                ];
            }

            return [
                'is_unlimited' => false,
                'limit' => $plan->properties_limit !== null ? (int) $plan->properties_limit : 50,
                'plan_name' => $plan->name,
            ];
        });
    }

    public function canCreateProperty(int $currentCount): bool
    {
        $info = $this->getPropertyLimitInfo();

        if ($info['is_unlimited']) {
            return true;
        }

        return $currentCount < ($info['limit'] ?? 0);
    }

    /**
     * @return array{
     *     is_unlimited: bool,
     *     limit: ?int,
     *     plan_name: string
     * }
     */
    public function getAuctionLimitInfo(): array
    {
        $tenant = tenant();
        if (! $tenant) {
            return [
                'is_unlimited' => false,
                'limit' => 0,
                'plan_name' => 'غير محدد',
            ];
        }

        $tenantId = (string) $tenant->id;

        return Tenancy::central(function () use ($tenantId): array {
            $tenant = Tenant::query()->with('lastSubscription.plan')->find($tenantId);
            $subscription = $tenant?->lastSubscription;
            $plan = $subscription?->plan;

            if (! $subscription) {
                return [
                    'is_unlimited' => false,
                    'limit' => 0,
                    'plan_name' => 'غير محدد',
                ];
            }

            if ($subscription->is_trial && ! $subscription->plan_id) {
                return [
                    'is_unlimited' => false,
                    'limit' => (int) config('trial.limits.active_auctions_limit', 1),
                    'plan_name' => 'تجربة مجانية',
                ];
            }

            if (! $plan) {
                return [
                    'is_unlimited' => false,
                    'limit' => 0,
                    'plan_name' => 'غير محدد',
                ];
            }

            if ($plan->isUnlimited()) {
                return [
                    'is_unlimited' => true,
                    'limit' => null,
                    'plan_name' => $plan->name,
                ];
            }

            return [
                'is_unlimited' => false,
                'limit' => $plan->active_auctions_limit !== null ? (int) $plan->active_auctions_limit : 10,
                'plan_name' => $plan->name,
            ];
        });
    }

    public function canCreateAuction(int $currentActiveCount): bool
    {
        $info = $this->getAuctionLimitInfo();

        if ($info['is_unlimited']) {
            return true;
        }

        return $currentActiveCount < ($info['limit'] ?? 0);
    }
}
