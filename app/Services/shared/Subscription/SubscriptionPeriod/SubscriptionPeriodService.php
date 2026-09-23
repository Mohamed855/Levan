<?php

namespace App\Services\shared\Subscription\SubscriptionPeriod;

use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;

class SubscriptionPeriodService
{
    public function createNewPeriod(Subscription $subscription, $startDate, $endDate, $status, $isCurrent): SubscriptionPeriod
    {
        return SubscriptionPeriod::create([
            'subscription_id'       => $subscription->id,

            'period_start'          => $startDate,
            'period_end'            => $endDate,

            'plan_id_snapshot'      => $subscription->plan_id,
            'price_snapshot'        => $subscription->price,
            'total_users_snapshot'  => $subscription->total_users,
            'used_users_snapshot'   => $subscription->used_users,

            'status'                => $status,
            'is_current'            => $isCurrent,
        ]);
    }


    public function ClosePeriodAndcreateNewPeriod(Subscription $subscription, $startDate, $endDate, $status, $isCurrent): SubscriptionPeriod
    {
        SubscriptionPeriod::where('subscription_id', $subscription->id)->where('is_current', true)
            ->update([
                'is_current' => false,
                'status'     => SubscriptionPeriodStatus::ENDED
            ]);

        return SubscriptionPeriod::create([
            'subscription_id'       => $subscription->id,

            'period_start'          => $startDate,
            'period_end'            => $endDate,

            'plan_id_snapshot'      => $subscription->plan_id,
            'price_snapshot'        => $subscription->price,
            'total_users_snapshot'  => $subscription->total_users,
            'used_users_snapshot'   => $subscription->used_users,

            'status'                => $status,
            'is_current'            => $isCurrent,
        ]);
    }
}
