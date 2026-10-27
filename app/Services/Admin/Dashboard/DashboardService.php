<?php

namespace App\Services\Admin\Dashboard;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\PublicRequests\TrialRequestStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class DashboardService
{
    public function getDashboardData()
    {
        return [
            // KPIs
            'kpis' => $this->getKPIs(),

            // Charts Data
            'platformActivity' => $this->getPlatformActivity('1month'),
            'subscriptionStatus' => $this->getSubscriptionStatusDistribution(),

            // Recent Activities
            'recentActivities' => $this->getRecentActivities(5),

            // Recent Subscription Requests
            'latestSubscriptionRequests' => $this->getRecentSubscriptionRequests(10),
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

            // 2. العملاء المشتركين (tenants لديهم اشتراك active)
            'subscribed_companies' => Tenant::whereNull('deleted_at')
                ->whereHas('subscriptions', fn ($q) => $q->where('status', SubscriptionStatus::ACTIVE))
                ->count(),

            // 3. الاشتراكات النشطة
            'active_subscriptions' => Subscription::where('status', SubscriptionStatus::ACTIVE)->count(),

            // 4. الاشتراكات المعلقة
            'suspended_subscriptions' => Subscription::where('status', SubscriptionStatus::SUSPENDED)->count(),

            // 5. القوالب المركزية
            'central_templates' => AuctionTemplate::where('is_active', true)->count(),

            // 6. المدفوعات هذا الشهر
            'monthly_payments' => $this->getMonthlyPayments(),
        ];
    }

    private function getMonthlyPayments(): float
    {
        $paidBills = (float) Bill::where('status', BillStatus::PAID)
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNotNull('paid_at')
                        ->whereMonth('paid_at', now()->month)
                        ->whereYear('paid_at', now()->year);
                })->orWhere(function ($sub) {
                    $sub->whereNull('paid_at')
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year);
                });
            })
            ->sum('amount');

        if ($paidBills > 0) {
            return $paidBills;
        }

        return (float) Subscription::where('status', SubscriptionStatus::ACTIVE)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('price');
    }

    // ==========================================
    // Platform Activity Chart
    // ==========================================

    /**
     * إجمالي الاشتراكات المعلقة والنشطة، مع طلبات التجربة المعتمدة، حسب الفترة.
     */
    public function getPlatformActivity(string $range = '1month'): array
    {
        [$startDate, $groupFormat, $labelFormat, $carbonInterval] = $this->resolveRangeConfig($range);

        $data = $this->aggregatePlatformActivity($startDate, $groupFormat, $labelFormat, $carbonInterval);

        return $data;
    }

    private function resolveRangeConfig(string $range): array
    {
        return match ($range) {
            '3months' => [now()->subMonths(3), '%Y-%m', 'M Y', 'month'],
            '6months' => [now()->subMonths(6), '%Y-%m', 'M Y', 'month'],
            '1year' => [now()->subYear(),    '%Y-%m', 'M Y', 'month'],
            default => [now()->subMonth(),   '%Y-%m-%d', 'Y-m-d', 'day'], // آخر شهر
        };
    }

    private function aggregatePlatformActivity(
        Carbon $startDate,
        string $groupFormat,
        string $labelFormat,
        string $interval
    ): array {
        $subscriptionData = DB::connection('mysql')
            ->table('subscriptions')
            ->selectRaw('DATE_FORMAT(created_at, ?) as period, COUNT(*) as total', [$groupFormat])
            ->where('created_at', '>=', $startDate)
            ->whereIn('status', [
                SubscriptionStatus::PENDING->value,
                SubscriptionStatus::ACTIVE->value,
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        $approvedTrialData = DB::connection('mysql')
            ->table('trial_requests')
            ->selectRaw('DATE_FORMAT(processed_at, ?) as period, COUNT(*) as total', [$groupFormat])
            ->where('status', TrialRequestStatus::APPROVED->value)
            ->whereNotNull('processed_at')
            ->where('processed_at', '>=', $startDate)
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        $periods = [];
        $cursor = clone $startDate;
        $now = now();

        while ($cursor->lte($now)) {
            $key = $cursor->format($this->phpFormatFromMySql($groupFormat));
            $label = $cursor->locale('ar')->translatedFormat($this->phpLabelFormat($labelFormat));
            $count = ($subscriptionData->get($key)?->total ?? 0)
                + ($approvedTrialData->get($key)?->total ?? 0);

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
            '%Y-%m' => 'Y-m',
            default => 'Y-m',
        };
    }

    private function phpLabelFormat(string $format): string
    {
        return match ($format) {
            'Y-m-d' => 'j M',
            'M Y' => 'M Y',
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
            ['key' => 'expired',   'label' => 'منتهي',         'color' => 'dark',      'status' => SubscriptionStatus::EXPIRED],
        ];

        return collect($statuses)->map(function ($item) use ($total) {
            $count = Subscription::where('status', $item['status'])->count();

            return [
                'key' => $item['key'],
                'label' => $item['label'],
                'color' => $item['color'],
                'count' => $count,
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

    // ==========================================
    // Recent Activities (10 records)
    // ==========================================
    public function getRecentActivities(int $limit = 5)
    {
        $eventMap = [
            'created' => 'إضافة',
            'updated' => 'تحديث',
            'deleted' => 'حذف',
            'restored' => 'استعادة',
            'login' => 'تسجيل دخول',
            'logout' => 'تسجيل خروج',
        ];

        $logMap = [
            'companies' => 'الشركات',
            'plans' => 'الباقات',
            'coupons' => 'الكوبونات',
            'users' => 'المستخدمين',
            'auth' => 'تسجيل الدخول',
            'subscriptions' => 'الاشتراكات',
            'bills' => 'المعاملات المالية',
            'tickets' => 'التذاكر',
        ];

        return Activity::query()
            ->where(function ($q) {
                $q->whereNull('causer_type')->orWhere('causer_type', 'not like', 'App\\Models\\Tenants%');
            })
            ->where(function ($q) {
                $q->whereNull('subject_type')->orWhere('subject_type', 'not like', 'App\\Models\\Tenants%');
            })
            ->with(['causer', 'subject'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($activity) use ($eventMap, $logMap) {
                $activity->formatted_event = $eventMap[$activity->event] ?? ($activity->description ?? $activity->event ?? 'نشاط');
                $activity->formatted_module = $logMap[$activity->log_name] ?? ($activity->log_name ?? '');
                $activity->causer_name = $activity->causer ? $activity->causer->name : 'النظام';
                $activity->time_ago = $activity->created_at ? Carbon::parse($activity->created_at)->locale('ar')->diffForHumans() : '-';

                return $activity;
            });
    }

    // ==========================================
    // Latest Subscription Requests (10 records)
    // ==========================================
    public function getRecentSubscriptionRequests(int $limit = 10)
    {
        return Subscription::with(['tenant', 'plan'])
            ->where('status', SubscriptionStatus::PENDING)
            ->latest()
            ->limit($limit)
            ->get();
    }
}
