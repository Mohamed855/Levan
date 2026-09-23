<?php

namespace App\Jobs\Admin;

use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
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

            // $this->expireTrials($now);

            // 3. إرسال تنبيهات قبل الانتهاء (اختياري)
            // $this->sendExpirationReminders($now);

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
     * إرسال تنبيهات قبل الانتهاء (اختياري)
     */
    // private function sendExpirationReminders($now): void
    // {
    //     // البحث عن الاشتراكات التي ستنتهي خلال 7 أيام
    //     $upcomingExpiry = Subscription::where('status', SubscriptionStatus::ACTIVE)
    //         ->whereHas('currentPeriod', function ($query) use ($now) {
    //             $query->where('period_end', '>', $now)
    //                 ->where('period_end', '<=', $now->copy()->addDays(7))
    //                 ->where('status', SubscriptionPeriodStatus::ACTIVE);
    //         } )
    //         ->whereDoesntHave('periods', function ($query) {
    //             $query->where('status', SubscriptionPeriodStatus::QUEUED);
    //         } )
    //         ->with(['currentPeriod', 'tenant'])
    //         ->get();

    //     foreach ($upcomingExpiry as $subscription) {
    //         // تحقق من عدم إرسال تنبيه سابق اليوم
    //         $reminderSentToday = $subscription->reminders()
    //             ->whereDate('created_at', $now->toDateString())
    //             ->exists();

    //         if (!$reminderSentToday) {
    //             // إرسال الإشعار
    //             // event(new SubscriptionExpiringReminder($subscription));

    //             Log::info('📧 تم إرسال تذكير بانتهاء الاشتراك', [
    //                 'subscription_id' => $subscription->id,
    //                 'tenant_id'       => $subscription->tenant_id,
    //                 'expires_at'      => $subscription->currentPeriod->period_end,
    //             ]);
    //         }
    //     }
    // }
}
