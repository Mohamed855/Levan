<?php

declare(strict_types=1);

namespace App\Mail\Subscriptions;

use App\Mail\BaseMailable;
use App\Models\Admin\Subscription\Subscription;

final class SubscriptionExpiredMail extends BaseMailable
{
    public function __construct(
        public readonly Subscription $subscription
    ) {}

    public function build(): static
    {
        $tenant = $this->subscription->tenant;
        $renewUrl = $tenant ? route('path.subscriptions.index', ['tenant' => $tenant->id], false) : config('app.url');
        if (! str_starts_with($renewUrl, 'http')) {
            $renewUrl = url($renewUrl);
        }

        return $this->subject('إشعار: انتهت فترة اشتراكك في منصة لوت Levan')
            ->viewWithOffice('emails.subscriptions.expired', [
                'customerName' => $tenant?->company_name ?? 'عزيزنا العميل',
                'renewUrl'     => $renewUrl,
            ]);
    }
}
