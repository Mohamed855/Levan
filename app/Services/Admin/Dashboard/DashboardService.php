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
            'kpis'                  => $this->getKPIs(),

            // Charts Data
            'monthlyCustomers'      => $this->getMonthlyCustomersData(),
            'topCustomersByUsers'   => $this->getTopCustomersByUsers(),
            'monthlyRevenue'        => $this->getMonthlyRevenue(),
            'subscriptionStatus'    => $this->getSubscriptionStatusDistribution(),
            'userUsage'             => $this->getUserUsageAnalysis(),
            'couponUsage'           => $this->getCouponUsageStats(),
            'domainsByExtension'    => $this->getDomainsByExtension(), // جديد

            // Tables Data
            'upcomingRenewals'      => $this->getUpcomingRenewals(),
            'recentSubscriptions'   => $this->getRecentSubscriptions(),
            'expiringSubscriptions' => $this->getExpiringSubscriptions(),
            'controlDomains'        => $this->getControlDomainsTable(), // جديد
            'recentDomains'         => $this->getRecentDomains(), // جديد

            // Growth Metrics
            'growthRate'            => $this->getGrowthRate(),
        ];
    }

    // ==========================================
    // KPIs - المؤشرات الرئيسية
    // ==========================================
    private function getKPIs()
    {
        return [
            'total_tenants'          => Tenant::whereNull('deleted_at')->count(),
            'active_subscriptions'   => Subscription::where('status', SubscriptionStatus::ACTIVE)->count(),
            'mrr'                    => $this->calculateMRR(),
            'total_users'            => $this->getTotalUsers(),
            'new_tenants_this_month' => Tenant::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'expiring_soon'          => $this->getExpiringSoonCount(),
            'active_coupons'         => $this->getActiveCouponsCount(),
            'revenue_this_month'     => $this->getRevenueThisMonth(),
        ];
    }

    private function calculateMRR()
    {
        return Subscription::where('status', SubscriptionStatus::ACTIVE)->sum('price');
    }

    private function getTotalUsers()
    {
        return Subscription::where('status', SubscriptionStatus::ACTIVE)->sum('total_users');
    }

    private function getExpiringSoonCount()
    {
        return SubscriptionPeriod::where('is_current', true)
            ->whereBetween('period_end', [now(), now()->addDays(30)])
            ->count();
    }

    private function getActiveCouponsCount()
    {
        return Coupon::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now());
            })
            ->count();
    }

    private function getRevenueThisMonth()
    {
        return Subscription::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('price');
    }

    // ==========================================
    // Charts - المخططات البيانية
    // ==========================================

    public function getMonthlyCustomersData()
    {
        $months = collect();

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = Tenant::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $months->push([
                'month' => $date->locale('ar')->translatedFormat('M Y'),
                'count' => $count
            ]);
        }

        return $months;
    }

    public function getTopCustomersByUsers()
    {
        return Tenant::with('lastSubscription')
            ->whereHas('lastSubscription', function ($q) {
                $q->where('status', SubscriptionStatus::ACTIVE);
            })
            ->get()
            ->map(function ($tenant) {
                return [
                    'name'  => $tenant->company_name,
                    'users' => $tenant->lastSubscription->total_users ?? 0
                ];
            })
            ->sortByDesc('users')
            ->take(10)
            ->values();
    }

    public function getMonthlyRevenue()
    {
        $months = collect();

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);

            $revenue = Subscription::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('price');

            $months->push([
                'month'   => $date->locale('ar')->translatedFormat('M Y'),
                'revenue' => $revenue
            ]);
        }

        return $months;
    }

    public function getSubscriptionStatusDistribution()
    {
        return [
            ['status' => 'نشط', 'count' => Subscription::where('status', SubscriptionStatus::ACTIVE)->count()],
            ['status' => 'معلق', 'count' => Subscription::where('status', SubscriptionStatus::SUSPENDED)->count()],
            ['status' => 'ملغي', 'count' => Subscription::where('status', SubscriptionStatus::CANCELLED)->count()],
            ['status' => 'منتهي', 'count' => Subscription::where('status', SubscriptionStatus::EXPIRED)->count()],
        ];
    }

    public function getUserUsageAnalysis()
    {
        return Subscription::where('status', SubscriptionStatus::ACTIVE)
            ->select('subscription_number', 'total_users', 'used_users')
            ->get()
            ->map(function ($sub) {
                $usagePercentage = $sub->total_users > 0
                    ? round(($sub->used_users / $sub->total_users) * 100, 2)
                    : 0;

                return [
                    'subscription'     => $sub->subscription_number,
                    'total'            => $sub->total_users,
                    'used'             => $sub->used_users,
                    'usage_percentage' => $usagePercentage
                ];
            })
            ->sortByDesc('usage_percentage')
            ->take(10)
            ->values();
    }

    public function getCouponUsageStats()
    {
        return Coupon::select('code', 'name', 'usage_limit', 'used_count', 'discount_type', 'discount_value')
            ->where('is_active', true)
            ->where('used_count', '>', 0)
            ->orderByDesc('used_count')
            ->take(10)
            ->get()
            ->map(function ($coupon) {
                return [
                    'code'  => $coupon->code,
                    'name'  => $coupon->name,
                    'used'  => $coupon->used_count,
                    'limit' => $coupon->usage_limit ?? 'غير محدود'
                ];
            });
    }

    /**
     * توزيع النطاقات حسب الامتداد
     * يعرض عدد النطاقات لكل امتداد
     */
    public function getDomainsByExtension()
    {
        return Domain::select(DB::raw('
                SUBSTRING_INDEX(domain, ".", -1) as extension,
                COUNT(*) as count
            '))
            ->whereNull('deleted_at')
            ->groupBy('extension')
            ->orderByDesc('count')
            ->take(10)
            ->get()
            ->map(function ($item) {
                return [
                    'extension' => '.' . $item->extension,
                    'count'     => $item->count
                ];
            });
    }

    // ==========================================
    // Tables - الجداول التفصيلية
    // ==========================================

    public function getUpcomingRenewals()
    {
        return Subscription::with(['tenant', 'plan'])
            ->where('status', SubscriptionStatus::ACTIVE)
            ->where('auto_renew', true)
            ->whereBetween('next_payment_date', [now(), now()->addDays(30)])
            ->orderBy('next_payment_date')
            ->take(10)
            ->get()
            ->map(function ($sub) {
                return [
                    'tenant'       => $sub->tenant->company_name,
                    'subscription' => $sub->subscription_number,
                    'amount'       => number_format($sub->price, 2),
                    'renewal_date' => $sub->next_payment_date->format('Y-m-d'),
                    'days_until'   => now()->diffInDays($sub->next_payment_date, false)
                ];
            });
    }

    public function getRecentSubscriptions()
    {
        return Subscription::with(['tenant', 'plan'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($sub) {
                return [
                    'tenant'       => $sub->tenant->company_name ?? 'لا يوجد',
                    'subscription' => $sub->subscription_number,
                    'plan'         => $sub->plan->name ?? 'N/A',
                    'price'        => number_format($sub->price, 2),
                    'status'       => $sub->status->value,
                    'created_at'   => $sub->created_at ? $sub->created_at->format('Y-m-d') : 'N/A'
                ];
            });
    }

    public function getExpiringSubscriptions()
    {
        return SubscriptionPeriod::with('subscription.tenant')
            ->where('is_current', true)
            ->whereBetween('period_end', [now(), now()->addDays(30)])
            ->orderBy('period_end')
            ->take(10)
            ->get()
            ->map(function ($period) {
                return [
                    'tenant'       => $period->subscription->tenant->company_name,
                    'subscription' => $period->subscription->subscription_number,
                    'expires_at'   => $period->period_end->format('Y-m-d'),
                    'days_left'    => now()->diffInDays($period->period_end, false)
                ];
            });
    }

    /**
     * جدول أسعار الامتدادات
     * يعرض جميع امتدادات النطاقات وأسعارها
     */
    public function getControlDomainsTable()
    {
        return ControlDomain::where('is_active', true)
            ->orderBy('domain_extension')
            ->get()
            ->map(function ($domain) {
                return [
                    'extension' => $domain->domain_extension,
                    'price'     => number_format($domain->price, 2),
                    'status'    => $domain->is_active ? 'نشط' : 'غير نشط'
                ];
            });
    }

    /**
     * النطاقات المسجلة حديثاً
     * آخر 10 نطاقات تم تسجيلها
     */
    public function getRecentDomains()
    {
        return Domain::with('tenant')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($domain) {
                return [
                    'domain'     => $domain->domain,
                    'tenant'     => $domain->tenant->company_name ?? 'N/A',
                    'type'       => $domain->type == 'registered' ? 'مسجل' : 'مربوط',
                    'is_active'  => $domain->is_active,
                    'created_at' => $domain->created_at->format('Y-m-d')
                ];
            });
    }

    public function getGrowthRate()
    {
        $currentMonth = Tenant::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $lastMonth = Tenant::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $growthRate = $lastMonth > 0
            ? round((($currentMonth - $lastMonth) / $lastMonth) * 100, 2)
            : 0;

        return [
            'current_month' => $currentMonth,
            'last_month'    => $lastMonth,
            'growth_rate'   => $growthRate,
            'trend'         => $growthRate >= 0 ? 'up' : 'down'
        ];
    }
}
