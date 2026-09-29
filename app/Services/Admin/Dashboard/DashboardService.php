<?php

namespace App\Services\Admin\Dashboard;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\ControlDomain\ControlDomain;
use App\Models\Admin\Tenants\Domain;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    public function getDashboardData()
    {
        return [
            // KPIs
            'kpis'               => $this->getKPIs(),

            // Charts Data
            'platformActivity'   => $this->getPlatformActivity('1month'),
            'subscriptionStatus' => $this->getSubscriptionStatusDistribution(),
        ];
    }

    public function getPlatformActivityByRange(string $range = '1month'): array
    {
        return $this->getPlatformActivity($range);
    }

    // ==========================================
    // KPIs - المؤشرات الرئيسية (6 كروت)
    // ==========================================
    private function getKPIs(): array
    {
        return [
            // 1. طلبات الاشتراك الجديدة (pending)
            'new_subscription_requests' => Subscription::where('status', SubscriptionStatus::PENDING)->count(),

            // 2. الشركات المشتركة (tenants لديهم اشتراك active)
            'subscribed_companies'      => Tenant::whereNull('deleted_at')
                ->whereHas('lastSubscription', fn($q) => $q->where('status', SubscriptionStatus::ACTIVE))
                ->count(),

            // 3. الاشتراكات النشطة
            'active_subscriptions'      => Subscription::where('status', SubscriptionStatus::ACTIVE)->count(),

            // 4. الاشتراكات المعلقة
            'suspended_subscriptions'   => Subscription::where('status', SubscriptionStatus::SUSPENDED)->count(),

            // 5. القوالب المركزية (ثابت 0 حتى يتم إنشاء الموديل)
            'central_templates'         => 0,

            // 6. المدفوعات هذا الشهر
            'monthly_payments'          => $this->getMonthlyPayments(),
        ];
    }

    private function getMonthlyPayments(): float
    {
        // مجموع سعر الاشتراكات المُنشأة هذا الشهر والتي حالتها active
        return (float) Subscription::where('status', SubscriptionStatus::ACTIVE)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('price');
    }

    // ==========================================
    // Platform Activity Chart
    // ==========================================

    /**
     * إجمالي النشاط للمنصة (عدد سجلات الـ activity_log في كل tenant)
     * مجمّعة حسب اليوم/الأسبوع/الشهر بناءً على المدة المختارة
     */
    public function getPlatformActivity(string $range = '1month'): array
    {
        [$startDate, $groupFormat, $labelFormat, $carbonInterval] = $this->resolveRangeConfig($range);

        $data = $this->aggregateActivityFromSubscriptions($startDate, $groupFormat, $labelFormat, $carbonInterval);

        return $data;
    }

    private function resolveRangeConfig(string $range): array
    {
        return match ($range) {
            '3months' => [now()->subMonths(3), '%Y-%m', 'M Y', 'month'],
            '6months' => [now()->subMonths(6), '%Y-%m', 'M Y', 'month'],
            '1year'   => [now()->subYear(),    '%Y-%m', 'M Y', 'month'],
            default   => [now()->subMonth(),   '%Y-%m-%d', 'Y-m-d', 'day'], // آخر شهر
        };
    }

    private function aggregateActivityFromSubscriptions(
        Carbon $startDate,
        string $groupFormat,
        string $labelFormat,
        string $interval
    ): array {
        $rawData = DB::connection('mysql')
            ->table('subscriptions')
            ->selectRaw("DATE_FORMAT(created_at, ?) as period, COUNT(*) as total", [$groupFormat])
            ->where('created_at', '>=', $startDate)
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        $periods = [];
        $cursor  = clone $startDate;
        $now     = now();

        while ($cursor->lte($now)) {
            $key    = $cursor->format($this->phpFormatFromMySql($groupFormat));
            $label  = $cursor->locale('ar')->translatedFormat($this->phpLabelFormat($labelFormat));
            $count  = $rawData->get($key)?->total ?? 0;

            $periods[] = ['label' => $label, 'count' => (int) $count];

            if ($interval === 'day') {
                $cursor->addDay();
            } else {
                $cursor->addMonth();
            }
        }

        return $periods;
    }

    private function phpFormatFromMySql(string $mysqlFormat): string
    {
        return match ($mysqlFormat) {
            '%Y-%m-%d' => 'Y-m-d',
            '%Y-%m'    => 'Y-m',
            default    => 'Y-m',
        };
    }

    private function phpLabelFormat(string $format): string
    {
        return match ($format) {
            'Y-m-d' => 'j M',
            'M Y'   => 'M Y',
            default => 'M Y',
        };
    }

    // ==========================================
    // Subscription Status Distribution
    // ==========================================
    public function getSubscriptionStatusDistribution(): array
    {
        $total = Subscription::count();

        $statuses = [
            ['key' => 'pending',   'label' => 'قيد المراجعة', 'color' => 'primary',   'status' => SubscriptionStatus::PENDING],
            ['key' => 'active',    'label' => 'نشط',           'color' => 'success',   'status' => SubscriptionStatus::ACTIVE],
            ['key' => 'suspended', 'label' => 'معلق',          'color' => 'warning',   'status' => SubscriptionStatus::SUSPENDED],
            ['key' => 'cancelled', 'label' => 'ملغي',          'color' => 'danger',    'status' => SubscriptionStatus::CANCELLED],
            ['key' => 'expired',   'label' => 'منتهي',         'color' => 'secondary', 'status' => SubscriptionStatus::EXPIRED],
        ];

        return collect($statuses)->map(function ($item) use ($total) {
            $count = Subscription::where('status', $item['status'])->count();
            return [
                'key'        => $item['key'],
                'label'      => $item['label'],
                'color'      => $item['color'],
                'count'      => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        })->values()->toArray();
    }

    // ==========================================
    // Pending Subscriptions count (for sidebar)
    // ==========================================
    public function getPendingCount(): int
    {
        return Subscription::where('status', SubscriptionStatus::PENDING)->count();
    }
}
