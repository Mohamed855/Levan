<?php

declare(strict_types=1);

namespace App\Actions\Admin\Subscriptions;

use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Subscriptions\Subscription;
use App\Models\Admin\Subscriptions\SubscriptionPeriod;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

final class UpdateSubscriptionStatusAction
{
    public function execute(Subscription $subscription, string $newStatus): Subscription
    {
        return DB::transaction(function () use ($subscription, $newStatus): Subscription {
            $subscription = Subscription::query()->lockForUpdate()->findOrFail($subscription->getKey());

            if (! SubscriptionStatus::canTransition((string) $subscription->status, $newStatus)) {
                throw ValidationException::withMessages([
                    'status' => ['الانتقال إلى هذه الحالة غير مسموح من الحالة الحالية.'],
                ]);
            }

            $data = ['status' => $newStatus];

            if ($newStatus === 'cancelled') {
                $data['cancelled_at'] = now();
            }

            $subscription->update($data);

            $period = SubscriptionPeriod::query()
                ->where('subscription_id', $subscription->getKey())
                ->orderByDesc('period_number')
                ->lockForUpdate()
                ->first();

            if ($period !== null) {
                if ($newStatus === 'active') {
                    SubscriptionPeriod::query()
                        ->where('subscription_id', $subscription->getKey())
                        ->where('is_current', true)
                        ->update(['is_current' => false]);
                    $period->update(['status' => 'active', 'is_current' => true]);
                } elseif ($newStatus === 'cancelled') {
                    SubscriptionPeriod::query()
                        ->where('subscription_id', $subscription->getKey())
                        ->where('is_current', true)
                        ->update(['is_current' => false]);
                    $period->update(['status' => 'cancelled', 'is_current' => false]);
                } else {
                    $period->update(['status' => $newStatus]);
                }
            }

            return $subscription->refresh();
        });
    }
}
