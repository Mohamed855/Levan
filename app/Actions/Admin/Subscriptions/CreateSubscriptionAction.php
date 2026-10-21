<?php

declare(strict_types=1);

namespace App\Actions\Admin\Subscriptions;

use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscriptions\Subscription;
use App\Models\Admin\Subscriptions\SubscriptionPeriod;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateSubscriptionAction
{
    public function execute(Tenant $tenant, Plan $plan, float $amount, string $status = 'active'): Subscription
    {
        return DB::transaction(function () use ($tenant, $plan, $amount, $status): Subscription {
            $start = now();
            $end = $this->periodEnd($start, $plan->subscription_billing_cycle);
            $totalUsers = $plan->limits_mode === 'unlimited' ? 1 : max(1, (int) $plan->included_users);

            $subscription = Subscription::query()->create([
                'subscription_number' => 'SUB-'.now()->format('Y').'-'.Str::upper(Str::random(10)),
                'tenant_id' => $tenant->getKey(),
                'plan_id' => $plan->getKey(),
                'billing_cycle' => $plan->subscription_billing_cycle ?: 'monthly',
                'status' => $status,
                'price' => $amount,
                'total_users' => $totalUsers,
                'used_users' => 1,
                'starts_at' => $start,
                'ends_at' => $end,
                'next_payment_date' => $end,
            ]);

            SubscriptionPeriod::query()->create([
                'subscription_id' => $subscription->getKey(),
                'period_number' => 1,
                'period_start' => $start,
                'period_end' => $end,
                'plan_id_snapshot' => $plan->getKey(),
                'plan_code_snapshot' => $plan->code ?: 'plan-'.$plan->getKey(),
                'plan_name_snapshot' => $plan->name,
                'plan_type_snapshot' => $plan->plan_type ?: 'basic',
                'billing_cycle_snapshot' => $subscription->billing_cycle,
                'limits_mode_snapshot' => $plan->limits_mode ?: 'limited',
                'limits_snapshot' => $plan->limits_mode === 'unlimited' ? null : [
                    'included_users' => $totalUsers,
                    'properties_limit' => $plan->properties_limit,
                    'storage_quota_gb' => $plan->storage_quota_gb,
                    'active_auctions_limit' => $plan->active_auctions_limit,
                ],
                'features_snapshot' => $plan->features ?? [],
                'price_snapshot' => $amount,
                'total_users_snapshot' => $totalUsers,
                'used_users_snapshot' => 1,
                'status' => $status,
                'is_current' => $status === 'active',
            ]);

            return $subscription->load(['plan', 'currentPeriod']);
        });
    }

    private function periodEnd(Carbon $start, ?string $cycle): Carbon
    {
        return match ($cycle) {
            'quarterly' => $start->copy()->addMonths(3),
            'yearly', 'annual' => $start->copy()->addYear(),
            default => $start->copy()->addMonth(),
        };
    }
}
