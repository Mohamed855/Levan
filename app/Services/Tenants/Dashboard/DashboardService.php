<?php

declare(strict_types=1);

namespace App\Services\Tenants\Dashboard;

use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Shared\CentralSubscriptionContextGateway;

class DashboardService
{
    public function __construct(private readonly CentralSubscriptionContextGateway $subscriptionGateway) {}

    /** @return array{stats:array<string,mixed>,tenant:mixed,subscription:null} */
    public function getFoundationDashboardData(): array
    {
        $subscription = $this->subscriptionGateway->current();

        return [
            'stats' => [
                'total_users' => User::query()->count(),
                'active_users' => User::query()->where('status', 'active')->count(),
                ...$subscription,
            ],
            'tenant' => tenant(),
            'subscription' => null,
        ];
    }
}
