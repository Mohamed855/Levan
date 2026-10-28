<?php

declare(strict_types=1);

namespace App\Services\Shared\Notifications;

use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Notifications\NotificationDelivery;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;

class NotificationDeadlineScanner
{
    public function __construct(private readonly DashboardNotificationService $notices) {}

    public function scan(): void
    {
        if (! config('dashboard-notifications.enabled', true)) {
            return;
        }
        Tenancy::central(function (): void {
            foreach (Tenant::with('lastSubscription.currentPeriod')->cursor() as $tenant) {
                try {
                    if ($tenant->lastSubscription) {
                        $this->subscription($tenant->lastSubscription);
                    }
                } catch (\Throwable $exception) {
                    Log::warning('Tenant notification scan will retry.', ['tenant_id' => $tenant->id]);
                }
            }
            foreach (Coupon::cursor() as $coupon) {
                $this->coupon($coupon);
            }
            foreach (NotificationDelivery::whereNull('delivered_at')->orderBy('updated_at')->limit(200)->get() as $delivery) {
                $this->notices->deliver($delivery);
            }
        });
    }

    public function subscription(Subscription $subscription): void
    {
        if (! in_array($subscription->status->value, ['active', 'trial', 'expired'], true)) {
            return;
        }
        if ($subscription->tenant?->lastSubscription?->id !== $subscription->id) {
            return;
        }
        if ($subscription->periods()->where('status', 'queued')->where('period_end', '>', now())->exists()) {
            return;
        }
        $end = $subscription->currentPeriod?->period_end ?? $subscription->ends_at;
        if (! $end) {
            return;
        }
        $end = Carbon::parse($end);
        $seconds = now()->diffInSeconds($end, false);
        $days = max(0, (int) ceil($seconds / 86400));
        $marker = $subscription->id.':'.$end->toIso8601String();
        $adminUrl = route('admin.clients.show', $subscription->tenant_id, false);
        $tenantUrl = route('path.subscriptions.index', ['tenant' => $subscription->tenant_id], false);
        $company = $subscription->tenant?->company_name ?? $subscription->tenant_id;
        $email = $subscription->tenant?->primary_contact_email ?? $subscription->tenant?->email;

        if ($seconds <= 0) {
            $this->notices->admins('subscription.expired:'.$marker, DashboardNotificationService::payload('subscription.expired', 'انتهاء اشتراك', "انتهى اشتراك {$company} بتاريخ {$end->format('Y-m-d')}.", $adminUrl, 'danger'));
            $this->notices->tenant($subscription->tenant_id, 'subscription.expired:'.$marker, DashboardNotificationService::payload('subscription.expired', 'انتهى الاشتراك', 'انتهى اشتراك شركتك. راجع صفحة الاشتراك لمتابعة التفعيل.', $tenantUrl, 'danger'));
            if ($email) {
                $cacheKey = "mail_sent:sub_{$subscription->id}:expired:{$end->format('Y-m-d')}";
                if (! \Illuminate\Support\Facades\Cache::has($cacheKey)) {
                    \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addDays(40));
                    \App\Services\Shared\Mail\SystemMailService::sendSubscriptionExpired($email, $subscription);
                }
            }
        } elseif ($seconds <= 7 * 86400) {
            $this->notices->admins('subscription.expiring7:'.$marker, DashboardNotificationService::payload('subscription.expiring7', 'اشتراك يقترب من الانتهاء', "اشتراك {$company} ينتهي خلال {$days} أيام.", $adminUrl, 'warning'));
            $this->notices->tenant($subscription->tenant_id, 'subscription.expiring7:'.$marker, DashboardNotificationService::payload('subscription.expiring7', 'متبقي 7 أيام أو أقل على انتهاء الاشتراك', "اشتراك شركتك ينتهي خلال {$days} أيام.", $tenantUrl, 'warning'));
            if ($email) {
                $cacheKey = "mail_sent:sub_{$subscription->id}:exp7:{$end->format('Y-m-d')}";
                if (! \Illuminate\Support\Facades\Cache::has($cacheKey)) {
                    \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addDays(40));
                    \App\Services\Shared\Mail\SystemMailService::sendSubscriptionExpiring($email, $subscription, $days);
                }
            }
        } elseif ($seconds <= 30 * 86400) {
            $this->notices->tenant($subscription->tenant_id, 'subscription.expiring30:'.$marker, DashboardNotificationService::payload('subscription.expiring30', 'متبقي 30 يومًا أو أقل على انتهاء الاشتراك', "اشتراك شركتك ينتهي خلال {$days} يومًا.", $tenantUrl, 'warning'));
            if ($email) {
                $cacheKey = "mail_sent:sub_{$subscription->id}:exp30:{$end->format('Y-m-d')}";
                if (! \Illuminate\Support\Facades\Cache::has($cacheKey)) {
                    \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addDays(40));
                    \App\Services\Shared\Mail\SystemMailService::sendSubscriptionExpiring($email, $subscription, $days);
                }
            }
        }
    }

    public function coupon(Coupon $coupon): void
    {
        $url = route('admin.coupons.index', [], false);
        if ($coupon->isExpired()) {
            $this->notices->admins("coupon.expired:$coupon->id:".$coupon->expiry_date->format('Y-m-d'), DashboardNotificationService::payload('coupon.expired', 'انتهت مدة الكوبون', "انتهت صلاحية الكوبون {$coupon->code}.", $url, 'warning', 'ti ti-discount-2'));
        }
        if ($coupon->hasReachedLimit()) {
            $this->notices->admins("coupon.exhausted:$coupon->id:$coupon->usage_limit", DashboardNotificationService::payload('coupon.exhausted', 'نفد حد استخدام الكوبون', "وصل الكوبون {$coupon->code} إلى حد الاستخدام ({$coupon->usage_limit}).", $url, 'warning', 'ti ti-discount-2'));
        }
    }
}
