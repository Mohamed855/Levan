<?php

declare(strict_types=1);

namespace App\Actions\Admin\Subscriptions;

use App\Models\Admin\Subscriptions\Subscription;
use App\Models\Admin\Subscriptions\SubscriptionPeriod;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Reviews manually-created pending subscriptions without settling any invoice. */
final class ReviewPendingSubscriptionsAction
{
    public function accept(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription): Subscription {
            return $this->review([$subscription->getKey()], true)->first()
                ?? throw new RuntimeException('الاشتراك المحدد لم يعد متاحًا.');
        });
    }

    public function reject(Subscription $subscription, ?string $reason): Subscription
    {
        return DB::transaction(function () use ($subscription, $reason): Subscription {
            return $this->review([$subscription->getKey()], false, $reason)->first()
                ?? throw new RuntimeException('الاشتراك المحدد لم يعد متاحًا.');
        });
    }

    public function acceptMany(array $ids): int
    {
        return DB::transaction(fn (): int => $this->review($ids, true)->count());
    }

    public function rejectMany(array $ids, ?string $reason): int
    {
        return DB::transaction(fn (): int => $this->review($ids, false, $reason)->count());
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Subscription> */
    private function review(array $ids, bool $accept, ?string $reason = null)
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $subscriptions = Subscription::query()->whereKey($ids)->lockForUpdate()->get()->keyBy('id');

        if (count($ids) !== $subscriptions->count()) {
            throw new RuntimeException('بعض الاشتراكات المحددة لم تعد متاحة.');
        }

        foreach ($ids as $id) {
            $subscription = $subscriptions->get($id);
            if (! $subscription instanceof Subscription || $subscription->status !== 'pending') {
                throw new RuntimeException('لا يمكن مراجعة اشتراك ليس قيد المراجعة.');
            }

            $period = SubscriptionPeriod::query()
                ->where('subscription_id', $subscription->getKey())
                ->orderByDesc('period_number')
                ->lockForUpdate()
                ->first();

            if ($period === null) {
                throw new RuntimeException('لا توجد فترة مرتبطة بالاشتراك المحدد.');
            }

            if ($accept) {
                SubscriptionPeriod::query()
                    ->where('subscription_id', $subscription->getKey())
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
                $subscription->update(['status' => 'active', 'cancelled_at' => null, 'cancellation_reason' => null]);
                $period->update(['status' => 'active', 'is_current' => true]);
            } else {
                $subscription->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason,
                ]);
                $period->update(['status' => 'cancelled', 'is_current' => false]);
            }
        }

        return $subscriptions->values();
    }
}
