<?php

namespace App\Jobs\Admin;

use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Notifications\Tenants\General\GeneralNotification;
use App\Services\shared\Subscription\SubscriptionManager;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;

class ProcessSubscriptionPeriods implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Tenancy::central(function () {
            $now = now();

            //  تفعيل الفترات المجدولة التي حان وقتها
            $this->activateQueuedPeriods($now);

            //  إنهاء الفترات والاشتراكات المنتهية
            $this->expireEndedSubscriptions($now);

            // للتاكد فقط اذا كان هناك اي اشتراك نشط ليسه له فترة حالية ولا فترات قادمة
            $this->expireActivesWithoutCurrentOrQueued();

            // 3. إرسال تنبيهات قبل الانتهاء (30 يوم و 7 أيام)
            $this->sendExpirationReminders($now);

            Log::info('✅ انتهت معالجة الفترات والاشتراكات');
        });
    }

    /*
    |============================================================================
    |============================================================================
    |                          Private methods
    |============================================================================
    |============================================================================
    */
    private function activateQueuedPeriods($now): void
    {
        $queuedPeriods = SubscriptionPeriod::where('status', SubscriptionPeriodStatus::QUEUED)->where('period_start', '<=', $now)->with('subscription')->get();

        Log::error("===============================");
        Log::error($queuedPeriods);

        foreach ($queuedPeriods as $period) {
            DB::transaction(function () use ($period) {
                $subscription = $period->subscription()->lockForUpdate()->first();

                $this->switchCurrentTo($subscription, $period);

                $this->forgetSubscriptionCache($subscription->tenant_id);
            });
        }
    }

    // إنهاء الاشتراكات المنتهية
    private function expireEndedSubscriptions($now): void
    {
        $expiredSubscriptions = Subscription::whereIn('status', [SubscriptionStatus::ACTIVE, SubscriptionStatus::TRIAL])
            ->whereHas('currentPeriod', function ($query) use ($now) {
                $query->where('period_end', '<', $now)->whereIn('status', [SubscriptionPeriodStatus::ACTIVE, SubscriptionPeriodStatus::TRIAL]);
            })
            ->with('currentPeriod')
            ->get();

        foreach ($expiredSubscriptions as $subscription) {
            DB::transaction(function () use ($subscription) {
                $hasQueued = $subscription->periods()->where('status', SubscriptionPeriodStatus::QUEUED)->exists();

                if ($hasQueued) return;

                // انهاء الفترة
                SubscriptionPeriod::where('subscription_id', $subscription->id)->where('is_current', true)
                    ->update([
                        'status'     => SubscriptionPeriodStatus::ENDED,
                        'is_current' => false,
                    ]);

                $subscription->update(['status' => SubscriptionStatus::EXPIRED]);

                $this->forgetSubscriptionCache($subscription->tenant_id);
            });
        }
    }

    private function expireActivesWithoutCurrentOrQueued(): void
    {
        $ids = Subscription::query()->where('status', SubscriptionStatus::ACTIVE)->doesntHave('currentPeriod')
            ->whereDoesntHave('periods', function ($q) {
                $q->where('status', SubscriptionPeriodStatus::QUEUED);
            })->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $subscriptions = Subscription::whereIn('id', $ids)->get(['id', 'tenant_id']);

        Subscription::whereIn('id', $ids)->update([
            'status'     => SubscriptionStatus::EXPIRED,
            'updated_at' => now(),
        ]);

        foreach ($subscriptions as $subscription) {
            $this->forgetSubscriptionCache($subscription->tenant_id);
        }
    }

    // private function expireTrials($now): void
    // {
    //     $expired = Subscription::where('status', SubscriptionStatus::TRIAL)->where('trial_end_date', '<', $now)->get();

    //     foreach ($expired as $subscription) {
    //         $subscription->update(['status' => SubscriptionStatus::EXPIRED]);
    //         $this->forgetSubscriptionCache($subscription->tenant_id); //
    //     }

    //     Log::info("تجارب منتهية: {$expired->count()} ");
    // }

    private function forgetSubscriptionCache(string $tenantId): void
    {
        cache()->forget("tenant:{$tenantId}:subscription");
    }

    private function switchCurrentTo(Subscription $subscription, SubscriptionPeriod $newPeriod): void
    {
        DB::transaction(function () use ($subscription, $newPeriod) {
            // انهي أي فترة حالية
            SubscriptionPeriod::where('subscription_id', $subscription->id)->where('is_current', true)
                ->update([
                    'status'     => SubscriptionPeriodStatus::ENDED,
                    'is_current' => false,
                ]);

            $newPeriod->update([
                'status'     => SubscriptionPeriodStatus::ACTIVE,
                'is_current' => true,
            ]);
        });
    }

    /**
     * إرسال تنبيهات قبل الانتهاء (30 يوم و 7 أيام)
     */
    private function sendExpirationReminders($now): void
    {
        $today = $now->copy()->startOfDay();

        // 1. تذكيرات قبل 30 يوم (بين 8 و 30 يوماً)
        $periods30 = SubscriptionPeriod::where('is_current', true)
            ->where('status', SubscriptionPeriodStatus::ACTIVE)
            ->whereNull('reminded_at_30')
            ->where('period_end', '<=', $now->copy()->addDays(30))
            ->where('period_end', '>', $now->copy()->addDays(7))
            ->whereDoesntHave('subscription.periods', function ($q) {
                $q->where('status', SubscriptionPeriodStatus::QUEUED);
            })
            ->with(['subscription.tenant'])
            ->get();

        foreach ($periods30 as $period) {
            $subscription = $period->subscription;
            if (!$subscription || !$subscription->tenant) continue;

            $days = app(SubscriptionManager::class)->calculateDaysRemaining($period) ?? 0;

            $this->notifyTenantSubscriptionExpiring(
                $subscription->tenant,
                $days,
                $period->period_end,
                'warning'
            );

            $period->update(['reminded_at_30' => $now]);
        }

        // 2. تذكيرات عاجلة قبل 7 أيام أو أقل
        $periods7 = SubscriptionPeriod::where('is_current', true)
            ->where('status', SubscriptionPeriodStatus::ACTIVE)
            ->whereNull('reminded_at_7')
            ->where('period_end', '<=', $now->copy()->addDays(7))
            ->where('period_end', '>', $now)
            ->whereDoesntHave('subscription.periods', function ($q) {
                $q->where('status', SubscriptionPeriodStatus::QUEUED);
            })
            ->with(['subscription.tenant'])
            ->get();

        foreach ($periods7 as $period) {
            $subscription = $period->subscription;
            if (!$subscription || !$subscription->tenant) continue;

            $days = app(SubscriptionManager::class)->calculateDaysRemaining($period) ?? 0;

            $this->notifyTenantSubscriptionExpiring(
                $subscription->tenant,
                $days,
                $period->period_end,
                'danger'
            );

            $period->update([
                'reminded_at_7'  => $now,
                'reminded_at_30' => $period->reminded_at_30 ?? $now,
            ]);
        }
    }

    /**
     * إرسال إشعار للمستأجر عبر GeneralNotification
     */
    private function notifyTenantSubscriptionExpiring($tenant, int $days, $periodEnd, string $level = 'warning'): void
    {
        try {
            $tenant->run(function () use ($days, $periodEnd, $level) {
                $user = \App\Models\Tenants\UserMangment\User\User::find(1) ?? \App\Models\Tenants\UserMangment\User\User::first();
                if (!$user) return;

                $formattedDate = Carbon::parse($periodEnd)->format('Y-m-d');
                $isUrgent = ($level === 'danger');

                $title = $isUrgent ? 'تحذير عاجل: اقتراب انتهاء الاشتراك' : 'تذكير: اقتراب موعد تجديد الاشتراك';
                $message = $isUrgent
                    ? "اشتراك المنشأة سينتهي خلال {$days} أيام (بتاريخ {$formattedDate}). يرجى التجديد لتفادي إيقاف الخدمات."
                    : "اشتراك المنشأة ينتهي خلال {$days} يوماً (بتاريخ {$formattedDate}). يرجى تجديد الاشتراك لضمان استمرار الخدمة.";

                $url = tenant_route_name('subscriptions.index');
                $icon = $isUrgent ? 'ti ti-alert-triangle' : 'ti ti-calendar-time';

                $notification = new GeneralNotification($title, $message, $level, $icon, $url);
                $user->notifyNow($notification);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to send subscription expiration reminder: ' . $e->getMessage());
        }
    }
}
