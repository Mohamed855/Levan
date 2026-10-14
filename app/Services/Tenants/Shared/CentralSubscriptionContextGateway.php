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
}
