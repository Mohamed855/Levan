<?php

declare(strict_types=1);

namespace App\Services\shared\Subscription;

use App\Enums\Admin\Plans\PlanLimitsMode;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Models\Admin\Tenants\Tenant;
use Stancl\Tenancy\Facades\Tenancy;

class SubscriptionLimitsResolver
{
    /**
     * Resolve the current period limits data for a given tenant.
     *
     * @param string|Tenant $tenant
     * @return array{
     *     is_unlimited: bool,
     *     limits_mode: string,
     *     included_users: ?int,
     *     properties_limit: ?int,
     *     storage_quota_gb: ?int,
     *     active_auctions_limit: ?int,
     *     plan_name: string
     * }
     */
    public function resolveForTenant(string|Tenant $tenant): array
    {
        $tenantId = $tenant instanceof Tenant ? (string) $tenant->id : (string) $tenant;

        return Tenancy::central(function () use ($tenantId) {
            $subscription = Subscription::where('tenant_id', $tenantId)
                ->where('status', SubscriptionStatus::ACTIVE)
                ->with(['currentPeriod', 'plan'])
                ->latest('id')
                ->first();

            if (!$subscription) {
                $subscription = Subscription::where('tenant_id', $tenantId)
                    ->with(['currentPeriod', 'plan'])
                    ->latest('id')
                    ->first();
            }

            if (!$subscription) {
                return [
                    'is_unlimited'          => false,
                    'limits_mode'           => PlanLimitsMode::LIMITED->value,
                    'included_users'        => 0,
                    'properties_limit'      => 0,
                    'storage_quota_gb'      => 0,
                    'active_auctions_limit' => 0,
                    'plan_name'             => '-',
                ];
            }

            $currentPeriod = $subscription->currentPeriod;
            $plan = $subscription->plan;

            // 1. Try resolving from snapshot in current period
            if ($currentPeriod && !empty($currentPeriod->limits_mode_snapshot)) {
                $isUnlimited = (string) $currentPeriod->limits_mode_snapshot === PlanLimitsMode::UNLIMITED->value;
                $snapshot = $currentPeriod->limits_snapshot ?? [];

                if ($isUnlimited) {
                    return [
                        'is_unlimited'          => true,
                        'limits_mode'           => PlanLimitsMode::UNLIMITED->value,
                        'included_users'        => null,
                        'properties_limit'      => null,
                        'storage_quota_gb'      => null,
                        'active_auctions_limit' => null,
                        'plan_name'             => (string) ($currentPeriod->plan_name_snapshot ?? $plan?->name ?? 'Plan'),
                    ];
                }

                return [
                    'is_unlimited'          => false,
                    'limits_mode'           => PlanLimitsMode::LIMITED->value,
                    'included_users'        => isset($snapshot['included_users']) ? (int) $snapshot['included_users'] : (int) ($currentPeriod->total_users_snapshot ?: ($plan?->included_users ?: 1)),
                    'properties_limit'      => isset($snapshot['properties_limit']) ? (int) $snapshot['properties_limit'] : (int) ($plan?->properties_limit ?: 50),
                    'storage_quota_gb'      => isset($snapshot['storage_quota_gb']) ? (int) $snapshot['storage_quota_gb'] : (int) ($plan?->storage_quota_gb ?: 10),
                    'active_auctions_limit' => isset($snapshot['active_auctions_limit']) ? (int) $snapshot['active_auctions_limit'] : (int) ($plan?->active_auctions_limit ?: 10),
                    'plan_name'             => (string) ($currentPeriod->plan_name_snapshot ?? $plan?->name ?? 'Plan'),
                ];
            }

            // 2. Fallback to plan directly if snapshot is missing
            $isUnlimited = $plan?->isUnlimited() ?? false;
            if ($isUnlimited) {
                return [
                    'is_unlimited'          => true,
                    'limits_mode'           => PlanLimitsMode::UNLIMITED->value,
                    'included_users'        => null,
                    'properties_limit'      => null,
                    'storage_quota_gb'      => null,
                    'active_auctions_limit' => null,
                    'plan_name'             => (string) ($plan?->name ?? 'Plan'),
                ];
            }

            return [
                'is_unlimited'          => false,
                'limits_mode'           => PlanLimitsMode::LIMITED->value,
                'included_users'        => (int) ($plan?->included_users ?: 1),
                'properties_limit'      => (int) ($plan?->properties_limit ?: 50),
                'storage_quota_gb'      => (int) ($plan?->storage_quota_gb ?: 10),
                'active_auctions_limit' => (int) ($plan?->active_auctions_limit ?: 10),
                'plan_name'             => (string) ($plan?->name ?? 'Plan'),
            ];
        });
    }

    public function isUnlimited(string|Tenant $tenant): bool
    {
        return $this->resolveForTenant($tenant)['is_unlimited'];
    }

}
