<?php

namespace App\Services\Tenants\Dashboard;

use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Contract\Contract;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use App\Models\Tenants\BusinessCenter\Customers\History\CustomerRelationshipHistory;
use App\Models\Tenants\BusinessCenter\Customers\Task\Task;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\DashboardControl\DashboardControl;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\FinancialCollection\CollectionProcess\CollectionProcessSession;
use App\Models\Tenants\FinancialCollection\Target\CollectionTarget;
use App\Models\Tenants\Settings\Settings;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Temporary business-discovery payloads.
     * Keep the database implementations below intact until the dashboard
     * requirements are confirmed.
     */
    public function getDummyAdminDashboardData(): array
    {
        $filterCompanies = collect([
            (object) ['id' => 1, 'name' => 'شركة تجريبية'],
            (object) ['id' => 2, 'name' => 'شركة نموذجية'],
        ]);
        $filterPortfolios = collect([
            (object) ['id' => 1, 'name' => 'محفظة تجريبية'],
            (object) ['id' => 2, 'name' => 'محفظة نموذجية'],
        ]);
        $filterManagers = collect([
            (object) ['id' => 1, 'name' => 'مسؤول تجريبي'],
            (object) ['id' => 2, 'name' => 'مسؤول نموذجي'],
        ]);

        return [
            'companyStats' => ['total' => 12, 'active' => 9, 'recent' => 3, 'without_delegates' => 1],
            'contractStats' => ['total' => 28, 'active' => 21, 'expiring_soon' => 4, 'expired' => 3],
            'portfolioStats' => ['total' => 16, 'active' => 12, 'inactive' => 4, 'recent' => 2],
            'customerStats' => ['total' => '1.2K', 'active' => '980', 'inactive' => '220', 'recent' => '64', 'with_debt' => '735', 'paid_full_this_month' => '128'],
            'collectionStatsMonth' => ['total' => '246', 'approved' => '184', 'pending' => '42', 'rejected' => '20'],
            'collectionStats' => ['total' => 246, 'pending' => 42, 'approved' => 184, 'rejected' => 20, 'total_amount' => 486000],
            'targetStats' => ['total_targets' => 18, 'active_targets' => 14, 'monthly_targets' => 10, 'yearly_targets' => 4, 'custom_targets' => 4],
            'userPerformance' => [],
            'targetVsAchievement' => [
                ['user_id' => 1, 'user_name' => 'مسؤول تجريبي', 'target_amount' => 100000, 'achieved_amount' => 76000, 'achievement_percentage' => 76, 'remaining_amount' => 24000, 'status' => 'on_track'],
                ['user_id' => 2, 'user_name' => 'مسؤول نموذجي', 'target_amount' => 80000, 'achieved_amount' => 68000, 'achievement_percentage' => 85, 'remaining_amount' => 12000, 'status' => 'excellent'],
            ],
            'timeBasedStats' => ['monthly' => []],
            'quickStats' => ['pending_today' => 8, 'approved_today' => 24, 'total_amount_today' => 42000, 'active_users_today' => 6],
            'expiringCompanies' => 4,
            'pendingCollections' => 42,
            'paymentMethodDistribution' => [],
            'kpiMetrics' => [],
            'collectionSessions' => [],
            'filterCompanies' => $filterCompanies,
            'filterPortfolios' => $filterPortfolios,
            'filterManagers' => $filterManagers,
            'dashboardSetting' => (object) ['compare_effort' => 1, 'daily_tasks' => 1, 'Overdue_tasks' => 1],
            'chartSettings' => ['compare_effort' => 1, 'daily_tasks' => 1, 'Overdue_tasks' => 1],
        ];
    }

    public function getDummyUserDashboardData($user): array
    {
        return [
            'myCollectionStats' => ['total' => 64, 'pending' => 8, 'approved' => 48, 'rejected' => 8, 'total_amount' => 126000],
            'myActiveTargets' => [],
            'myTargetComparison' => [],
            'myTimeStats' => ['weekly' => []],
            'myQuickStats' => [
                'today_collections' => 6,
                'today_amount' => 12000,
                'month_total_count' => 64,
                'month_total_amount' => 126000,
                'month_approved_count' => 48,
                'month_approved_amount' => 98000,
                'month_pending_count' => 8,
                'month_pending_amount' => 18000,
                'month_vs_last' => ['this_month_amount' => 98000, 'this_month_count' => 48, 'last_month_amount' => 84000, 'last_month_count' => 41, 'amount_delta_pct' => 16.7, 'count_delta_pct' => 17.1],
            ],
            'targetProgress' => [],
            'performanceComparison' => [],
            'myWorkStats' => ['today_tasks' => 9, 'overdue_tasks' => 3],
            'comparisonData' => ['top_collections' => []],
            'myCustomersData' => ['assigned' => ['count' => 7], 'transferred' => ['count' => 3]],
            'user' => $user,
            'welcomeMessage' => 'مرحباً بك في لوحة التحكم',
            'dashboardSetting' => (object) ['compare_effort' => 1, 'daily_tasks' => 1, 'Overdue_tasks' => 1],
        ];
    }

    public function getDummyExcellenceDashboardData(): array
    {
        return [
            'topPerformers' => [
                ['rank' => 1, 'user_id' => 1, 'user_name' => 'مسؤول تجريبي', 'employee_number' => 'EMP-001', 'image_url' => asset('img/logo.svg'), 'total_amount' => 76000, 'collections_count' => 24, 'follows_count' => 18],
                ['rank' => 2, 'user_id' => 2, 'user_name' => 'مسؤول نموذجي', 'employee_number' => 'EMP-002', 'image_url' => asset('img/logo.svg'), 'total_amount' => 68000, 'collections_count' => 21, 'follows_count' => 15],
            ],
            'performanceDistribution' => [],
            'performanceTrend' => [],
            'teamStats' => [],
        ];
    }

    public function getDummyPerformanceByPeriod(): array
    {
        return [];
    }

    public function getDummyComparisonData(): array
    {
        return ['top_collections' => [], 'top_follows' => []];
    }

    public function getDummyStatisticsData(): array
    {
        return ['followups' => 18, 'collections' => 32, 'total_collected' => 42000];
    }

    public function getDummyApprovalsData(): array
    {
        return ['pending' => 12, 'approved' => 26, 'rejected' => 3];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Admin Dashboard Data
    |--------------------------------------------------------------------------
    */
    public function getAdminDashboardData(): array
    {
        $userIds  = $this->getAccessibleUserIds();
        $user     = Auth::user();
        // $cacheKey = 'admin_dashboard_' . $user->id . '_' . md5(json_encode($userIds));
        $cacheKey = 'admin_dashboard_' . tenant('id') . '_' . $user->id . '_' . md5(json_encode($userIds));


        $cached = Cache::remember($cacheKey, 300, function () use ($userIds) {
            return [
                'companyStats'              => $this->getCompanyStatistics(),
                'contractStats'             => $this->getContractStatistics(),
                'portfolioStats'            => $this->getPortfolioStatistics(),
                'customerStats'             => $this->getCustomerStatistics($userIds),
                'collectionStatsMonth'      => $this->getMonthlyCollectionStatistics($userIds),
                'collectionStats'           => $this->getCollectionStatistics($userIds),
                'targetStats'               => $this->getTargetStatistics($userIds),
                'userPerformance'           => $this->getUserCollectionPerformance($userIds),
                'targetVsAchievement'       => $this->getTargetVsAchievementComparison($userIds),
                'timeBasedStats'            => $this->getTimeBasedCollectionStats($userIds),
                'quickStats'                => $this->getQuickStats($userIds),
                'expiringCompanies'         => $this->getExpiringCompaniesCount(),
                'pendingCollections'        => $this->getPendingCollections($userIds),
                'paymentMethodDistribution' => $this->getPaymentMethodDistribution($userIds),
                'kpiMetrics'                => $this->getKPIMetrics($userIds),
                'collectionSessions'        => $this->getCollectionSessionsData(null, null, $userIds),
            ];
        });

        // البيانات الثابتة (لا تحتاج cache منفصلة)
        $cached['filterCompanies']  = Company::select('id', 'name')->orderBy('name')->get();
        $cached['filterPortfolios'] = Portfolio::select('id', 'name')->orderBy('name')->get();
        $cached['filterManagers']   = User::whereIn('id', $userIds ?? User::pluck('id'))->select('id', 'name')->orderBy('name')->get();
        $settings = Settings::first();
        $cached['dashboardSetting'] = $settings;
        $cached['chartSettings']    = [
            'compare_effort' => $settings ? (int)$settings->compare_effort : 1,
            'daily_tasks'    => $settings ? (int)$settings->daily_tasks : 1,
            'Overdue_tasks'  => $settings ? (int)$settings->Overdue_tasks : 1,
        ];

        return $cached;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Accessible User IDs
    |--------------------------------------------------------------------------
    */
    private function getAccessibleUserIds(): ?array
    {
        $user = Auth::user();

        if ($user->can('dashboard.view') && !$user->canManageTeam()) {
            return null;
        }

        if ($user->canManageTeam()) {
            return $user->teamMemberIds();
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Dashboard Data
    |--------------------------------------------------------------------------
    */
    public function getUserDashboardData(): array
    {
        $user     = Auth::user();
        // $cacheKey = 'user_dashboard_' . $user->id;
        $cacheKey = 'user_dashboard_' . tenant('id') . '_' . $user->id;


        $cached = Cache::remember($cacheKey, 300, function () use ($user) {
            return [
                'myCollectionStats'     => $this->getMyCollectionStatistics(),
                'myActiveTargets'       => $this->getMyActiveTargets($user->id),
                'myTargetComparison'    => $this->getMyTargetComparison($user->id),
                'myTimeStats'           => $this->getMyTimeBasedStats($user->id),
                'myQuickStats'          => $this->getMyQuickStats($user->id),
                'targetProgress'        => $this->getMyTargetProgress($user->id),
                'performanceComparison' => $this->getMyPerformanceComparison($user->id),
                'myWorkStats'           => $this->getMyWorkStatistics($user->id),
                'comparisonData'        => $this->getUserComparisonData($user->id, 'month', 'collections'),
                'myCustomersData'       => $this->getMyNewlyAssignedAndTransferredCustomers($user->id),
            ];
        });

        $cached['user']             = $user;
        $cached['welcomeMessage']   = $this->getWelcomeMessage($user);
        $cached['dashboardSetting'] = Settings::first();

        return $cached;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Company Statistics
    |--------------------------------------------------------------------------
    */
    private function getCompanyStatistics(): array
    {
        return [
            'total'             => Company::count(),
            'active'            => Company::where('status', 'active')->count(),
            'recent'            => Company::where('created_at', '>=', now()->subDays(30))->count(),
            'without_delegates' => Company::doesntHave('authorizedPeople')->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    private function getContractStatistics(): array
    {
        if (!Schema::hasTable('contracts')) {
            return [
                'total'         => 0,
                'active'        => 0,
                'expiring_soon' => 0,
                'expired'       => 0,
            ];
        }

        $today = now()->toDateString();

        return [
            'total'         => Contract::count(),
            'active'        => Contract::where('status', 'active')->count(),
            'expiring_soon' => Contract::expiringSoon(30)->count(),
            'expired'       => Contract::where(function ($q) use ($today) {
                $q->where('status', 'expired')
                    ->orWhere(function ($q2) use ($today) {
                        $q2->where('status', 'active')
                            ->whereDate('end_date', '<', $today);
                    });
            })->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Portfolio Statistics
    |--------------------------------------------------------------------------
    */
    private function getPortfolioStatistics(): array
    {
        return [
            'total' =>    Portfolio::count(),
            'active' =>   Portfolio::where('status', 'active')->count(),
            'inactive' => Portfolio::where('status', 'inactive')->count(),
            'recent' =>   Portfolio::where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Customer Statistics
    |--------------------------------------------------------------------------
    */
    private function getCustomerStatistics(?array $userIds = null): array
    {
        $query = Customer::query();

        if ($userIds !== null) {
            $query->whereIn('relationship_manager_id', $userIds);
        }

        $total    = (clone $query)->count();
        $active   = (clone $query)->whereHas('customerStatus', fn($q) => $q->where('status', 'active'))->count();
        $inactive = (clone $query)->whereHas('customerStatus', fn($q) => $q->where('status', 'inactive'))->count();
        $recent   = (clone $query)->where('created_at', '>=', now()->subDays(30))->count();

        // Derived table بدلاً من correlated subquery لكل صف — أسرع بكثير مع البيانات الكبيرة
        $netDebtSub = DB::raw('(SELECT customer_id,
            SUM(CASE WHEN type = "debit"  THEN amount ELSE 0 END) -
            SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END) AS net_debt
            FROM customer_transactions GROUP BY customer_id) AS ct');

        $withDebt = (clone $query)
            ->join($netDebtSub, 'ct.customer_id', '=', 'customers.id')
            ->where('ct.net_debt', '>', 0)
            ->count('customers.id');

        $start = now()->startOfMonth()->toDateString();
        $end   = now()->endOfMonth()->toDateString();

        $paidFullThisMonth = (clone $query)
            ->leftJoin($netDebtSub, 'ct.customer_id', '=', 'customers.id')
            ->whereRaw('COALESCE(ct.net_debt, 0) = 0')
            ->whereExists(function ($q) use ($start, $end) {
                $q->select(DB::raw(1))
                    ->from('collections as c')
                    ->whereColumn('c.customer_id', 'customers.id')
                    ->where('c.status', 'approved')
                    ->whereBetween('c.collection_date', [$start, $end]);
            })->count('customers.id');

        $fmt = function ($n) {
            if ($n >= 1000000000) return rtrim(rtrim(number_format($n / 1_000_000_000, 1), '0'), '.') . 'B';
            if ($n >= 1000000)    return rtrim(rtrim(number_format($n / 1_000_000, 1), '0'), '.') . 'M';
            if ($n >= 1000)       return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K';
            return (string)$n;
        };

        return [
            'total'                => $fmt($total),
            'active'               => $fmt($active),
            'inactive'             => $fmt($inactive),
            'recent'               => $fmt($recent),
            'with_debt'            => $fmt($withDebt),
            'paid_full_this_month' => $fmt($paidFullThisMonth),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Get Monthly Collection Statistics
    |--------------------------------------------------------------------------
    */
    private function getMonthlyCollectionStatistics(?array $userIds = null): array
    {
        $start = now()->startOfMonth()->toDateString();
        $end   = now()->endOfMonth()->toDateString();

        $query = Collection::whereBetween('collection_date', [$start, $end]);

        if ($userIds !== null) {
            $query->whereIn('added_by', $userIds);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = "pending"  THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected
        ')->first();

        $fmt = function ($n) {
            if ($n >= 1000000000) return rtrim(rtrim(number_format($n / 1_000_000_000, 1), '0'), '.') . 'B';
            if ($n >= 1000000)    return rtrim(rtrim(number_format($n / 1_000_000, 1), '0'), '.') . 'M';
            if ($n >= 1000)       return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K';
            return (string)$n;
        };

        return [
            'total'    => $fmt((int)($stats->total    ?? 0)),
            'approved' => $fmt((int)($stats->approved ?? 0)),
            'pending'  => $fmt((int)($stats->pending  ?? 0)),
            'rejected' => $fmt((int)($stats->rejected ?? 0)),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Collection Statistics
    |--------------------------------------------------------------------------
    */
    private function getCollectionStatistics(?array $userIds = null): array
    {
        $query = Collection::query();

        if ($userIds !== null) {
            $query->whereIn('added_by', $userIds);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "pending"  THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
            COALESCE(SUM(CASE WHEN status = "approved" THEN collected_amount ELSE 0 END), 0) as total_amount
        ')->first();

        return [
            'total'        => (int)($stats->total        ?? 0),
            'pending'      => (int)($stats->pending       ?? 0),
            'approved'     => (int)($stats->approved      ?? 0),
            'rejected'     => (int)($stats->rejected      ?? 0),
            'total_amount' => (float)($stats->total_amount ?? 0),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Target Statistics
    |--------------------------------------------------------------------------
    */
    private function getTargetStatistics(?array $userIds = null): array
    {
        $query = CollectionTarget::query();

        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_targets,
            SUM(CASE WHEN status = "active"           THEN 1 ELSE 0 END) as active_targets,
            SUM(CASE WHEN target_type = "monthly"     THEN 1 ELSE 0 END) as monthly_targets,
            SUM(CASE WHEN target_type = "yearly"      THEN 1 ELSE 0 END) as yearly_targets,
            SUM(CASE WHEN target_type = "custom"      THEN 1 ELSE 0 END) as custom_targets
        ')->first();

        return [
            'total_targets'   => (int)($stats->total_targets   ?? 0),
            'active_targets'  => (int)($stats->active_targets  ?? 0),
            'monthly_targets' => (int)($stats->monthly_targets ?? 0),
            'yearly_targets'  => (int)($stats->yearly_targets  ?? 0),
            'custom_targets'  => (int)($stats->custom_targets  ?? 0),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Collection Performance
    |--------------------------------------------------------------------------
    */
    private function getUserCollectionPerformance(?array $userIds = null): array
    {
        $query = Collection::select([
            'added_by',
            DB::raw('COUNT(*) as total_collections'),
            DB::raw('SUM(CASE WHEN status = "approved" THEN collected_amount ELSE 0 END) as total_amount'),
            DB::raw('COUNT(CASE WHEN status = "approved" THEN 1 END) as approved_collections'),
            DB::raw('COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_collections'),
            DB::raw('COUNT(CASE WHEN status = "rejected" THEN 1 END) as rejected_collections'),
        ])
            ->with(['addedBy' => function ($query) {
                $query->select('id', 'name', 'employee_number')->accessible();
            }])
            ->groupBy('added_by')
            ->orderByDesc('total_amount')
            ->take(20);

        if ($userIds !== null) {
            $query->whereIn('added_by', $userIds);
        }

        return $query->get()
            ->map(function ($item) {
                $successRate = $item->total_collections > 0 ?
                    round(($item->approved_collections / $item->total_collections) * 100, 2) : 0;

                return [
                    'user_name' => $item->addedBy->name ?? 'مستخدم محذوف',
                    'employee_number' => $item->addedBy->employee_number ?? 'غير محدد',
                    'total_collections' => $item->total_collections,
                    'total_amount' => $item->total_amount,
                    'approved_collections' => $item->approved_collections,
                    'pending_collections' => $item->pending_collections,
                    'rejected_collections' => $item->rejected_collections,
                    'success_rate' => $successRate
                ];
            })->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Target vs Achievement Comparison
    |--------------------------------------------------------------------------
    */
    private function getTargetVsAchievementComparison(?array $userIds = null): array
    {
        $currentMonth = now()->month;
        $currentYear  = now()->year;

        $query = CollectionTarget::where('target_type', 'monthly')
            ->where('target_month', $currentMonth)
            ->where('target_year', $currentYear)
            ->where('status', 'active')
            ->with('user:id,name');

        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        $monthlyTargets = $query->get();

        if ($monthlyTargets->isEmpty()) {
            return [];
        }

        $targetUserIds = $monthlyTargets->pluck('user_id')->all();

        // استعلام واحد بدلاً من N استعلام
        $collectionsMap = Collection::where('status', 'approved')
            ->whereMonth('collection_date', $currentMonth)
            ->whereYear('collection_date', $currentYear)
            ->whereIn('added_by', $targetUserIds)
            ->selectRaw('added_by, COALESCE(SUM(collected_amount), 0) as total')
            ->groupBy('added_by')
            ->get()
            ->keyBy('added_by');

        $comparison = [];
        foreach ($monthlyTargets as $target) {
            $achieved = (float)($collectionsMap->get($target->user_id)?->total ?? 0);

            $achievementPercentage = $target->target_amount > 0
                ? round(($achieved / $target->target_amount) * 100, 2)
                : 0;

            $comparison[] = [
                'user_id'                => $target->user_id,
                'user_name'              => $target->user->name,
                'target_amount'          => $target->target_amount,
                'achieved_amount'        => $achieved,
                'achievement_percentage' => $achievementPercentage,
                'remaining_amount'       => max(0, $target->target_amount - $achieved),
                'status'                 => $this->getAchievementStatus($achievementPercentage),
            ];
        }

        return $comparison;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Time Based Collection Stats
    |--------------------------------------------------------------------------
    */
    private function getTimeBasedCollectionStats(?array $userIds = null): array
    {
        $startDate = now()->subMonths(11)->startOfMonth()->toDateString();
        $endDate   = now()->endOfMonth()->toDateString();

        $query = Collection::where('status', 'approved')
            ->whereBetween('collection_date', [$startDate, $endDate])
            ->selectRaw('YEAR(collection_date) as yr, MONTH(collection_date) as mo, COUNT(*) as count, SUM(collected_amount) as amount')
            ->groupByRaw('YEAR(collection_date), MONTH(collection_date)');

        if ($userIds !== null) {
            $query->whereIn('added_by', $userIds);
        }

        $results = $query->get()->keyBy(fn($r) => $r->yr . '-' . $r->mo);

        $monthlyStats = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key  = $date->year . '-' . $date->month;
            $row  = $results->get($key);

            $monthlyStats[] = [
                'month'    => $date->format('M Y'),
                'month_ar' => $date->locale('ar')->translatedFormat('M Y'),
                'count'    => $row ? (int)$row->count   : 0,
                'amount'   => $row ? (float)$row->amount : 0,
            ];
        }

        return ['monthly' => $monthlyStats];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Quick Stats
    |--------------------------------------------------------------------------
    */
    private function getQuickStats(?array $userIds = null): array
    {
        $todayDate = today()->toDateString();

        $query = Collection::query();
        if ($userIds !== null) {
            $query->whereIn('added_by', $userIds);
        }

        $stats = (clone $query)->selectRaw('
            SUM(CASE WHEN status = "pending"  AND DATE(created_at)     = ? THEN 1 ELSE 0 END) as pending_today,
            SUM(CASE WHEN status = "approved" AND DATE(approved_at)    = ? THEN 1 ELSE 0 END) as approved_today,
            COALESCE(SUM(CASE WHEN status = "approved" AND DATE(collection_date) = ? THEN collected_amount ELSE 0 END), 0) as total_amount_today
        ', [$todayDate, $todayDate, $todayDate])->first();

        $activeUsers = (clone $query)->whereDate('created_at', $todayDate)
            ->distinct('added_by')
            ->count('added_by');

        return [
            'pending_today'      => (int)($stats->pending_today      ?? 0),
            'approved_today'     => (int)($stats->approved_today     ?? 0),
            'total_amount_today' => (float)($stats->total_amount_today ?? 0),
            'active_users_today' => $activeUsers,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Expiring Companies Count
    |--------------------------------------------------------------------------
    */
    private function getExpiringCompaniesCount(): int
    {
        return 120;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Pending Collections
    |--------------------------------------------------------------------------
    */
    private function getPendingCollections(?array $userIds = null)
    {
        $query = Collection::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(20);

        if ($userIds !== null) {
            $query->whereIn('added_by', $userIds);
        }

        return $query->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Payment Method Distribution
    |--------------------------------------------------------------------------
    */
    private function getPaymentMethodDistribution(?array $userIds = null): array
    {
        $query = Collection::join('settings_payment_methods', 'collections.payment_method_id', '=', 'settings_payment_methods.id')
            ->where('collections.status', 'approved')
            ->selectRaw('
                settings_payment_methods.name as payment_method,
                COUNT(*) as count,
                SUM(collections.collected_amount) as total_amount
            ')
            ->groupBy('settings_payment_methods.id', 'settings_payment_methods.name')
            ->orderByDesc('total_amount');

        if ($userIds !== null) {
            $query->whereIn('collections.added_by', $userIds);
        }

        return $query->get()->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Get KPI Metrics
    |--------------------------------------------------------------------------
    */
    private function getKPIMetrics(?array $userIds = null): array
    {
        $currentMonth = now();
        $lastMonth    = now()->subMonth();

        $query = Collection::where('status', 'approved')
            ->where(function ($q) use ($currentMonth, $lastMonth) {
                $q->where(function ($q2) use ($currentMonth) {
                    $q2->whereMonth('collection_date', $currentMonth->month)
                        ->whereYear('collection_date', $currentMonth->year);
                })->orWhere(function ($q2) use ($lastMonth) {
                    $q2->whereMonth('collection_date', $lastMonth->month)
                        ->whereYear('collection_date', $lastMonth->year);
                });
            })
            ->selectRaw('
                SUM(CASE WHEN MONTH(collection_date)=? AND YEAR(collection_date)=? THEN 1 ELSE 0 END)                        as current_count,
                COALESCE(SUM(CASE WHEN MONTH(collection_date)=? AND YEAR(collection_date)=? THEN collected_amount ELSE 0 END), 0) as current_amount,
                SUM(CASE WHEN MONTH(collection_date)=? AND YEAR(collection_date)=? THEN 1 ELSE 0 END)                        as last_count,
                COALESCE(SUM(CASE WHEN MONTH(collection_date)=? AND YEAR(collection_date)=? THEN collected_amount ELSE 0 END), 0) as last_amount
            ', [
                $currentMonth->month,
                $currentMonth->year,
                $currentMonth->month,
                $currentMonth->year,
                $lastMonth->month,
                $lastMonth->year,
                $lastMonth->month,
                $lastMonth->year,
            ]);

        if ($userIds !== null) {
            $query->whereIn('added_by', $userIds);
        }

        $stats = $query->first();

        $currentCount  = (int)($stats->current_count   ?? 0);
        $currentAmount = (float)($stats->current_amount ?? 0);
        $lastCount     = (int)($stats->last_count       ?? 0);
        $lastAmount    = (float)($stats->last_amount    ?? 0);

        $amountGrowth = $lastAmount > 0
            ? round((($currentAmount - $lastAmount) / $lastAmount) * 100, 2)
            : 0;

        $countGrowth = $lastCount > 0
            ? round((($currentCount - $lastCount) / $lastCount) * 100, 2)
            : 0;

        return [
            'current_month_amount'     => $currentAmount,
            'current_month_count'      => $currentCount,
            'last_month_amount'        => $lastAmount,
            'last_month_count'         => $lastCount,
            'amount_growth_percentage' => $amountGrowth,
            'count_growth_percentage'  => $countGrowth,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Collection Statistics
    |--------------------------------------------------------------------------
    */
    private function getMyCollectionStatistics(): array
    {
        $userId = Auth::id();

        $stats = Collection::where('added_by', $userId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "pending"  THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected,
                COALESCE(SUM(CASE WHEN status = "approved" THEN collected_amount ELSE 0 END), 0) as total_amount
            ')
            ->first();

        return [
            'total'        => (int)($stats->total        ?? 0),
            'pending'      => (int)($stats->pending       ?? 0),
            'approved'     => (int)($stats->approved      ?? 0),
            'rejected'     => (int)($stats->rejected      ?? 0),
            'total_amount' => (float)($stats->total_amount ?? 0),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Active Targets
    |--------------------------------------------------------------------------
    */
    private function getMyActiveTargets(int $userId)
    {
        return CollectionTarget::where('user_id', $userId)
            ->where('status', 'active')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Target Comparison
    |--------------------------------------------------------------------------
    */
    private function getMyTargetComparison(int $userId): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $monthlyTarget = CollectionTarget::where('user_id', $userId)
            ->where('target_type', 'monthly')
            ->where('target_month', $currentMonth)
            ->where('target_year', $currentYear)
            ->where('status', 'active')
            ->first();

        $yearlyTarget = CollectionTarget::where('user_id', $userId)
            ->where('target_type', 'yearly')
            ->where('target_year', $currentYear)
            ->where('status', 'active')
            ->first();

        $comparison = [];

        if ($monthlyTarget) {
            $monthlyCollections = Collection::where('added_by', $userId)
                ->where('status', 'approved')
                ->whereMonth('collection_date', $currentMonth)
                ->whereYear('collection_date', $currentYear)
                ->sum('collected_amount');

            $monthlyPercentage = $monthlyTarget->target_amount > 0 ?
                round(($monthlyCollections / $monthlyTarget->target_amount) * 100, 2) : 0;

            $comparison['monthly'] = [
                'target_amount' => $monthlyTarget->target_amount,
                'achieved_amount' => $monthlyCollections,
                'percentage' => $monthlyPercentage,
                'remaining_amount' => max(0, $monthlyTarget->target_amount - $monthlyCollections),
                'status' => $this->getAchievementStatus($monthlyPercentage),
            ];
        }

        if ($yearlyTarget) {
            $yearlyCollections = Collection::where('added_by', $userId)
                ->where('status', 'approved')
                ->whereYear('collection_date', $currentYear)
                ->sum('collected_amount');

            $yearlyPercentage = $yearlyTarget->target_amount > 0 ?
                round(($yearlyCollections / $yearlyTarget->target_amount) * 100, 2) : 0;

            $comparison['yearly'] = [
                'target_amount' => $yearlyTarget->target_amount,
                'achieved_amount' => $yearlyCollections,
                'percentage' => $yearlyPercentage,
                'remaining_amount' => max(0, $yearlyTarget->target_amount - $yearlyCollections),
                'status' => $this->getAchievementStatus($yearlyPercentage),
            ];
        }

        return $comparison;
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Time Based Stats
    |--------------------------------------------------------------------------
    */
    private function getMyTimeBasedStats(int $userId): array
    {
        $startDate = now()->subDays(6)->toDateString();
        $endDate   = now()->toDateString();

        $results = Collection::where('added_by', $userId)
            ->where('status', 'approved')
            ->whereBetween('collection_date', [$startDate, $endDate])
            ->selectRaw('DATE(collection_date) as day, COUNT(*) as count, SUM(collected_amount) as amount')
            ->groupByRaw('DATE(collection_date)')
            ->get()
            ->keyBy('day');

        $weeklyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $row  = $results->get($date->toDateString());

            $weeklyStats[] = [
                'date'     => $date->format('Y-m-d'),
                'day_name' => $date->locale('ar')->dayName,
                'count'    => $row ? (int)$row->count   : 0,
                'amount'   => $row ? (float)$row->amount : 0,
            ];
        }

        return ['weekly' => $weeklyStats];
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Quick Stats
    |--------------------------------------------------------------------------
    */
    private function getMyQuickStats(int $userId): array
    {
        $now        = now();
        $todayDate  = $now->toDateString();
        $startMonth = $now->copy()->startOfMonth();
        $endMonth   = $now->copy()->endOfMonth();
        $lastMonth  = $now->copy()->subMonth();
        $lastStart  = $lastMonth->copy()->startOfMonth();
        $lastEnd    = $lastMonth->copy()->endOfMonth();

        // استعلام واحد لكل إحصائيات الشهر الحالي بدلاً من 6 استعلامات
        $monthStats = Collection::where('added_by', $userId)
            ->whereBetween('collection_date', [$startMonth, $endMonth])
            ->selectRaw('
                COUNT(*) as total_count,
                COALESCE(SUM(collected_amount), 0) as total_amount,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
                COALESCE(SUM(CASE WHEN status = "approved" THEN collected_amount ELSE 0 END), 0) as approved_amount,
                SUM(CASE WHEN status = "pending"  THEN 1 ELSE 0 END) as pending_count,
                COALESCE(SUM(CASE WHEN status = "pending"  THEN collected_amount ELSE 0 END), 0) as pending_amount
            ')
            ->first();

        // استعلام واحد للشهر الماضي بدلاً من 2
        $lastMonthStats = Collection::where('added_by', $userId)
            ->where('status', 'approved')
            ->whereBetween('collection_date', [$lastStart, $lastEnd])
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(collected_amount), 0) as amount')
            ->first();

        // استعلام واحد لإحصائيات اليوم بدلاً من 2
        $todayStats = Collection::where('added_by', $userId)
            ->selectRaw('
                SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as today_count,
                COALESCE(SUM(CASE WHEN status = "approved" AND DATE(collection_date) = ? THEN collected_amount ELSE 0 END), 0) as today_amount
            ', [$todayDate, $todayDate])
            ->first();

        $monthApprovedAmount = (float)($monthStats->approved_amount ?? 0);
        $monthApprovedCount  = (int)($monthStats->approved_count    ?? 0);
        $lastMonthAmount     = (float)($lastMonthStats->amount       ?? 0);
        $lastMonthCount      = (int)($lastMonthStats->cnt            ?? 0);

        $amountDeltaPct = $lastMonthAmount > 0
            ? round((($monthApprovedAmount - $lastMonthAmount) / $lastMonthAmount) * 100, 1)
            : ($monthApprovedAmount > 0 ? 100.0 : 0.0);

        $countDeltaPct = $lastMonthCount > 0
            ? round((($monthApprovedCount - $lastMonthCount) / $lastMonthCount) * 100, 1)
            : ($monthApprovedCount > 0 ? 100.0 : 0.0);

        return [
            'today_collections'     => (int)($todayStats->today_count    ?? 0),
            'today_amount'          => (float)($todayStats->today_amount  ?? 0),
            'month_total_count'     => (int)($monthStats->total_count     ?? 0),
            'month_total_amount'    => (float)($monthStats->total_amount  ?? 0),
            'month_approved_count'  => $monthApprovedCount,
            'month_approved_amount' => $monthApprovedAmount,
            'month_pending_count'   => (int)($monthStats->pending_count   ?? 0),
            'month_pending_amount'  => (float)($monthStats->pending_amount ?? 0),
            'month_vs_last' => [
                'this_month_amount' => $monthApprovedAmount,
                'this_month_count'  => $monthApprovedCount,
                'last_month_amount' => $lastMonthAmount,
                'last_month_count'  => $lastMonthCount,
                'amount_delta_pct'  => $amountDeltaPct,
                'count_delta_pct'   => $countDeltaPct,
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Target Progress
    |--------------------------------------------------------------------------
    */
    private function getMyTargetProgress(int $userId): array
    {
        $targets = CollectionTarget::where('user_id', $userId)
            ->where('status', 'active')
            ->get();

        $progress = [];

        foreach ($targets as $target) {
            $achieved = 0;

            switch ($target->target_type) {
                case 'monthly':
                    $achieved = Collection::where('added_by', $userId)
                        ->where('status', 'approved')
                        ->whereMonth('collection_date', $target->target_month)
                        ->whereYear('collection_date', $target->target_year)
                        ->sum('collected_amount');
                    break;

                case 'yearly':
                    $achieved = Collection::where('added_by', $userId)
                        ->where('status', 'approved')
                        ->whereYear('collection_date', $target->target_year)
                        ->sum('collected_amount');
                    break;

                case 'custom':
                    $achieved = Collection::where('added_by', $userId)
                        ->where('status', 'approved')
                        ->whereBetween('collection_date', [
                            $target->custom_start_date,
                            $target->custom_end_date
                        ])
                        ->sum('collected_amount');
                    break;
            }

            $percentage = $target->target_amount > 0 ?
                round(($achieved / $target->target_amount) * 100, 2) : 0;

            $progress[] = [
                'target_id' => $target->id,
                'target_type' => $target->target_type,
                'target_type_label' => $target->target_type_label,
                'target_period' => $target->target_period_formatted,
                'target_amount' => $target->target_amount,
                'achieved_amount' => $achieved,
                'percentage' => $percentage,
                'status' => $this->getAchievementStatus($percentage)
            ];
        }

        return $progress;
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Performance Comparison
    |--------------------------------------------------------------------------
    */
    private function getMyPerformanceComparison(int $userId): array
    {
        $currentMonth = now();

        $userStats = Collection::where('added_by', $userId)
            ->where('status', 'approved')
            ->whereMonth('collection_date', $currentMonth->month)
            ->whereYear('collection_date', $currentMonth->year)
            ->selectRaw('COUNT(*) as count, SUM(collected_amount) as amount')
            ->first();

        $generalAverage = Collection::where('status', 'approved')
            ->whereMonth('collection_date', $currentMonth->month)
            ->whereYear('collection_date', $currentMonth->year)
            ->selectRaw('
                COUNT(*) / COUNT(DISTINCT added_by) as avg_count_per_user,
                SUM(collected_amount) / COUNT(DISTINCT added_by) as avg_amount_per_user
            ')
            ->first();

        return [
            'user_collections_count' => $userStats->count ?? 0,
            'user_total_amount' => $userStats->amount ?? 0,
            'general_avg_count' => round($generalAverage->avg_count_per_user ?? 0, 2),
            'general_avg_amount' => round($generalAverage->avg_amount_per_user ?? 0, 2),
            'performance_vs_average' => [
                'count_ratio' => $generalAverage->avg_count_per_user > 0 ?
                    round((($userStats->count ?? 0) / $generalAverage->avg_count_per_user) * 100, 2) : 0,
                'amount_ratio' => $generalAverage->avg_amount_per_user > 0 ?
                    round((($userStats->amount ?? 0) / $generalAverage->avg_amount_per_user) * 100, 2) : 0
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Welcome Message
    |--------------------------------------------------------------------------
    */
    private function getWelcomeMessage(User $user): string
    {
        $hour = now()->hour;
        $greeting = '';

        if ($hour < 12) {
            $greeting = 'صباح الخير ';
        } else {
            $greeting = 'مساء الخير ';
        }

        $messages = [
            'نتمنى لك يوماً مثمراً وناجحاً في عملك',
            'استمر في التميز وتحقيق أهدافك',
            'عملك الدؤوب هو سر نجاحنا جميعاً',
            'كل يوم جديد فرصة لتحقيق إنجازات أكبر'
        ];

        return $greeting . '، ' . $user->name . ' ! ' . $messages[array_rand($messages)];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Achievement Status
    |--------------------------------------------------------------------------
    */
    private function getAchievementStatus(float $percentage): array
    {
        if ($percentage >= 100) {
            return [
                'status' => 'completed',
                'label' => 'مكتمل',
                'class' => 'success',
                'color' => '#28a745'
            ];
        } elseif ($percentage >= 80) {
            return [
                'status' => 'excellent',
                'label' => 'ممتاز',
                'class' => 'primary',
                'color' => '#007bff'
            ];
        } elseif ($percentage >= 60) {
            return [
                'status' => 'good',
                'label' => 'جيد',
                'class' => 'info',
                'color' => '#17a2b8'
            ];
        } elseif ($percentage >= 40) {
            return [
                'status' => 'moderate',
                'label' => 'متوسط',
                'class' => 'warning',
                'color' => '#ffc107'
            ];
        } else {
            return [
                'status' => 'low',
                'label' => 'ضعيف',
                'class' => 'danger',
                'color' => '#dc3545'
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Performance By Period
    |--------------------------------------------------------------------------
    */
    public function getPerformanceByPeriod(string $period): array
    {
        $query = Collection::select([
            'added_by',
            DB::raw('COUNT(*) as total_collections'),
            DB::raw('SUM(CASE WHEN status = "approved" THEN collected_amount ELSE 0 END) as total_amount'),
        ])
            ->with(['addedBy' => function ($query) {
                $query->select('id', 'name', 'employee_number')->accessible();
            }])
            ->where('status', 'approved');

        switch ($period) {
            case 'today':
                $query->whereDate('collection_date', today()->toDateString());
                break;
            case 'week':
                $query->whereBetween('collection_date', [
                    now()->startOfWeek()->toDateString(),
                    now()->endOfWeek()->toDateString()
                ]);
                break;
            case 'month':
                $query->whereMonth('collection_date', now()->month)
                    ->whereYear('collection_date', now()->year);
                break;
            case 'year':
            default:
                $query->whereYear('collection_date', now()->year);
                break;
        }

        return $query->groupBy('added_by')
            ->having('total_collections', '>', 0)
            ->orderByDesc('total_amount')
            ->take(20)
            ->get()
            ->map(function ($item) {
                return [
                    'user_name' => $item->addedBy->name ?? 'مستخدم محذوف',
                    'employee_number' => $item->addedBy->employee_number ?? 'غير محدد',
                    'total_collections' => (int)$item->total_collections,
                    'total_amount' => (float)$item->total_amount,
                ];
            })->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Excellence Dashboard Data
    |--------------------------------------------------------------------------
    */
    public function getExcellenceDashboardData(string $period = 'month'): array
    {
        $userIds      = $this->getAccessibleUserIds();
        $topPerformers = $this->getTopPerformers($period, $userIds);

        return [
            'topPerformers'           => $topPerformers,
            'performanceDistribution' => $this->getPerformanceDistribution($period, $userIds),
            'performanceTrend'        => $this->getPerformanceTrend($period, $userIds, $topPerformers),
            'teamStats'               => $this->getTeamStats($period, $userIds),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Top Performers (محسّن لدعم التحصيلات + المتابعات)
    |--------------------------------------------------------------------------
    */
    private function getTopPerformers(string $period, ?array $userIds = null): array
    {
        $dateFilter = $this->getCollectionDateFilter($period);
        $followDateFilter = $this->getFollowDateFilter($period);

        $collectionsQuery = Collection::select([
            'added_by',
            DB::raw('SUM(CASE WHEN status = "approved" THEN collected_amount ELSE 0 END) as total_amount'),
            DB::raw('COUNT(CASE WHEN status = "approved" THEN 1 END) as collections_count'),
        ])
            ->where('status', 'approved');

        if ($period === 'today') {
            $collectionsQuery->whereDate('collection_date', $dateFilter['start']);
        } else {
            $collectionsQuery->whereBetween('collection_date', [$dateFilter['start'], $dateFilter['end']]);
        }

        if ($userIds !== null) {
            $collectionsQuery->whereIn('added_by', $userIds);
        }

        $collectionsData = $collectionsQuery
            ->groupBy('added_by')
            ->having('total_amount', '>', 0)
            ->get()
            ->keyBy('added_by');

        $followsQuery = CustomerFollow::select([
            'created_by',
            DB::raw('COUNT(*) as follows_count')
        ]);

        if ($period === 'today') {
            $followsQuery->whereDate('created_at', $followDateFilter['start']);
        } else {
            $followsQuery->whereBetween('created_at', [$followDateFilter['start'], $followDateFilter['end']]);
        }

        if ($userIds !== null) {
            $followsQuery->whereIn('created_by', $userIds);
        }

        $followsData = $followsQuery
            ->groupBy('created_by')
            ->having('follows_count', '>', 0)
            ->get()
            ->keyBy('created_by');

        $allUserIds = collect($collectionsData->keys())
            ->merge($followsData->keys())
            ->unique()
            ->values();

        $combinedData = $allUserIds->map(function ($userId) use ($collectionsData, $followsData) {
            $collection = $collectionsData->get($userId);
            $follow = $followsData->get($userId);

            return [
                'user_id' => $userId,
                'total_amount' => $collection ? (float)$collection->total_amount : 0,
                'collections_count' => $collection ? (int)$collection->collections_count : 0,
                'follows_count' => $follow ? (int)$follow->follows_count : 0,
            ];
        });

        $sortedData = $combinedData->sortByDesc('total_amount')->values();

        $top20 = $sortedData->take(20);

        $userIdsToFetch = $top20->pluck('user_id');
        $users = User::whereIn('id', $userIdsToFetch)
            ->select('id', 'name', 'employee_number', 'image')
            ->accessible()
            ->get()
            ->keyBy('id');

        return $top20->map(function ($item, $index) use ($users) {
            $user = $users->get($item['user_id']);

            $imageUrl = asset('img/logo.svg');
            if ($user && $user->image) {
                $imagePath = str_replace('storage/', '', $user->image);
                $imageUrl = asset('storage/' . $imagePath);
            }

            return [
                'rank' => $index + 1,
                'user_id' => $item['user_id'],
                'user_name' => $user ? $user->name : 'مستخدم محذوف',
                'employee_number' => $user ? ($user->employee_number ?? 'غير محدد') : 'غير محدد',
                'image_url' => $user ? tasset($user->image) : asset('img/logo.svg'),
                'total_amount' => $item['total_amount'],
                'collections_count' => $item['collections_count'],
                'follows_count' => $item['follows_count'],
            ];
        })->values()->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Collection Date Filter (إضافة ربع سنوي)
    |--------------------------------------------------------------------------
    */
    private function getCollectionDateFilter(string $period): array
    {
        return match ($period) {
            'today' => [
                'start' => today()->toDateString(),
                'end' => today()->toDateString()
            ],
            'week' => [
                'start' => now()->startOfWeek(Carbon::SATURDAY)->toDateString(),
                'end' => now()->endOfWeek(Carbon::FRIDAY)->toDateString()
            ],
            'month' => [
                'start' => now()->startOfMonth()->toDateString(),
                'end' => now()->endOfMonth()->toDateString()
            ],
            'quarter' => [
                'start' => now()->startOfQuarter()->toDateString(),
                'end' => now()->endOfQuarter()->toDateString()
            ],
            'year' => [
                'start' => now()->startOfYear()->toDateString(),
                'end' => now()->endOfYear()->toDateString()
            ],
            default => [
                'start' => now()->startOfMonth()->toDateString(),
                'end' => now()->endOfMonth()->toDateString()
            ]
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Get Follow Date Filter (إضافة ربع سنوي)
    |--------------------------------------------------------------------------
    */
    private function getFollowDateFilter(string $period): array
    {
        return match ($period) {
            'today' => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay()
            ],
            'week' => [
                'start' => now()->startOfWeek(Carbon::SATURDAY),
                'end' => now()->endOfWeek(Carbon::FRIDAY)
            ],
            'month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth()
            ],
            'quarter' => [
                'start' => now()->startOfQuarter(),
                'end' => now()->endOfQuarter()
            ],
            'year' => [
                'start' => now()->startOfYear(),
                'end' => now()->endOfYear()
            ],
            default => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth()
            ]
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Get Performance Distribution
    |--------------------------------------------------------------------------
    */
    private function getPerformanceDistribution(string $period, ?array $userIds = null): array
    {
        $followDateFilter = $this->getFollowDateFilter($period);
        $collectionDateFilter = $this->getCollectionDateFilter($period);

        $query = CustomerFollow::select([
            'created_by',
            DB::raw('COUNT(*) as follows_count')
        ])
            ->with('createdBy:id,name,image')
            ->whereBetween('created_at', [$followDateFilter['start'], $followDateFilter['end']])
            ->groupBy('created_by')
            ->having('follows_count', '>', 0)
            ->orderByDesc('follows_count')
            ->take(20);

        if ($userIds !== null) {
            $query->whereIn('created_by', $userIds);
        }

        $distribution = $query->get()
            ->map(function ($item) use ($collectionDateFilter, $period, $userIds) {
                $userId = $item->created_by;

                $query = Collection::where('added_by', $userId)
                    ->where('status', 'approved');

                if ($period === 'today') {
                    $query->whereDate('collection_date', $collectionDateFilter['start']);
                } else {
                    $query->whereBetween('collection_date', [$collectionDateFilter['start'], $collectionDateFilter['end']]);
                }

                $amount = $query->sum('collected_amount');

                $imageUrl = asset('img/logo.svg');
                if ($item->createdBy && $item->createdBy->image) {
                    $imagePath = str_replace('storage/', '', $item->createdBy->image);
                    $imageUrl = asset('storage/' . $imagePath);
                }

                return [
                    'user_id' => $userId,
                    'user_name' => $item->createdBy->name ?? 'مستخدم محذوف',
                    'image_url' => $item->createdBy ? tasset($item->createdBy->image) : asset('img/logo.svg'),
                    'follows_count' => (int)$item->follows_count,
                    'amount' => (float)$amount,
                ];
            })
            ->toArray();

        return $distribution;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Performance Trend
    |--------------------------------------------------------------------------
    */
    private function getPerformanceTrend(string $period, ?array $userIds = null, array $topPerformers = []): array
    {
        $dates = $this->getTrendDates($period);

        if (empty($dates)) {
            return [];
        }

        // استخدام topPerformers الممرّرة بدلاً من استدعاء getTopPerformers مرة ثانية
        if (empty($topPerformers)) {
            $topPerformers = $this->getTopPerformers($period, $userIds);
        }

        if (empty($topPerformers)) {
            return [];
        }

        // استخدام user_id مباشرة — بدون البحث بالاسم
        $topUserIds = collect($topPerformers)
            ->take(5)
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($topUserIds)) {
            return [];
        }

        $startDate = $dates[0]['date'];
        $endDate   = $dates[count($dates) - 1]['date'];

        // استعلام واحد لكل البيانات بدلاً من dates × users استعلامات
        $rawData = Collection::where('status', 'approved')
            ->whereIn('added_by', $topUserIds)
            ->whereBetween('collection_date', [$startDate, $endDate])
            ->selectRaw('added_by, DATE(collection_date) as day, SUM(collected_amount) as amount')
            ->groupBy('added_by', 'day')
            ->get()
            ->groupBy('day');

        $userNames = User::whereIn('id', $topUserIds)->pluck('name', 'id');

        $trendData = [];
        foreach ($dates as $date) {
            $dayData    = ['date' => $date['label']];
            $dayRecords = $rawData->get($date['date'], collect());

            foreach ($topUserIds as $uid) {
                $name           = $userNames->get($uid, 'مستخدم محذوف');
                $record         = $dayRecords->firstWhere('added_by', $uid);
                $dayData[$name] = $record ? (float)$record->amount : 0;
            }

            $trendData[] = $dayData;
        }

        return $trendData;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Team Stats
    |--------------------------------------------------------------------------
    */
    private function getTeamStats(string $period, ?array $userIds = null): array
    {
        $dateFilter = $this->getCollectionDateFilter($period);

        $query = Collection::where('status', 'approved');

        if ($period === 'today') {
            $query->whereDate('collection_date', $dateFilter['start']);
        } else {
            $query->whereBetween('collection_date', [$dateFilter['start'], $dateFilter['end']]);
        }

        if ($userIds !== null) {
            $query->whereIn('added_by', $userIds);
        }

        $activeUsers = (clone $query)->distinct('added_by')->count('added_by');
        $totalCollections = $query->count();

        $totalActiveUsersQuery = User::where('status', 'active')->accessible();
        if ($userIds !== null) {
            $totalActiveUsersQuery->whereIn('id', $userIds);
        }
        $totalActiveUsers = $totalActiveUsersQuery->count();

        return [
            'total_users' => $totalActiveUsers,
            'active_users' => $activeUsers,
            'avg_collections' => $totalActiveUsers > 0 ? round($totalCollections / $totalActiveUsers, 2) : 0
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Trend Dates
    |--------------------------------------------------------------------------
    */
    private function getTrendDates(string $period): array
    {
        $dates = [];
        $days = match ($period) {
            'today' => 1,
            'week' => 7,
            'month' => 30,
            default => 30
        };

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dates[] = [
                'date' => $date->toDateString(),
                'label' => $date->format('M d')
            ];
        }

        return $dates;
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Work Statistics
    |--------------------------------------------------------------------------
    */
    private function getMyWorkStatistics(int $userId): array
    {
        $today = now()->toDateString();

        $todayTasks = Task::where('status', 'pending')
            ->whereDate('due_date', $today)
            ->whereHas('customer', function ($q) use ($userId) {
                $q->where('relationship_manager_id', $userId);
            })
            ->count();

        $overdueTasks = Task::where('status', 'pending')
            ->whereDate('due_date', '<', $today)
            ->whereHas('customer', function ($q) use ($userId) {
                $q->where('relationship_manager_id', $userId);
            })
            ->count();

        $newCustomers = Customer::where('relationship_manager_id', $userId)
            ->whereDoesntHave('customerFollows')
            ->count();


        return [
            'today_tasks' => $todayTasks,
            'new_customers' => $newCustomers,
            'overdue_tasks' => $overdueTasks,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function getCollectionSessionsData(?string $date = null, ?string $status = null, ?array $userIds = null): array
    {
        if (!Schema::hasTable('collection_process_sessions')) {
            return [];
        }

        $query = CollectionProcessSession::with([
            'user:id,name,employee_number,image',
            'workPeriods' => function ($q) {
                $q->orderBy('entry_time', 'asc');
            }
        ])->when($date, function ($q) use ($date) {
            $q->whereDate('date', $date);
        }, function ($q) {
            $q->whereDate('date', today());
        });

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($userIds !== null) {
            $query->whereIn('user_id', $userIds);
        }

        return $query
            ->orderBy('is_active', 'desc')
            ->orderBy('total_work_minutes', 'desc')
            ->get()
            ->map(function ($session) {
                $totalMinutes = $session->total_work_minutes;

                if ($session->is_active) {
                    $openPeriod = $session->workPeriods->whereNull('exit_time')->first();
                    if ($openPeriod) {
                        $currentPeriodMinutes = $openPeriod->entry_time->diffInMinutes(now());
                        $totalMinutes += $currentPeriodMinutes;
                    }
                }

                $hours = floor($totalMinutes / 60);
                $minutes = $totalMinutes % 60;

                $activityTime = null;
                $activityType = null;

                if ($session->is_active) {
                    $openPeriod = $session->workPeriods->whereNull('exit_time')->first();
                    if ($openPeriod) {
                        $activityTime = $openPeriod->entry_time->format('H:i');
                        $activityType = 'دخول';
                    }
                } else {
                    $lastPeriod = $session->workPeriods->sortByDesc('exit_time')->first();
                    if ($lastPeriod && $lastPeriod->exit_time) {
                        $activityTime = $lastPeriod->exit_time->format('H:i');
                        $activityType = 'خروج';
                    }
                }

                return [
                    'user_id' => $session->user_id,
                    'user_name' => $session->user->name ?? 'غير محدد',
                    'employee_number' => $session->user->employee_number ?? 'غير محدد',
                    'image_url' => tasset($session->user->image ?? null),
                    'is_active' => $session->is_active,
                    'first_entry_time' => $session->first_entry_time?->format('H:i'),
                    'activity_time' => $activityTime,
                    'activity_type' => $activityType,
                    'total_follows' => $session->total_follows,
                    'total_activities' => $session->total_activities,
                    'working_hours' => sprintf('%02d:%02d', $hours, $minutes),
                    'total_minutes' => $totalMinutes,
                ];
            })->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Comparison Data - For User Dashboard with Filters
    |--------------------------------------------------------------------------
    */
    public function getUserComparisonData(int $userId, string $period = 'month', string $dataType = 'collections'): array
    {
        $dateRange  = $this->getDateRangeForPeriod($period);
        $allUsers   = User::where('status', 'active')->accessible()->get(['id', 'name', 'employee_number', 'image']);
        $allUserIds = $allUsers->pluck('id')->all();

        if ($dataType === 'collections') {
            // استعلام واحد بدلاً من N×2 استعلام
            $collectionsMap = Collection::where('status', 'approved')
                ->whereBetween('collection_date', [$dateRange['start'], $dateRange['end']])
                ->whereIn('added_by', $allUserIds)
                ->selectRaw('added_by, COALESCE(SUM(collected_amount),0) as collection_amount, COUNT(*) as collections_count')
                ->groupBy('added_by')
                ->get()
                ->keyBy('added_by');

            $allUsersData = $allUsers->map(function ($u) use ($collectionsMap) {
                $row = $collectionsMap->get($u->id);
                return [
                    'user_id'           => $u->id,
                    'user_name'         => $u->name,
                    'employee_number'   => $u->employee_number,
                    'image_url'         => tasset($u->image ?? null),
                    'collection_amount' => $row ? (float)$row->collection_amount  : 0,
                    'collections_count' => $row ? (int)$row->collections_count    : 0,
                ];
            })->toArray();
        } else {
            // استعلامان فقط بدلاً من N×2
            $followsMap = CustomerFollow::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->whereIn('created_by', $allUserIds)
                ->selectRaw('created_by, COUNT(*) as followups_count')
                ->groupBy('created_by')
                ->get()
                ->keyBy('created_by');

            $collectionsMap = Collection::where('status', 'approved')
                ->whereBetween('collection_date', [$dateRange['start'], $dateRange['end']])
                ->whereIn('added_by', $allUserIds)
                ->selectRaw('added_by, COALESCE(SUM(collected_amount),0) as collection_amount')
                ->groupBy('added_by')
                ->get()
                ->keyBy('added_by');

            $allUsersData = $allUsers->map(function ($u) use ($followsMap, $collectionsMap) {
                $follow     = $followsMap->get($u->id);
                $collection = $collectionsMap->get($u->id);
                return [
                    'user_id'           => $u->id,
                    'user_name'         => $u->name,
                    'employee_number'   => $u->employee_number,
                    'image_url'         => tasset($u->image ?? null),
                    'followups_count'   => $follow     ? (int)$follow->followups_count       : 0,
                    'collection_amount' => $collection ? (float)$collection->collection_amount : 0,
                ];
            })->toArray();
        }

        $sortedData = $dataType === 'collections'
            ? collect($allUsersData)->sortByDesc('collection_amount')->values()->toArray()
            : collect($allUsersData)->sortByDesc('followups_count')->values()->toArray();

        $userRank = collect($sortedData)->search(fn($u) => $u['user_id'] === $userId);
        $userRank = ($userRank === false) ? count($sortedData) : $userRank + 1;

        if ($userRank <= 20) {
            $displayedUsers = array_slice($sortedData, 0, 20);
        } else {
            $top19          = array_slice($sortedData, 0, 19);
            $currentUser    = collect($sortedData)->firstWhere('user_id', $userId);
            $displayedUsers = array_merge($top19, [$currentUser]);
        }

        $currentUserData = collect($sortedData)->firstWhere('user_id', $userId);

        return [
            'current_user' => [
                'user_id'           => $userId,
                'rank'              => $userRank,
                'collection_amount' => $currentUserData['collection_amount'] ?? 0,
                'collections_count' => $currentUserData['collections_count'] ?? 0,
                'followups_count'   => $currentUserData['followups_count']   ?? 0,
            ],
            'top_collections'  => $displayedUsers,
            'all_users_count'  => count($allUsersData),
            'period'           => $period,
            'dataType'         => $dataType,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Statistics Data (الإحصائيات)
    |--------------------------------------------------------------------------
    | بيانات التحصيلات والمتابعات حسب الفترة
    */
    public function getStatisticsData(string $period = 'month'): array
    {
        $userIds = $this->getAccessibleUserIds();
        $dateRange = $this->getDateRangeForPeriod($period);

        $collectionsQuery = Collection::select([
            'added_by',
            DB::raw('COUNT(CASE WHEN status = "approved" THEN 1 END) as total_collections'),
            DB::raw('SUM(CASE WHEN status = "approved" THEN collected_amount ELSE 0 END) as total_amount'),
        ])
            ->with('addedBy:id,name')
            ->where('status', 'approved')
            ->whereBetween('collection_date', [$dateRange['start'], $dateRange['end']])
            ->groupBy('added_by')
            ->having('total_collections', '>', 0)
            ->orderByDesc('total_amount');

        if ($userIds !== null) {
            $collectionsQuery->whereIn('added_by', $userIds);
        }

        $collectionsData = $collectionsQuery->get()
            ->map(function ($item) {
                return [
                    'user_name' => $item->addedBy->name ?? 'مستخدم محذوف',
                    'total_collections' => (int) $item->total_collections,
                    'total_amount' => (float) $item->total_amount,
                ];
            })->toArray();

        $followsQuery = CustomerFollow::select([
            'created_by',
            DB::raw('COUNT(*) as follows_count')
        ])
            ->with('createdBy:id,name')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('created_by')
            ->having('follows_count', '>', 0)
            ->orderByDesc('follows_count');

        if ($userIds !== null) {
            $followsQuery->whereIn('created_by', $userIds);
        }

        $followsData = $followsQuery->get()
            ->map(function ($item) {
                return [
                    'user_name' => $item->createdBy->name ?? 'مستخدم محذوف',
                    'follows_count' => (int) $item->follows_count,
                ];
            })->toArray();

        $mergedData = $this->mergeStatisticsData($collectionsData, $followsData);

        return [
            'userPerformance' => $mergedData,
            'period' => $period
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Approvals Data (الاعتمادات)
    |--------------------------------------------------------------------------
    | بيانات المعتمد وقيد الانتظار حسب الفترة
    */
    public function getApprovalsData(string $period = 'month'): array
    {
        $userIds = $this->getAccessibleUserIds();
        $dateRange = $this->getDateRangeForPeriod($period);

        $approvalsQuery = Collection::select([
            'added_by',
            DB::raw('COUNT(CASE WHEN status = "approved" THEN 1 END) as approved_collections'),
            DB::raw('COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_collections'),
        ])
            ->with('addedBy:id,name')
            ->whereBetween('collection_date', [$dateRange['start'], $dateRange['end']])
            ->whereIn('status', ['approved', 'pending'])
            ->groupBy('added_by')
            ->orderByDesc('approved_collections');

        if ($userIds !== null) {
            $approvalsQuery->whereIn('added_by', $userIds);
        }

        $approvalsData = $approvalsQuery->get()
            ->map(function ($item) {
                return [
                    'user_name' => $item->addedBy->name ?? 'مستخدم محذوف',
                    'approved_collections' => (int) $item->approved_collections,
                    'pending_collections' => (int) $item->pending_collections,
                ];
            })->toArray();

        return [
            'userPerformance' => $approvalsData,
            'period' => $period
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Merge Statistics Data
    |--------------------------------------------------------------------------
    | دمج بيانات التحصيلات والمتابعات
    */
    private function mergeStatisticsData(array $collectionsData, array $followsData): array
    {
        $merged = [];

        foreach ($collectionsData as $collection) {
            $merged[$collection['user_name']] = [
                'user_name' => $collection['user_name'],
                'total_collections' => $collection['total_collections'],
                'total_amount' => $collection['total_amount'],
                'follows_count' => 0,
            ];
        }

        foreach ($followsData as $follow) {
            if (isset($merged[$follow['user_name']])) {
                $merged[$follow['user_name']]['follows_count'] = $follow['follows_count'];
            } else {
                $merged[$follow['user_name']] = [
                    'user_name' => $follow['user_name'],
                    'total_collections' => 0,
                    'total_amount' => 0,
                    'follows_count' => $follow['follows_count'],
                ];
            }
        }

        usort($merged, function ($a, $b) {
            return $b['total_amount'] <=> $a['total_amount'];
        });

        return array_values($merged);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Date Range For Period
    |--------------------------------------------------------------------------
    | تحديد نطاق التاريخ حسب الفترة
    */
    private function getDateRangeForPeriod(string $period): array
    {
        $now = now();

        return match ($period) {
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            'week' => [
                'start' => now()->startOfWeek(Carbon::SATURDAY),
                'end' => now()->endOfWeek(Carbon::FRIDAY)
            ],
            'month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
            'quarter' => [
                'start' => $now->copy()->startOfQuarter(),
                'end' => $now->copy()->endOfQuarter(),
            ],
            'year' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
            ],
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Get Overdue Tasks Query
    |--------------------------------------------------------------------------
    */
    public function getOverdueTasksQuery(?int $userId = null, array $filters = [])
    {
        $today = now()->toDateString();

        $query = Customer::query()
            ->select([
                'customers.id',
                'customers.name',
                'customers.phone',
                'customers.account_number',
                'customers.company_id',
                'customers.portfolio_id',
                'customers.relationship_manager_id',

                'cmp.name as company_name',
                'prt.name as portfolio_name',
                'rm.name as rm_name',
                'rm.employee_number as rm_emp',

                DB::raw('COALESCE((SELECT SUM(amount) FROM customer_transactions WHERE customer_id = customers.id AND type = "debit"), 0) as total_debit'),
                DB::raw('COALESCE((SELECT SUM(amount) FROM customer_transactions WHERE customer_id = customers.id AND type = "credit"), 0) as total_credit'),
                DB::raw('COALESCE((SELECT SUM(amount) FROM customer_transactions WHERE customer_id = customers.id AND type = "debit"), 0) - COALESCE((SELECT SUM(amount) FROM customer_transactions WHERE customer_id = customers.id AND type = "credit"), 0) as remaining_debt'),

                DB::raw('(SELECT contact_result_id FROM customer_follows WHERE customer_id = customers.id ORDER BY created_at DESC LIMIT 1) as last_result_id'),

                DB::raw("COALESCE(
                (SELECT DATE(ppd.promise_date) FROM customer_follows cf JOIN promise_payment_details ppd ON ppd.contact_result_id = cf.id WHERE cf.customer_id = customers.id AND cf.contact_result_id = 3 ORDER BY cf.created_at DESC LIMIT 1),
                (SELECT DATE(cbd.callback_date) FROM customer_follows cf JOIN callback_details cbd ON cbd.contact_result_id = cf.id WHERE cf.customer_id = customers.id AND cf.contact_result_id = 4 ORDER BY cf.created_at DESC LIMIT 1),
                (SELECT DATE(drd.documents_date) FROM customer_follows cf JOIN document_request_details drd ON drd.contact_result_id = cf.id WHERE cf.customer_id = customers.id AND cf.contact_result_id = 9 ORDER BY cf.created_at DESC LIMIT 1)
            ) as scheduled_date"),

                DB::raw("DATEDIFF(CURDATE(), COALESCE(
                (SELECT DATE(ppd.promise_date) FROM customer_follows cf JOIN promise_payment_details ppd ON ppd.contact_result_id = cf.id WHERE cf.customer_id = customers.id AND cf.contact_result_id = 3 ORDER BY cf.created_at DESC LIMIT 1),
                (SELECT DATE(cbd.callback_date) FROM customer_follows cf JOIN callback_details cbd ON cbd.contact_result_id = cf.id WHERE cf.customer_id = customers.id AND cf.contact_result_id = 4 ORDER BY cf.created_at DESC LIMIT 1),
                (SELECT DATE(drd.documents_date) FROM customer_follows cf JOIN document_request_details drd ON drd.contact_result_id = cf.id WHERE cf.customer_id = customers.id AND cf.contact_result_id = 9 ORDER BY cf.created_at DESC LIMIT 1)
            )) as overdue_days")
            ])
            ->leftJoin('companies as cmp', 'cmp.id', '=', 'customers.company_id')
            ->leftJoin('portfolios as prt', 'prt.id', '=', 'customers.portfolio_id')
            ->leftJoin('users as rm', 'rm.id', '=', 'customers.relationship_manager_id')

            ->havingRaw('remaining_debt > 0')

            ->whereRaw('(SELECT contact_result_id FROM customer_follows WHERE customer_id = customers.id ORDER BY created_at DESC LIMIT 1) IN (3, 4, 9)')

            ->whereRaw("COALESCE(
            (SELECT DATE(ppd.promise_date) FROM customer_follows cf JOIN promise_payment_details ppd ON ppd.contact_result_id = cf.id WHERE cf.customer_id = customers.id AND cf.contact_result_id = 3 ORDER BY cf.created_at DESC LIMIT 1),
            (SELECT DATE(cbd.callback_date) FROM customer_follows cf JOIN callback_details cbd ON cbd.contact_result_id = cf.id WHERE cf.customer_id = customers.id AND cf.contact_result_id = 4 ORDER BY cf.created_at DESC LIMIT 1),
            (SELECT DATE(drd.documents_date) FROM customer_follows cf JOIN document_request_details drd ON drd.contact_result_id = cf.id WHERE cf.customer_id = customers.id AND cf.contact_result_id = 9 ORDER BY cf.created_at DESC LIMIT 1)
        ) < ?", [$today]);

        if ($userId !== null) {
            $query->where('customers.relationship_manager_id', $userId);
        }

        if (!empty($filters['company_id'])) {
            $query->where('customers.company_id', $filters['company_id']);
        }

        if (!empty($filters['portfolio_id'])) {
            $query->where('customers.portfolio_id', $filters['portfolio_id']);
        }

        if (!empty($filters['relationship_manager_id'])) {
            $query->where('customers.relationship_manager_id', $filters['relationship_manager_id']);
        }

        if (!empty($filters['result_type'])) {
            $query->whereRaw('(SELECT contact_result_id FROM customer_follows WHERE customer_id = customers.id ORDER BY created_at DESC LIMIT 1) = ?', [$filters['result_type']]);
        }

        if (!empty($filters['overdue_days'])) {
            $days = (int)$filters['overdue_days'];
            $query->havingRaw('overdue_days <= ?', [$days]);
        }

        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->havingRaw('scheduled_date BETWEEN ? AND ?', [$filters['date_from'], $filters['date_to']]);
        }

        if ($userId !== null) {
            $query->orderByRaw('overdue_days DESC, remaining_debt DESC');
        } else {
            $query->orderByRaw('overdue_days DESC')->orderBy('rm.name', 'ASC')->orderByRaw('remaining_debt DESC');
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Newly Assigned And Transferred Customers
    |--------------------------------------------------------------------------
    */
    private function getMyNewlyAssignedAndTransferredCustomers(int $userId): array
    {
        $startOfMonth = now()->startOfMonth()->toDateTimeString();
        $endOfMonth   = now()->endOfMonth()->toDateTimeString();

        $assignedHistories = CustomerRelationshipHistory::where('new_manager_id', $userId)
            ->where('change_type', 'assigned')
            ->whereBetween('changed_at', [$startOfMonth, $endOfMonth])
            ->with(['customer:id,name,account_number,phone,company_id,portfolio_id'])
            ->orderByDesc('changed_at')
            ->get();

        $transferredHistories = CustomerRelationshipHistory::where('new_manager_id', $userId)
            ->where('change_type', 'transfer')
            ->whereBetween('changed_at', [$startOfMonth, $endOfMonth])
            ->with([
                'customer:id,name,account_number,phone,company_id,portfolio_id',
                'previousManager:id,name,employee_number'
            ])
            ->orderByDesc('changed_at')
            ->get();

        // --- التعديل هنا (Assigned) ---
        // أضفنا ->filter() للتأكد من عدم وجود قيم null
        $assignedCustomers = $assignedHistories->pluck('customer')->filter()->unique('id');

        $assignedRecent = $assignedCustomers->take(5)->map(function ($customer) use ($assignedHistories) {
            // إضافة تحقق إضافي للسلامة (اختياري لأننا قمنا بالفلتر سابقاً)
            if (!$customer) return null;

            $history = $assignedHistories->where('customer_id', $customer->id)->first();
            return [
                'customer' => $customer,
                'assigned_at' => $history ? $history->changed_at : null,
            ];
        })->filter(); // فلترة النتائج Null إن وجدت

        // --- التعديل هنا (Transferred) ---
        // أضفنا ->filter() لإزالة العملاء المحذوفين (Null values)
        $transferredCustomers = $transferredHistories->pluck('customer')->filter()->unique('id');

        $transferredRecent = $transferredCustomers->take(5)->map(function ($customer) use ($transferredHistories) {
            // السطر التالي كان يسبب الخطأ لأن $customer أحياناً يكون null
            if (!$customer) return null;

            $history = $transferredHistories->where('customer_id', $customer->id)->first();
            return [
                'customer' => $customer,
                'transferred_at' => $history ? $history->changed_at : null,
                'from_manager' => $history ? $history->previousManager : null,
            ];
        })->filter(); // فلترة النتائج Null

        return [
            'assigned' => [
                'count' => $assignedCustomers->count(),
                'recent_list' => $assignedRecent->values(),
            ],
            'transferred' => [
                'count' => $transferredCustomers->count(),
                'recent_list' => $transferredRecent->values(),
            ],
        ];
    }
}
