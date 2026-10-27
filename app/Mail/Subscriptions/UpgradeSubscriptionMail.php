<?php

declare(strict_types=1);

namespace App\Mail\Subscriptions;

use App\Mail\BaseMailable;
use App\Models\Admin\Subscription\Subscription;

final class UpgradeSubscriptionMail extends BaseMailable
{
    public function __construct(
        public readonly Subscription $subscription,
        public readonly int $newLicensesCount = 0
    ) {}

    public function build(): static
    {
        $tenant = $this->subscription->tenant;
        $plan = $this->subscription->plan;
        $loginUrl = $tenant ? route('path.users.index', ['tenant' => $tenant->id], false) : config('app.url');
        if (! str_starts_with($loginUrl, 'http')) {
            $loginUrl = url($loginUrl);
        }

        return $this->subject('تمت ترقية اشتراكك وشراء تراخيص جديدة — لوت Levan')
            ->viewWithOffice('emails.subscriptions.upgraded', [
                'customerName'     => $tenant?->company_name ?? 'عزيزنا العميل',
                'planName'         => $plan?->name ?? 'الباقة',
                'newLicensesCount' => $this->newLicensesCount,
                'totalUsers'       => $this->subscription->total_users,
                'loginUrl'         => $loginUrl,
            ]);
    }
}
