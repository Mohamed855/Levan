<?php

namespace App\Services\Tenants\Dashboard;

use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Contract\Contract;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use App\Models\Tenants\BusinessCenter\Customers\History\CustomerRelationshipHistory;
use App\Models\Tenants\BusinessCenter\Customers\Task\Task;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\FinancialCollection\CollectionProcess\CollectionProcessSession;
use App\Models\Tenants\FinancialCollection\Target\CollectionTarget;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    /*
    |--------------------------------------------------------------------------
    | Get Admin Dashboard Data
    |--------------------------------------------------------------------------
    */
    public function getAdminDashboardData(): array
    {
        return [
            'companyStats'            => $this->getCompanyStatistics(),
            'contractStats'           => $this->getContractStatistics(),
            'portfolioStats'          => $this->getPortfolioStatistics(),
            'customerStats'           => $this->getCustomerStatistics(),
            'collectionStatsMonth'    => $this->getMonthlyCollectionStatistics(),
            'collectionStats'         => $this->getCollectionStatistics(),
            'targetStats'             => $this->getTargetStatistics(),
            'userPerformance'         => $this->getUserCollectionPerformance(),
            'targetVsAchievement'     => $this->getTargetVsAchievementComparison(),
            'timeBasedStats'          => $this->getTimeBasedCollectionStats(),
            'quickStats'              => $this->getQuickStats(),
            'expiringCompanies'       => $this->getExpiringCompaniesCount(),
            'pendingCollections'      => $this->getPendingCollections(),
            'paymentMethodDistribution' => $this->getPaymentMethodDistribution(),
            'kpiMetrics'              => $this->getKPIMetrics(),
            'collectionSessions'      => $this->getCollectionSessionsData(),
            'filterCompanies'         => Company::select('id', 'name')->orderBy('name')->get(),
            'filterPortfolios'        => Portfolio::select('id', 'name')->orderBy('name')->get(),
            'filterManagers'          => User::select('id', 'name')->orderBy('name')->get(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Get User Dashboard Data
    |--------------------------------------------------------------------------
    */
    public function getUserDashboardData(): array
    {
        $user = Auth::user();

        return [
            'user' => $user,
            'myCollectionStats' => $this->getMyCollectionStatistics(),
            'myActiveTargets' => $this->getMyActiveTargets($user->id),
            'myTargetComparison' => $this->getMyTargetComparison($user->id),
            'myTimeStats' => $this->getMyTimeBasedStats($user->id),
            'myQuickStats' => $this->getMyQuickStats($user->id),
            'targetProgress' => $this->getMyTargetProgress($user->id),
            'performanceComparison' => $this->getMyPerformanceComparison($user->id),
            'welcomeMessage' => $this->getWelcomeMessage($user),
            'myWorkStats' => $this->getMyWorkStatistics($user->id),
            'comparisonData' => $this->getUserComparisonData($user->id, 'month', 'collections'),
            'myCustomersData' => $this->getMyNewlyAssignedAndTransferredCustomers($user->id),
        ];
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
    private function getCustomerStatistics(): array
    {
        $total   = Customer::count();
        $active  = Customer::whereHas('customerStatus', fn($q) => $q->where('status', 'active'))->count();
        $inactive = Customer::whereHas('customerStatus', fn($q) => $q->where('status', 'inactive'))->count();
        $recent  = Customer::where('created_at', '>=', now()->subDays(30))->count();

        $debtPositiveCondition = '
      (
        COALESCE((SELECT SUM(amount) FROM customer_transactions WHERE customer_id = customers.id AND type = "debit"), 0)
         - COALESCE((SELECT SUM(amount) FROM customer_transactions WHERE customer_id = customers.id AND type = "credit"), 0)
         ) > 0
             ';

        $withDebt = Customer::whereRaw($debtPositiveCondition)->count();

        $start = now()->startOfMonth()->toDateString();
        $end   = now()->endOfMonth()->toDateString();

        $paidFullThisMonth = Customer::whereRaw('
       (
        COALESCE((SELECT SUM(amount) FROM customer_transactions WHERE customer_id = customers.id AND type = "debit"), 0)
      - COALESCE((SELECT SUM(amount) FROM customer_transactions WHERE customer_id = customers.id AND type = "credit"), 0)
          ) = 0
         ')
            ->whereExists(function ($q) use ($start, $end) {
                $q->select(DB::raw(1))
                    ->from('collections as c')
                    ->whereColumn('c.customer_id', 'customers.id')
                    ->where('c.status', 'approved')
                    ->whereBetween('c.collection_date', [$start, $end]);
            })->count();

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
    private function getMonthlyCollectionStatistics(): array
    {
        $start = now()->startOfMonth()->toDateString();
        $end   = now()->endOfMonth()->toDateString();

        $total    = Collection::whereBetween('collection_date', [$start, $end])->count();
        $approved = Collection::where('status', 'approved')->whereBetween('collection_date', [$start, $end])->count();
        $pending  = Collection::where('status', 'pending')->whereBetween('collection_date', [$start, $end])->count();
        $rejected = Collection::where('status', 'rejected')->whereBetween('collection_date', [$start, $end])->count();
        $fmt = function ($n) {
            if ($n >= 1000000000) return rtrim(rtrim(number_format($n / 1_000_000_000, 1), '0'), '.') . 'B';
            if ($n >= 1000000)    return rtrim(rtrim(number_format($n / 1_000_000, 1), '0'), '.') . 'M';
            if ($n >= 1000)       return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K';
            return (string)$n;
        };

        return [
            'total'    => $fmt($total),
            'approved' => $fmt($approved),
            'pending'  => $fmt($pending),
            'rejected' => $fmt($rejected),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Collection Statistics
    |--------------------------------------------------------------------------
    */
    private function getCollectionStatistics(): array
    {
        return [
            'total' => Collection::count(),
            'pending' => Collection::where('status', 'pending')->count(),
            'approved' => Collection::where('status', 'approved')->count(),
            'rejected' => Collection::where('status', 'rejected')->count(),
            'total_amount' => Collection::where('status', 'approved')->sum('collected_amount'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Target Statistics
    |--------------------------------------------------------------------------
    */
    private function getTargetStatistics(): array
    {
        return [
            'total_targets' => CollectionTarget::count(),
            'active_targets' => CollectionTarget::where('status', 'active')->count(),
            'monthly_targets' => CollectionTarget::where('target_type', 'monthly')->count(),
            'yearly_targets' => CollectionTarget::where('target_type', 'yearly')->count(),
            'custom_targets' => CollectionTarget::where('target_type', 'custom')->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Collection Performance
    |--------------------------------------------------------------------------
    */
    private function getUserCollectionPerformance(): array
    {
        return Collection::select([
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
            ->take(20)
            ->get()
            ->map(function ($item) {
                $successRate = $item->total_collections > 0 ?
                    round(($item->approved_collections / $item->total_collections) * 100, 2) : 0;

                return [
                    'user_name' => $item->addedBy->name ?? 'مستخدم محذوف',
                    'employee_number' => $item->addedBy->employee_number ?? 'غير محدد', // Add this line
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
    private function getTargetVsAchievementComparison(): array
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $monthlyTargets = CollectionTarget::where('target_type', 'monthly')
            ->where('target_month', $currentMonth)
            ->where('target_year', $currentYear)
            ->where('status', 'active')
            ->with('user:id,name')
            ->get();

        $comparison = [];

        foreach ($monthlyTargets as $target) {
            $userCollections = Collection::where('added_by', $target->user_id)
                ->where('status', 'approved')
                ->whereMonth('collection_date', $currentMonth)
                ->whereYear('collection_date', $currentYear)
                ->sum('collected_amount');

            $achievementPercentage = $target->target_amount > 0 ?
                round(($userCollections / $target->target_amount) * 100, 2) : 0;

            $comparison[] = [
                'user_id' => $target->user_id,
                'user_name' => $target->user->name,
                'target_amount' => $target->target_amount,
                'achieved_amount' => $userCollections,
                'achievement_percentage' => $achievementPercentage,
                'remaining_amount' => max(0, $target->target_amount - $userCollections),
                'status' => $this->getAchievementStatus($achievementPercentage),
            ];
        }

        return $comparison;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Time Based Collection Stats
    |--------------------------------------------------------------------------
    */
    private function getTimeBasedCollectionStats(): array
    {
        $monthlyStats = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->month;
            $year = $date->year;

            $stats = Collection::where('status', 'approved')
                ->whereMonth('collection_date', $month)
                ->whereYear('collection_date', $year)
                ->selectRaw('COUNT(*) as count, SUM(collected_amount) as amount')
                ->first();

            $monthlyStats[] = [
                'month' => $date->format('M Y'),
                'month_ar' => $date->locale('ar')->translatedFormat('M Y'),
                'count' => $stats->count ?? 0,
                'amount' => $stats->amount ?? 0
            ];
        }

        return [
            'monthly' => $monthlyStats,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Quick Stats
    |--------------------------------------------------------------------------
    */
    private function getQuickStats(): array
    {
        return [
            'pending_today' => Collection::where('status', 'pending')
                ->whereDate('created_at', today())
                ->count(),
            'approved_today' => Collection::where('status', 'approved')
                ->whereDate('approved_at', today())
                ->count(),
            'total_amount_today' => Collection::where('status', 'approved')
                ->whereDate('collection_date', today())
                ->sum('collected_amount'),
            'active_users_today' => Collection::whereDate('created_at', today())
                ->distinct('added_by')
                ->count('added_by'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Expiring Companies Count
    |--------------------------------------------------------------------------
    */
    private function getExpiringCompaniesCount(): int
    {
        return 120; // Placeholder as in original code
    }

    /*
    |--------------------------------------------------------------------------
    | Get Pending Collections
    |--------------------------------------------------------------------------
    */
    private function getPendingCollections()
    {
        return Collection::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Payment Method Distribution
    |--------------------------------------------------------------------------
    */
    private function getPaymentMethodDistribution(): array
    {
        return Collection::join('settings_payment_methods', 'collections.payment_method_id', '=', 'settings_payment_methods.id')
            ->where('collections.status', 'approved')
            ->selectRaw('
                settings_payment_methods.name as payment_method,
                COUNT(*) as count,
                SUM(collections.collected_amount) as total_amount
            ')
            ->groupBy('settings_payment_methods.id', 'settings_payment_methods.name')
            ->orderByDesc('total_amount')
            ->get()
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Get KPI Metrics
    |--------------------------------------------------------------------------
    */
    private function getKPIMetrics(): array
    {
        $currentMonth = now();
        $lastMonth = now()->subMonth();

        $currentMonthStats = Collection::where('status', 'approved')
            ->whereMonth('collection_date', $currentMonth->month)
            ->whereYear('collection_date', $currentMonth->year)
            ->selectRaw('COUNT(*) as count, SUM(collected_amount) as amount')
            ->first();

        $lastMonthStats = Collection::where('status', 'approved')
            ->whereMonth('collection_date', $lastMonth->month)
            ->whereYear('collection_date', $lastMonth->year)
            ->selectRaw('COUNT(*) as count, SUM(collected_amount) as amount')
            ->first();

        $amountGrowth = 0;
        $countGrowth = 0;

        if ($lastMonthStats->amount > 0) {
            $amountGrowth = round(
                (($currentMonthStats->amount - $lastMonthStats->amount) / $lastMonthStats->amount) * 100,
                2
            );
        }

        if ($lastMonthStats->count > 0) {
            $countGrowth = round(
                (($currentMonthStats->count - $lastMonthStats->count) / $lastMonthStats->count) * 100,
                2
            );
        }

        return [
            'current_month_amount' => $currentMonthStats->amount ?? 0,
            'current_month_count' => $currentMonthStats->count ?? 0,
            'last_month_amount' => $lastMonthStats->amount ?? 0,
            'last_month_count' => $lastMonthStats->count ?? 0,
            'amount_growth_percentage' => $amountGrowth,
            'count_growth_percentage' => $countGrowth,
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

        return [
            'total' => Collection::where('added_by', $userId)->count(),
            'pending' => Collection::where('added_by', $userId)->where('status', 'pending')->count(),
            'approved' => Collection::where('added_by', $userId)->where('status', 'approved')->count(),
            'rejected' => Collection::where('added_by', $userId)->where('status', 'rejected')->count(),
            'total_amount' => Collection::where('added_by', $userId)->where('status', 'approved')->sum('collected_amount'),
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
        $weeklyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $stats = Collection::where('added_by', $userId)
                ->where('status', 'approved')
                ->whereDate('collection_date', $date->toDateString())
                ->selectRaw('COUNT(*) as count, SUM(collected_amount) as amount')
                ->first();

            $weeklyStats[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->locale('ar')->dayName,
                'count' => $stats->count ?? 0,
                'amount' => $stats->amount ?? 0
            ];
        }

        return [
            'weekly' => $weeklyStats,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get My Quick Stats
    |--------------------------------------------------------------------------
    */
    private function getMyQuickStats(int $userId): array
    {
        $now        = now();
        $startMonth = $now->copy()->startOfMonth();
        $endMonth   = $now->copy()->endOfMonth();

        // الشهر الحالي — إجمالي (كل الحالات)
        $monthTotalCount = Collection::where('added_by', $userId)
            ->whereBetween('collection_date', [$startMonth, $endMonth])
            ->count();

        $monthTotalAmount = (float) Collection::where('added_by', $userId)
            ->whereBetween('collection_date', [$startMonth, $endMonth])
            ->sum('collected_amount');

        // الشهر الحالي — معتمد فقط
        $monthApprovedCount = Collection::where('added_by', $userId)
            ->where('status', 'approved')
            ->whereBetween('collection_date', [$startMonth, $endMonth])
            ->count();

        $monthApprovedAmount = (float) Collection::where('added_by', $userId)
            ->where('status', 'approved')
            ->whereBetween('collection_date', [$startMonth, $endMonth])
            ->sum('collected_amount');

        // الشهر الحالي — قيد الاعتماد
        $monthPendingCount = Collection::where('added_by', $userId)
            ->where('status', 'pending')
            ->whereBetween('collection_date', [$startMonth, $endMonth])
            ->count();

        $monthPendingAmount = (float) Collection::where('added_by', $userId)
            ->where('status', 'pending')
            ->whereBetween('collection_date', [$startMonth, $endMonth])
            ->sum('collected_amount');

        // مقارنة هذا الشهر مقابل الشهر الماضي — "معتمد فقط"
        $lastMonth   = $now->copy()->subMonth();
        $lastStart   = $lastMonth->copy()->startOfMonth();
        $lastEnd     = $lastMonth->copy()->endOfMonth();

        $thisMonthAmountApproved = $monthApprovedAmount;
        $thisMonthCountApproved  = $monthApprovedCount;

        $lastMonthAmountApproved = (float) Collection::where('added_by', $userId)
            ->where('status', 'approved')
            ->whereBetween('collection_date', [$lastStart, $lastEnd])
            ->sum('collected_amount');

        $lastMonthCountApproved = (int) Collection::where('added_by', $userId)
            ->where('status', 'approved')
            ->whereBetween('collection_date', [$lastStart, $lastEnd])
            ->count();

        $amountDeltaPct = $lastMonthAmountApproved > 0
            ? round((($thisMonthAmountApproved - $lastMonthAmountApproved) / $lastMonthAmountApproved) * 100, 1)
            : ($thisMonthAmountApproved > 0 ? 100.0 : 0.0);

        $countDeltaPct = $lastMonthCountApproved > 0
            ? round((($thisMonthCountApproved - $lastMonthCountApproved) / $lastMonthCountApproved) * 100, 1)
            : ($thisMonthCountApproved > 0 ? 100.0 : 0.0);

        return [
            // (اختياري) يومي
            'today_collections' => Collection::where('added_by', $userId)
                ->whereDate('created_at', $now->toDateString())
                ->count(),
            'today_amount' => Collection::where('added_by', $userId)
                ->where('status', 'approved')
                ->whereDate('collection_date', $now->toDateString())
                ->sum('collected_amount'),

            // الشهر الحالي — لواجهة الكروت
            'month_total_count'    => $monthTotalCount,
            'month_total_amount'   => $monthTotalAmount,

            'month_approved_count'  => $monthApprovedCount,
            'month_approved_amount' => $monthApprovedAmount,

            'month_pending_count'  => $monthPendingCount,
            'month_pending_amount' => $monthPendingAmount,

            // هذا الشهر مقابل الشهر الماضي (معتمد فقط)
            'month_vs_last' => [
                'this_month_amount' => $thisMonthAmountApproved,
                'this_month_count'  => $thisMonthCountApproved,
                'last_month_amount' => $lastMonthAmountApproved,
                'last_month_count'  => $lastMonthCountApproved,
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
        return [
            'topPerformers' => $this->getTopPerformers($period),
            'performanceDistribution' => $this->getPerformanceDistribution($period),
            'performanceTrend' => $this->getPerformanceTrend($period),
            'teamStats' => $this->getTeamStats($period),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Top Performers (محسّن لدعم التحصيلات + المتابعات)
    |--------------------------------------------------------------------------
    */
    private function getTopPerformers(string $period): array
    {
        $dateFilter = $this->getCollectionDateFilter($period);
        $followDateFilter = $this->getFollowDateFilter($period);

        // جلب بيانات التحصيلات
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

        $collectionsData = $collectionsQuery
            ->groupBy('added_by')
            ->having('total_amount', '>', 0)
            ->get()
            ->keyBy('added_by');

        // جلب بيانات المتابعات
        $followsQuery = CustomerFollow::select([
            'created_by',
            DB::raw('COUNT(*) as follows_count')
        ]);

        if ($period === 'today') {
            $followsQuery->whereDate('created_at', $followDateFilter['start']);
        } else {
            $followsQuery->whereBetween('created_at', [$followDateFilter['start'], $followDateFilter['end']]);
        }

        $followsData = $followsQuery
            ->groupBy('created_by')
            ->having('follows_count', '>', 0)
            ->get()
            ->keyBy('created_by');

        // دمج البيانات
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

        // ترتيب حسب التحصيلات (افتراضي)
        $sortedData = $combinedData->sortByDesc('total_amount')->values();

        // أخذ أفضل 15 موظف
        $top20 = $sortedData->take(20);

        // جلب بيانات المستخدمين
        $userIds = $top20->pluck('user_id');
        $users = User::whereIn('id', $userIds)
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
                // يبدأ من السبت وينتهي الجمعة
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
                // يبدأ من السبت وينتهي الجمعة
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
    private function getPerformanceDistribution(string $period): array
    {
        $followDateFilter = $this->getFollowDateFilter($period);
        $collectionDateFilter = $this->getCollectionDateFilter($period);

        $distribution = CustomerFollow::select([
            'created_by',
            DB::raw('COUNT(*) as follows_count')
        ])
            ->with('createdBy:id,name,image')
            ->whereBetween('created_at', [$followDateFilter['start'], $followDateFilter['end']])
            ->groupBy('created_by')
            ->having('follows_count', '>', 0)
            ->orderByDesc('follows_count')
            ->take(20)
            ->get()
            ->map(function ($item) use ($collectionDateFilter, $period) {
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
    private function getPerformanceTrend(string $period): array
    {
        $dates = $this->getTrendDates($period);
        $topUsers = $this->getTopPerformers($period);

        if (empty($topUsers)) {
            return [];
        }

        // الحصول على user IDs الفعلية
        $topUserIds = [];
        foreach ($topUsers as $user) {
            if ($user['user_name'] !== 'مستخدم محذوف') {
                // البحث عن المستخدم
                $collection = Collection::where('added_by', '!=', null)
                    ->with('addedBy:id,name')
                    ->whereHas('addedBy', function ($query) use ($user) {
                        $query->where('name', $user['user_name']);
                    })
                    ->first();

                if ($collection && $collection->added_by) {
                    $topUserIds[] = $collection->added_by;
                }
            }
        }

        if (empty($topUserIds)) {
            return [];
        }

        // أخذ أول 5 فقط
        $topUserIds = array_slice(array_unique($topUserIds), 0, 5);

        $trendData = [];
        foreach ($dates as $date) {
            $dayData = ['date' => $date['label']];

            foreach ($topUserIds as $userId) {
                $user = User::find($userId);
                if ($user) {
                    $amount = Collection::where('added_by', $userId)
                        ->where('status', 'approved')
                        ->whereDate('collection_date', $date['date'])
                        ->sum('collected_amount');

                    $dayData[$user->name] = (float)$amount;
                }
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
    private function getTeamStats(string $period): array
    {
        $dateFilter = $this->getCollectionDateFilter($period);

        $query = Collection::where('status', 'approved');

        if ($period === 'today') {
            $query->whereDate('collection_date', $dateFilter['start']);
        } else {
            $query->whereBetween('collection_date', [$dateFilter['start'], $dateFilter['end']]);
        }

        $activeUsers = (clone $query)->distinct('added_by')->count('added_by');
        $totalCollections = $query->count();
        $totalActiveUsers = User::where('status', 'active')->accessible()->count();

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

        // حساب المهام اليومية
        $todayTasks = Task::where('status', 'pending')
            ->whereDate('due_date', $today)
            ->whereHas('customer', function ($q) use ($userId) {
                $q->where('relationship_manager_id', $userId);
            })
            ->count();

        // حساب المهام المتأخرة
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
    public function getCollectionSessionsData(?string $date = null, ?string $status = null): array
    {
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
        $user = User::find($userId);

        // تحديد نطاق التاريخ بناءً على الفترة
        $dateRange = $this->getDateRangeForPeriod($period);

        $allUsers = User::where('status', 'active')->accessible()->get();

        // جمع بيانات جميع المستخدمين
        $allUsersData = $allUsers->map(function ($u) use ($dateRange, $dataType) {
            $data = [
                'user_id' => $u->id,
                'user_name' => $u->name,
                'employee_number' => $u->employee_number,
                'image_url' => tasset($u->image ?? null),
            ];

            if ($dataType === 'collections') {
                $data['collection_amount'] = Collection::where('added_by', $u->id)
                    ->where('status', 'approved')
                    ->whereBetween('collection_date', [$dateRange['start'], $dateRange['end']])
                    ->sum('collected_amount');
                $data['collections_count'] = Collection::where('added_by', $u->id)
                    ->where('status', 'approved')
                    ->whereBetween('collection_date', [$dateRange['start'], $dateRange['end']])
                    ->count();
            } else {
                $data['followups_count'] = CustomerFollow::where('created_by', $u->id)
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                    ->count();
                $data['collection_amount'] = Collection::where('added_by', $u->id)
                    ->where('status', 'approved')
                    ->whereBetween('collection_date', [$dateRange['start'], $dateRange['end']])
                    ->sum('collected_amount');
            }

            return $data;
        })->toArray();

        // ترتيب حسب نوع البيانات المختار
        if ($dataType === 'collections') {
            $sortedData = collect($allUsersData)
                ->sortByDesc('collection_amount')
                ->values()
                ->toArray();
        } else {
            $sortedData = collect($allUsersData)
                ->sortByDesc('followups_count')
                ->values()
                ->toArray();
        }

        // العثور على ترتيب الموظف الحالي
        $userRank = collect($sortedData)->search(function ($user) use ($userId) {
            return $user['user_id'] === $userId;
        });

        if ($userRank === false) {
            $userRank = count($sortedData);
        } else {
            $userRank += 1;
        }

        // المنطق الذكي لاختيار الموظفين (20 موظف)
        $displayedUsers = [];

        if ($userRank <= 20) {
            // إذا كان الموظف ضمن أفضل 20، اعرض أفضل 20
            $displayedUsers = array_slice($sortedData, 0, 20);
        } else {
            // إذا كان خارج أفضل 20، اعرض أفضل 19 + الموظف الحالي
            $top19 = array_slice($sortedData, 0, 19);
            $currentUser = collect($sortedData)->firstWhere('user_id', $userId);
            $displayedUsers = array_merge($top19, [$currentUser]);
        }

        $currentUserData = collect($sortedData)->firstWhere('user_id', $userId);

        return [
            'current_user' => [
                'user_id' => $userId,
                'rank' => $userRank,
                'collection_amount' => $currentUserData['collection_amount'] ?? 0,
                'collections_count' => $currentUserData['collections_count'] ?? 0,
                'followups_count' => $currentUserData['followups_count'] ?? 0,
            ],
            'top_collections' => $displayedUsers,
            'all_users_count' => count($allUsersData),
            'period' => $period,
            'dataType' => $dataType,
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
        $dateRange = $this->getDateRangeForPeriod($period);

        // بيانات التحصيلات (بالعدد والمبلغ)
        $collectionsData = Collection::select([
            'added_by',
            DB::raw('COUNT(CASE WHEN status = "approved" THEN 1 END) as total_collections'),
            DB::raw('SUM(CASE WHEN status = "approved" THEN collected_amount ELSE 0 END) as total_amount'),
        ])
            ->with('addedBy:id,name')
            ->where('status', 'approved')
            ->whereBetween('collection_date', [$dateRange['start'], $dateRange['end']])
            ->groupBy('added_by')
            ->having('total_collections', '>', 0)
            ->orderByDesc('total_amount')
            ->get()
            ->map(function ($item) {
                return [
                    'user_name' => $item->addedBy->name ?? 'مستخدم محذوف',
                    'total_collections' => (int) $item->total_collections,
                    'total_amount' => (float) $item->total_amount,
                ];
            })->toArray();

        // بيانات المتابعات
        $followsData = CustomerFollow::select([
            'created_by',
            DB::raw('COUNT(*) as follows_count')
        ])
            ->with('createdBy:id,name')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->groupBy('created_by')
            ->having('follows_count', '>', 0)
            ->orderByDesc('follows_count')
            ->get()
            ->map(function ($item) {
                return [
                    'user_name' => $item->createdBy->name ?? 'مستخدم محذوف',
                    'follows_count' => (int) $item->follows_count,
                ];
            })->toArray();

        // دمج البيانات
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
        $dateRange = $this->getDateRangeForPeriod($period);

        $approvalsData = Collection::select([
            'added_by',
            DB::raw('COUNT(CASE WHEN status = "approved" THEN 1 END) as approved_collections'),
            DB::raw('COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_collections'),
        ])
            ->with('addedBy:id,name')
            ->whereBetween('collection_date', [$dateRange['start'], $dateRange['end']])
            ->whereIn('status', ['approved', 'pending'])
            ->groupBy('added_by')
            ->orderByDesc('approved_collections')
            ->get()
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

        // إضافة بيانات التحصيلات
        foreach ($collectionsData as $collection) {
            $merged[$collection['user_name']] = [
                'user_name' => $collection['user_name'],
                'total_collections' => $collection['total_collections'],
                'total_amount' => $collection['total_amount'],
                'follows_count' => 0,
            ];
        }

        // إضافة بيانات المتابعات
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

        // ترتيب حسب إجمالي المبلغ
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

        $assignedCustomers = $assignedHistories->pluck('customer')->unique('id');
        $assignedRecent = $assignedCustomers->take(5)->map(function ($customer) use ($assignedHistories) {
            $history = $assignedHistories->where('customer_id', $customer->id)->first();
            return [
                'customer' => $customer,
                'assigned_at' => $history ? $history->changed_at : null,
            ];
        });

        $transferredCustomers = $transferredHistories->pluck('customer')->unique('id');
        $transferredRecent = $transferredCustomers->take(5)->map(function ($customer) use ($transferredHistories) {
            $history = $transferredHistories->where('customer_id', $customer->id)->first();
            return [
                'customer' => $customer,
                'transferred_at' => $history ? $history->changed_at : null,
                'from_manager' => $history ? $history->previousManager : null,
            ];
        });

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
