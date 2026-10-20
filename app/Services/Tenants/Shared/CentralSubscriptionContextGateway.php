<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

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
            $subscription = Tenant::query()->with('lastSubscription.plan')->find($tenantId)?->lastSubscription;
            $status = $subscription?->status;

            return [
                'plan_name' => $subscription?->plan?->name ?? 'غير محدد',
                'subscription_status' => $status instanceof SubscriptionStatus
                    ? $status->label()
                    : ($status ? (string) $status : 'غير نشط'),
                'next_payment_date' => $subscription?->next_payment_date,
                'total_seats' => (int) ($subscription?->total_users ?? 0),
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

            if ($plan->isUnlimited()) {
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
}
