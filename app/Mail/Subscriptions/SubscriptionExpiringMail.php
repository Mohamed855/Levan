<?php

declare(strict_types=1);

namespace App\Mail\Subscriptions;

use App\Mail\BaseMailable;
use App\Models\Admin\Subscription\Subscription;

final class SubscriptionExpiringMail extends BaseMailable
{
    public function __construct(
        public readonly Subscription $subscription,
        public readonly int $daysRemaining
    ) {}

    public function build(): static
    {
        $tenant = $this->subscription->tenant;
        $plan = $this->subscription->plan;
        $renewUrl = $tenant ? route('path.subscriptions.index', ['tenant' => $tenant->id], false) : config('app.url');
        if (! str_starts_with($renewUrl, 'http')) {
            $renewUrl = url($renewUrl);
        }

        $expiryDate = $this->subscription->ends_at?->format('Y-m-d') 
            ?? $this->subscription->currentPeriod?->period_end?->format('Y-m-d') 
            ?? date('Y-m-d');

        return $this->subject("تنبيه: اشتراكك ينتهي خلال {$this->daysRemaining} أيام — لوت Levan")
            ->viewWithOffice('emails.subscriptions.expiring_soon', [
                'customerName'  => $tenant?->company_name ?? 'عزيزنا العميل',
                'planName'      => $plan?->name ?? 'الباقة الحالية',
                'daysRemaining' => $this->daysRemaining,
                'expiryDate'    => $expiryDate,
                'renewUrl'      => $renewUrl,
            ]);
    }
}
