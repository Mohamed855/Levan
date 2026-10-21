<?php

declare(strict_types=1);

namespace App\Actions\Admin\Subscriptions;

use App\Models\Admin\Subscriptions\Subscription;
use Illuminate\Support\Facades\DB;

final class UpdateSubscriptionStatusAction
{
    public function execute(Subscription $subscription, string $newStatus): Subscription
    {
        return DB::transaction(function () use ($subscription, $newStatus): Subscription {
            $data = ['status' => $newStatus];

            if ($newStatus === 'cancelled') {
                $data['cancelled_at'] = now();
            }

            $subscription->update($data);

            // Keep the current period status in sync
            $subscription->currentPeriod?->update(['status' => $newStatus]);

            return $subscription->refresh();
        });
    }
}
