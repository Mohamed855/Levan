<?php

declare(strict_types=1);

namespace App\Mail\Subscriptions;

use App\Mail\BaseMailable;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Subscription\Subscription;

final class NewSubscriptionMail extends BaseMailable
{
    public function __construct(
        public readonly Subscription $subscription,
        public readonly ?Bill $bill = null
    ) {}

    public function build(): static
    {
        $tenant = $this->subscription->tenant;
        $plan = $this->subscription->plan;
        $loginUrl = $tenant ? route('path.dashboard', ['tenant' => $tenant->id], false) : config('app.url');
        if (! str_starts_with($loginUrl, 'http')) {
            $loginUrl = url($loginUrl);
        }

        return $this->subject("تم تفعيل اشتراكك في باقة {$plan?->name} بنجاح — لوت Levan")
            ->viewWithOffice('emails.subscriptions.new_subscription', [
                'customerName'       => $tenant?->company_name ?? 'عزيزنا العميل',
                'planName'           => $plan?->name ?? 'الباقة المختارة',
                'subscriptionNumber' => $this->subscription->subscription_number ?? (string) $this->subscription->id,
                'amount'             => $this->bill?->amount ?? $this->subscription->price,
                'billNumber'         => $this->bill?->bill_number,
                'startDate'          => $this->subscription->starts_at?->format('Y-m-d') ?? date('Y-m-d'),
                'endDate'            => $this->subscription->ends_at?->format('Y-m-d') ?? $this->subscription->next_payment_date?->format('Y-m-d'),
                'totalUsers'         => $this->subscription->total_users,
                'loginUrl'           => $loginUrl,
            ]);
    }
}
