<?php

namespace App\Services\Tenants\Reports\Performance;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Company\CompanyTarget;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\UserMangment\User\User;
use App\Models\Tenants\UserMangment\Team\Team;
use App\Models\Tenants\GeneralSetting\ContactResult\ContactResult;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

abstract class BasePerformanceService
{

    protected const LARGE_EXPORT_THRESHOLD = 0;
    protected const CHUNK_SIZE = 0;

    public function getFormData(): array
    {
        $months = [
            1 => 'يناير',
            2 => 'فبراير',
            3 => 'مارس',
            4 => 'أبريل',
            5 => 'مايو',
            6 => 'يونيو',
            7 => 'يوليو',
            8 => 'أغسطس',
            9 => 'سبتمبر',
            10 => 'أكتوبر',
            11 => 'نوفمبر',
            12 => 'ديسمبر',
        ];

        return [
            'months'        => $months,
            'companies'     => Company::select('id', 'name')->orderBy('name')->get(),
            'portfolios'    => Portfolio::select('id', 'name', 'company_id')->orderBy('name')->get(),
            'teams'         => Team::select('id', 'name')->orderBy('name')->get(),
            'users'         => User::select('id', 'name', 'team_id')->orderBy('name')->get(),
        ];
    }
    /*
    |--------------------------------------------------------------------------
    | Get Company Target Percentage - جلب نسبة المستهدف للشركة
    |--------------------------------------------------------------------------
    */
    protected function getCompanyTargetPercentage(int $companyId, string $startDate, string $endDate): float
    {
        $targets = CompanyTarget::where('company_id', $companyId)
            ->where('effective_from', '<=', $endDate)
            ->where(function ($query) use ($startDate) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $startDate);
            })
            ->orderBy('effective_from')
            ->get();

        if ($targets->isEmpty()) {
            return 0.0;
        }

        if ($targets->count() === 1) {
            return (float) $targets->first()->target_percentage;
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $totalDays = $start->diffInDays($end) + 1;
        $weightedSum = 0;

        foreach ($targets as $target) {
            $effectiveStart = Carbon::parse($target->effective_from)->max($start);
            $effectiveEnd = $target->effective_to ? Carbon::parse($target->effective_to)->min($end) : $end;

            $daysInEffect = $effectiveStart->diffInDays($effectiveEnd) + 1;

            if ($daysInEffect > 0) {
                $weightedSum += ($daysInEffect * $target->target_percentage);
            }
        }

        return $weightedSum / $totalDays;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Total Portfolio - إجمالي المحفظة
    |--------------------------------------------------------------------------
    */
    protected function getTotalPortfolio(array $filters = []): float
    {
        $query = Customer::query();

        $this->applyFilters($query, $filters);

        return $query->sum(DB::raw('
            (SELECT COALESCE(SUM(CASE WHEN type="debit" THEN amount WHEN type="credit" THEN -amount ELSE 0 END), 0)
             FROM customer_transactions
             WHERE customer_transactions.customer_id = customers.id)
        '));
    }


    // دالة جديدة: إجمالي الديون الأصلية فقط (بدون طرح التحصيل)
    protected function getTotalGrossDebt(array $filters = []): float
    {
        $query = Customer::query();
        $this->applyFilters($query, $filters);

        return (float) $query->join('customer_transactions', 'customer_transactions.customer_id', '=', 'customers.id')
            ->whereNull('customer_transactions.deleted_at')
            ->where('customer_transactions.type', 'debit')
            ->sum('customer_transactions.amount');
    }

    /*
    |--------------------------------------------------------------------------
    | Get Total Collections - إجمالي التحصيل
    |--------------------------------------------------------------------------
    */
    protected function getTotalCollections(string $startDate, string $endDate, array $filters = []): float
    {
        $query = Collection::where('status', 'approved')
            ->whereBetween('collection_date', [$startDate, $endDate]);

        if (!empty($filters['company_id'])) {
            $query->whereHas('customer', fn($q) => $q->where('company_id', $filters['company_id']));
        }

        if (!empty($filters['portfolio_id'])) {
            $query->whereHas('customer', fn($q) => $q->where('portfolio_id', $filters['portfolio_id']));
        }

        if (!empty($filters['user_id'])) {
            $query->where('added_by', $filters['user_id']);
        }

        if (!empty($filters['team_id'])) {
            $query->whereHas('addedBy', fn($q) => $q->where('team_id', $filters['team_id']));
        }

        return $query->sum('collected_amount');
    }

    /*
    |--------------------------------------------------------------------------
    | Get Paid Customers Count - عدد العملاء المسددين
    |--------------------------------------------------------------------------
    */
    protected function getPaidCustomersCount(string $startDate, string $endDate, array $filters = []): int
    {
        $query = Collection::where('status', 'approved')
            ->whereBetween('collection_date', [$startDate, $endDate]);

        if (!empty($filters['company_id'])) {
            $query->whereHas('customer', fn($q) => $q->where('company_id', $filters['company_id']));
        }

        if (!empty($filters['portfolio_id'])) {
            $query->whereHas('customer', fn($q) => $q->where('portfolio_id', $filters['portfolio_id']));
        }

        if (!empty($filters['user_id'])) {
            $query->where('added_by', $filters['user_id']);
        }

        if (!empty($filters['team_id'])) {
            $query->whereHas('addedBy', fn($q) => $q->where('team_id', $filters['team_id']));
        }

        return $query->distinct('customer_id')->count('customer_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Get Total Settlements - إجمالي التسويات
    |--------------------------------------------------------------------------
    */
    protected function getTotalSettlements(string $startDate, string $endDate, array $filters = []): float
    {
        $query = Collection::where('status', 'approved')
            ->where('payment_type', 'settlement')
            ->whereBetween('collection_date', [$startDate, $endDate]);

        if (!empty($filters['company_id'])) {
            $query->whereHas('customer', fn($q) => $q->where('company_id', $filters['company_id']));
        }

        if (!empty($filters['portfolio_id'])) {
            $query->whereHas('customer', fn($q) => $q->where('portfolio_id', $filters['portfolio_id']));
        }

        if (!empty($filters['user_id'])) {
            $query->where('added_by', $filters['user_id']);
        }

        if (!empty($filters['team_id'])) {
            $query->whereHas('addedBy', fn($q) => $q->where('team_id', $filters['team_id']));
        }

        return $query->sum('collected_amount');
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Filters - تطبيق الفلاتر
    |--------------------------------------------------------------------------
    */
    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['portfolio_id'])) {
            $query->where('portfolio_id', $filters['portfolio_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('relationship_manager_id', $filters['user_id']);
        }

        if (!empty($filters['team_id'])) {
            $query->whereHas('relationshipManager', fn($q) => $q->where('team_id', $filters['team_id']));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Form Data - بيانات النموذج
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Get Date Range Text - نص نطاق التاريخ
    |--------------------------------------------------------------------------
    */
    protected function getDateRangeText(?string $dateFrom, ?string $dateTo): string
    {
        if ($dateFrom && $dateTo) {
            return "من {$dateFrom} إلى {$dateTo}";
        } elseif ($dateFrom) {
            return "من {$dateFrom}";
        } elseif ($dateTo) {
            return "حتى {$dateTo}";
        }
        return 'جميع الفترات';
    }

    /*
    |--------------------------------------------------------------------------
    | Get Contact Result Categories - إفادة التواصل
    |--------------------------------------------------------------------------
    */
    protected function getContactResultCategories(): array
    {
        return [
            'positive' => [1, 2, 4, 3],
            'negative' => [6, 7, 8, 11, 10, 5],
            'other' => [9, 12],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Promise Achievement Rate - حساب نسبة تحقيق الوعد
    |--------------------------------------------------------------------------
    */
    protected function calculatePromiseAchievementRate(int $userId, $startDate, $endDate, array $filters = []): float
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $promises = CustomerFollow::where('created_by', $userId)
            ->where('contact_result_id', 3)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when(!empty($filters['company_id']) || !empty($filters['portfolio_id']), function ($query) use ($filters) {
                $query->whereHas('customer', function ($customerQuery) use ($filters) {
                    if (!empty($filters['company_id'])) {
                        $customerQuery->where('company_id', $filters['company_id']);
                    }

                    if (!empty($filters['portfolio_id'])) {
                        $customerQuery->where('portfolio_id', $filters['portfolio_id']);
                    }
                });
            })
            ->get();

        if ($promises->isEmpty()) {
            return 0;
        }

        $fulfilledPromises = 0;

        foreach ($promises as $promise) {
            $nextFollow = CustomerFollow::where('customer_id', $promise->customer_id)
                ->where('created_at', '>', $promise->created_at)
                ->whereIn('contact_result_id', [1, 2])
                ->orderBy('created_at', 'asc')
                ->first();

            if ($nextFollow) {
                $approvedCollection = Collection::where('id', $nextFollow->collection_id)
                    ->where('status', 'approved')
                    ->exists();

                if ($approvedCollection) {
                    $fulfilledPromises++;
                }
            }
        }

        return ($fulfilledPromises / $promises->count()) * 100;
    }
}
