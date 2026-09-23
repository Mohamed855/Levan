<?php

namespace App\Services\shared\Subscription;

use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use Stancl\Tenancy\Facades\Tenancy;

class SubscriptionFormatter
{
    public function format(?Subscription $subscription): array
    {
        if (!$subscription) {
            return $this->empty();
        }

        $currentPeriod  = $subscription->currentPeriod;
        $lastPaidPeriod = $this->getLastPaidPeriod($subscription);

        return [
            'plan_name'                => $subscription->plan?->name ?? 'غير محدد',
            'status'                   => $subscription->status->label(),
            'status_color'             => $subscription->status->color(),
            // 'paid_period_start'        => $lastPaidPeriod?->period_start?->format('Y/m/d') ?? $currentPeriod?->period_start?->format('Y/m/d'),
            'paid_period_end'          => $lastPaidPeriod?->period_end?->format('Y/m/d') ?? $currentPeriod?->period_end?->format('Y/m/d'),
            'paid_days_remaining'      => $this->calculateDaysRemaining($lastPaidPeriod ?? $currentPeriod),
            'total_users'              => $subscription->total_users ?? 0,
            'used_users'               => $subscription->used_users ?? 0,

            'subscription_number'      => $subscription->subscription_number,
            'next_payment_date'        => $subscription->next_payment_date?->format('Y/m/d') ?? 'غير محدد',
            'yearly_renewal_price'     => number_format($subscription->plan?->yearly_renewal_price, 2),
            'auto_renew'               => $subscription->auto_renew,
            'status_value'             => $subscription->status->value,
            'extra_user_license_price' => number_format($subscription->plan?->extra_user_license_price, 2),
        ];
    }

    private function getLastPaidPeriod(Subscription $subscription): ?SubscriptionPeriod
    {
        return Tenancy::central(
            fn() =>
            SubscriptionPeriod::where('subscription_id', $subscription->id)
                ->where('status', SubscriptionPeriodStatus::QUEUED)
                ->orderBy('period_end', 'desc')->first()
        );
    }

    private function calculateDaysRemaining(?SubscriptionPeriod $period): ?int
    {
        if (!$period || !$period->period_end) return null;

        $endDate = $period->period_end->copy();

        return $endDate->lt(now()) ? 0 : now()->diffInDays($endDate, false) + 1;
    }

    private function empty(): array
    {
        return [
            'plan_name'                => 'غير محدد',
            'status'                   => 'غير محدد',
            'status_color'             => 'secondary',
            // 'paid_period_start'        => 'غير محدد',
            'paid_period_end'          => 'غير محدد',
            'paid_days_remaining'      => 'غير محدد',
            'total_users'              => 0,
            'used_users'               => 0,
            'subscription_number'      => 'غير محدد',
            'next_payment_date'        => 'غير محدد',
            'yearly_renewal_price'     => 0,
            'auto_renew'               => 'غير محدد',
            'status_value'             => 'غير محدد',
            'extra_user_license_price' => 0,
        ];
    }
}
