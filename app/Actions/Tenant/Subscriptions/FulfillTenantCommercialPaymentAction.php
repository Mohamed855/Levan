<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Subscriptions;

use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Bill;
use App\Models\Admin\Subscriptions\Subscription;
use App\Models\Admin\Subscriptions\SubscriptionPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Applies a paid, tenant-owned commercial bill exactly once. */
final class FulfillTenantCommercialPaymentAction
{
    public function execute(Bill $bill): void
    {
        $metadata = $bill->metadata ?? [];
        if (($metadata['checkout_source'] ?? null) !== 'tenant_commercial') {
            return;
        }

        $connection = (string) config('tenancy.database.central_connection');
        if ($connection === '') {
            throw new RuntimeException('The central database connection must be configured.');
        }

        DB::connection($connection)->transaction(function () use ($bill, $connection): void {
            $lockedBill = (new Bill())->setConnection($connection)->newQuery()->lockForUpdate()->findOrFail($bill->getKey());
            $metadata = $lockedBill->metadata ?? [];
            if (($metadata['commercial_fulfilled_at'] ?? null) !== null) {
                return;
            }

            $subscription = (new Subscription())->setConnection($connection)->newQuery()->with('plan')->lockForUpdate()->findOrFail($metadata['subscription_id'] ?? null);
            match ($metadata['commercial_operation'] ?? null) {
                'renewal' => $this->renew($subscription, $connection),
                'license_purchase' => $this->addSeats($subscription, (int) ($metadata['license_count'] ?? 0)),
                default => throw new RuntimeException('نوع عملية الاشتراك غير صالح.'),
            };

            $metadata['commercial_fulfilled_at'] = now()->toIso8601String();
            $lockedBill->update(['metadata' => $metadata]);
        });
    }

    private function renew(Subscription $subscription, string $connection): void
    {
        $plan = $subscription->plan;
        if ($plan === null) {
            throw new RuntimeException('لا يمكن تجديد اشتراك بلا باقة مرتبطة.');
        }

        $start = $subscription->ends_at?->isFuture() ? $subscription->ends_at->copy() : now();
        $end = $this->periodEnd($start, $subscription->billing_cycle);
        $subscription->update([
            'status' => SubscriptionStatus::ACTIVE->value,
            'starts_at' => $subscription->starts_at ?? $start,
            'ends_at' => $end,
            'next_payment_date' => $end,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);

        $periods = (new SubscriptionPeriod())->setConnection($connection)->newQuery();
        $periods->where('subscription_id', $subscription->getKey())->where('is_current', true)->update(['is_current' => false]);
        $periods->create([
            'subscription_id' => $subscription->getKey(),
            'period_number' => ((int) $periods->where('subscription_id', $subscription->getKey())->max('period_number')) + 1,
            'period_start' => $start,
            'period_end' => $end,
            'plan_id_snapshot' => $plan->getKey(),
            'plan_code_snapshot' => $plan->code ?: 'plan-'.$plan->getKey(),
            'plan_name_snapshot' => $plan->name,
            'plan_type_snapshot' => $plan->plan_type ?: 'basic',
            'billing_cycle_snapshot' => $subscription->billing_cycle,
            'limits_mode_snapshot' => $plan->limits_mode ?: 'limited',
            'limits_snapshot' => $plan->limits_mode === 'unlimited' ? null : ['included_users' => $subscription->total_users],
            'features_snapshot' => $plan->features ?? [],
            'price_snapshot' => $subscription->price,
            'total_users_snapshot' => $subscription->total_users,
            'used_users_snapshot' => $subscription->used_users,
            'status' => SubscriptionStatus::ACTIVE->value,
            'is_current' => true,
        ]);
    }

    private function addSeats(Subscription $subscription, int $count): void
    {
        if ($count < 1) {
            throw new RuntimeException('عدد التراخيص الإضافية غير صالح.');
        }

        $subscription->increment('total_users', $count);
        $subscription->currentPeriod?->update([
            'total_users_snapshot' => $subscription->fresh()->total_users,
        ]);
    }

    private function periodEnd(Carbon $start, string $cycle): Carbon
    {
        return match ($cycle) {
            'quarterly' => $start->copy()->addMonths(3),
            'yearly', 'annual' => $start->copy()->addYear(),
            default => $start->copy()->addMonth(),
        };
    }
}
