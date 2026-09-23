<?php

namespace App\Services\Tenants\BusinessCenter\Company;

use App\Exports\Tenants\Reports\Company\CompanyReportExport;
use App\Jobs\Tenants\Reports\Company\ExportCompanyReportJob;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CompanyReportService
{
    public const LARGE_EXPORT_THRESHOLD = 5000;

    private const STATS_CACHE_TTL = 0;
    private const CHART_CACHE_TTL = 0;

    public const PERIODS = [
        'this_month',
        'last_month',
        'this_quarter',
        'last_quarter',
        'last_3months',
        'last_6months',
        'last_12months',
        'this_year',
        'last_year',
    ];

    /*
    |--------------------------------------------------------------------------
    | Helpers — الفترة الزمنية
    |--------------------------------------------------------------------------
    */
    public static function isValidPeriod(string $period): bool
    {
        return in_array($period, self::PERIODS, true);
    }

    public static function resolvePeriod(string $period): array
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            'this_month'    => [$now->startOfMonth(),                                $now->endOfMonth()],
            'last_month'    => [$now->subMonthNoOverflow()->startOfMonth(),          $now->subMonthNoOverflow()->endOfMonth()],
            'this_quarter'  => [$now->startOfQuarter(),                              $now->endOfQuarter()],
            'last_quarter'  => [$now->subMonthsNoOverflow(3)->startOfQuarter(),      $now->subMonthsNoOverflow(3)->endOfQuarter()],
            'last_3months'  => [$now->subMonthsNoOverflow(2)->startOfMonth(),        $now->endOfMonth()],
            'last_6months'  => [$now->subMonthsNoOverflow(5)->startOfMonth(),        $now->endOfMonth()],
            'last_12months' => [$now->subMonthsNoOverflow(11)->startOfMonth(),       $now->endOfMonth()],
            'this_year'     => [$now->startOfYear(),                                 $now->endOfYear()],
            'last_year'     => [$now->subYearNoOverflow()->startOfYear(),            $now->subYearNoOverflow()->endOfYear()],
            default         => [$now->subMonthsNoOverflow(5)->startOfMonth(),        $now->endOfMonth()],
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Public API
    |--------------------------------------------------------------------------
    */
    public function getGlobalStatistics(string $period): array
    {
        $period = $this->normalizePeriod($period);

        return Cache::remember($this->cacheKey("stats:global:{$period}"), self::STATS_CACHE_TTL, function () use ($period): array {
            [$from, $to] = self::resolvePeriod($period);
            $financials  = $this->fetchFinancials($period);

            return [
                'companies'  => $this->fetchGlobalCompanyCounts(),
                'portfolios' => $this->fetchGlobalPortfolioCounts(),
                'customers'  => $this->fetchCustomerCounts($financials['totalDebt']),
                'targets'    => $this->fetchGlobalTargetPerformance($from, $to),
                'financials' => $financials,
            ];
        });
    }

    public function getCompanyStatistics(Company $company, string $period): array
    {
        $period = $this->normalizePeriod($period);

        return Cache::remember($this->cacheKey("stats:company:{$company->id}:{$period}"), self::STATS_CACHE_TTL, function () use ($company, $period): array {
            [$from, $to] = self::resolvePeriod($period);
            $financials  = $this->fetchFinancials($period, companyId: $company->id);

            return [
                'companies'          => $this->fetchGlobalCompanyCounts(),
                'portfolios'         => $this->fetchCompanyPortfolioCounts($company),
                'customers'          => $this->fetchCustomerCounts($financials['totalDebt'], companyId: $company->id),
                'targets'            => $this->fetchCompanyTargetPerformance($company, $financials['rate'], $from, $to),
                'financials'         => $financials,
                'portfolioList'      => $this->getPortfolioListForCompany($company),
                'portfolioBreakdown' => $this->fetchPortfolioBreakdown($company, $from, $to),
            ];
        });
    }

    public function getPortfolioStatistics(Portfolio $portfolio, string $period): array
    {
        $period = $this->normalizePeriod($period);

        return Cache::remember($this->cacheKey("stats:portfolio:{$portfolio->id}:{$period}"), self::STATS_CACHE_TTL, function () use ($portfolio, $period): array {
            $financials = $this->fetchFinancials($period, portfolioId: $portfolio->id);

            return [
                'companies'     => $this->fetchGlobalCompanyCounts(),
                'portfolios'    => $this->fetchPortfolioCounts($portfolio),
                'customers'     => $this->fetchCustomerCounts($financials['totalDebt'], portfolioId: $portfolio->id),
                'financials'    => $financials,
                'portfolioList' => $this->getPortfolioListForCompany($portfolio->company),
            ];
        });
    }

    public function getPortfolioListForCompany(Company $company): array
    {
        return Cache::remember($this->cacheKey("portfolios:list:{$company->id}"), self::STATS_CACHE_TTL, function () use ($company): array {
            return $company->portfolios()
                ->orderBy('name')
                ->get(['id', 'name', 'status'])
                ->map(fn(Portfolio $p): array => [
                    'id'     => $p->id,
                    'name'   => $p->name,
                    'status' => $p->status,
                ])
                ->all();
        });
    }

    public function getAvailableExportFields(): array
    {
        return [
            'fields' => [
                ['key' => 'company_name', 'label' => 'اسم الشركة', 'selected' => true],
                ['key' => 'status', 'label' => 'الحالة', 'selected' => true],
                ['key' => 'total_portfolios', 'label' => 'إجمالي المحافظ', 'selected' => true],
                ['key' => 'active_portfolios', 'label' => 'المحافظ النشطة', 'selected' => false],
                ['key' => 'inactive_portfolios', 'label' => 'المحافظ غير النشطة', 'selected' => false],
                ['key' => 'customers_count', 'label' => 'عدد العملاء', 'selected' => true],
                ['key' => 'total_debt', 'label' => 'إجمالي الديون', 'selected' => true],
                ['key' => 'total_credit', 'label' => 'إجمالي التحصيل', 'selected' => true],
                ['key' => 'collection_rate', 'label' => 'نسبة التحصيل', 'selected' => true],
                ['key' => 'target_percentage', 'label' => 'الهدف %', 'selected' => true],
                ['key' => 'created_at', 'label' => 'تاريخ الإنشاء', 'selected' => true],
            ],
        ];
    }

    public function getCompanyReportRows(array $filters = []): array
    {
        return $this->companyReportQuery($filters)
            ->orderBy('companies.name')
            ->get()
            ->map(fn($row): array => [
                'company_name' => (string) $row->name,
                'status' => (string) $row->status,
                'total_portfolios' => (int) $row->total_portfolios,
                'active_portfolios' => (int) $row->active_portfolios,
                'inactive_portfolios' => (int) $row->inactive_portfolios,
                'customers_count' => (int) $row->customers_count,
                'total_debt' => (float) $row->total_debt,
                'total_credit' => (float) $row->total_credit,
                'collection_rate' => (float) $row->collection_rate,
                'target_percentage' => $row->target_percentage === null ? null : (float) $row->target_percentage,
                'created_at' => $row->created_at ? CarbonImmutable::parse($row->created_at)->format('Y-m-d') : '',
            ])
            ->all();
    }

    public function getCompanyReportRecordsCount(array $filters = []): int
    {
        return count($this->getCompanyReportRows($filters));
    }

    public function exportCompanyReportToExcel(array $filters, array $selectedFields = []): string
    {
        $fileName = 'تقرير_الشركات_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        Excel::store(
            new CompanyReportExport($this->getCompanyReportRows($filters), $filters, $selectedFields),
            'exports/' . $fileName,
            'tenant_public'
        );

        return $fileName;
    }

    public function startAsyncExport(array $filters, array $selectedFields, int $userId): array
    {
        $count = $this->getCompanyReportRecordsCount($filters);

        if ($count === 0) {
            return [
                'success' => false,
                'message' => 'لا توجد بيانات للتصدير',
                'count' => 0,
            ];
        }

        $job = new ExportCompanyReportJob($filters, $selectedFields, $userId);
        dispatch($job);

        return [
            'success' => true,
            'export_id' => $job->getExportId(),
            'count' => $count,
            'message' => $count > self::LARGE_EXPORT_THRESHOLD
                ? "سيتم تصدير {$count} سجل. سيتم إشعارك عند الانتهاء"
                : "جاري تصدير {$count} سجل...",
        ];
    }

    public function getExportProgress(string $exportId): array
    {
        $progress = Cache::get("company_report_export_progress_{$exportId}");
        $result = Cache::get("company_report_export_result_{$exportId}");

        if ($result) {
            return array_merge(['export_id' => $exportId], $result);
        }

        if ($progress) {
            return array_merge(['export_id' => $exportId, 'status' => 'processing'], $progress);
        }

        return [
            'export_id' => $exportId,
            'status' => 'not_found',
            'message' => 'لم يتم العثور على عملية التصدير',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Charts
    |--------------------------------------------------------------------------
    */
    public function getTopCompaniesByDebt(int $limit, string $period): array
    {
        $period = $this->normalizePeriod($period);
        $limit  = max(1, min($limit, 20));
        [$from, $to] = self::resolvePeriod($period);

        return Cache::remember($this->cacheKey("chart:top-debt:{$limit}:{$period}"), self::CHART_CACHE_TTL, function () use ($limit, $from, $to): array {
            $rows = Company::query()
                ->join('customers', 'customers.company_id', '=', 'companies.id')
                ->join('customer_transactions', 'customer_transactions.customer_id', '=', 'customers.id')
                ->whereNull('customers.deleted_at')
                ->whereNull('customer_transactions.deleted_at')
                ->where('customer_transactions.type', 'debit')
                ->whereBetween('customer_transactions.transaction_date', [$from->toDateString(), $to->toDateString()])
                ->groupBy('companies.id', 'companies.name')
                ->orderByDesc('total_debt')
                ->limit($limit)
                ->select([
                    'companies.id',
                    'companies.name',
                    DB::raw('SUM(customer_transactions.amount) as total_debt'),
                ])
                ->get();

            return [
                'labels' => $rows->pluck('name')->all(),
                'data'   => $rows->pluck('total_debt')->map(fn($v): float => (float) $v)->all(),
            ];
        });
    }

    public function getCustomerDistribution(): array
    {
        return Cache::remember($this->cacheKey('chart:customer-distribution'), self::CHART_CACHE_TTL, function (): array {
            $rows = Company::query()
                ->withCount(['customers as total_customers' => fn($q) => $q->whereNull('customers.deleted_at')])
                ->having('total_customers', '>', 0)
                ->orderByDesc('total_customers')
                ->get(['id', 'name']);

            return [
                'labels' => $rows->pluck('name')->all(),
                'data'   => $rows->pluck('total_customers')->map(fn($v): int => (int) $v)->all(),
            ];
        });
    }

    // استبدل الدالة الحالية بالكامل
    public function getCollectionRateTrend(string $period, int $limit): array
    {
        $period = $this->normalizePeriod($period);
        $limit  = max(1, min($limit, 20));
        [$from, $to] = self::resolvePeriod($period);

        $isSingleMonth = in_array($period, ['this_month', 'last_month'], true);

        return Cache::remember(
            $this->cacheKey("chart:trend:{$limit}:{$period}"),
            self::CHART_CACHE_TTL,
            function () use ($limit, $from, $to, $isSingleMonth): array {
                // أعلى الشركات مديونية في الفترة
                $topCompanies = Company::query()
                    ->join('customers', 'customers.company_id', '=', 'companies.id')
                    ->join('customer_transactions', 'customer_transactions.customer_id', '=', 'customers.id')
                    ->whereNull('customers.deleted_at')
                    ->whereNull('customer_transactions.deleted_at')
                    ->where('customer_transactions.type', 'debit')
                    ->whereBetween('customer_transactions.transaction_date', [$from->toDateString(), $to->toDateString()])
                    ->groupBy('companies.id', 'companies.name')
                    ->orderByDesc('total_debt')
                    ->limit($limit)
                    ->select(['companies.id', 'companies.name', DB::raw('SUM(customer_transactions.amount) as total_debt')])
                    ->get();

                if ($topCompanies->isEmpty()) {
                    return ['labels' => [], 'datasets' => [], 'granularity' => 'monthly'];
                }

                if ($isSingleMonth) {
                    return $this->buildWeeklyTrend($topCompanies, $from, $to);
                }

                return $this->buildMonthlyTrend($topCompanies, $from, $to);
            }
        );
    }

    // دالة جديدة: التجميع الأسبوعي
    private function buildWeeklyTrend(Collection $topCompanies, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $weeks = $this->weeksBetween($from, $to);

        $transactions = CustomerTransaction::query()
            ->join('customers', 'customers.id', '=', 'customer_transactions.customer_id')
            ->whereNull('customers.deleted_at')
            ->whereNull('customer_transactions.deleted_at')
            ->whereIn('customers.company_id', $topCompanies->pluck('id'))
            ->whereBetween('customer_transactions.transaction_date', [$from->toDateString(), $to->toDateString()])
            ->select([
                'customers.company_id',
                DB::raw('YEARWEEK(customer_transactions.transaction_date, 1) as week_key'),
                'customer_transactions.type',
                DB::raw('SUM(customer_transactions.amount) as total'),
            ])
            ->groupBy('customers.company_id', 'week_key', 'customer_transactions.type')
            ->get()
            ->groupBy('company_id');

        $datasets = $topCompanies->map(fn($company): array => [
            'label' => $company->name,
            'data'  => $weeks->map(function (array $week) use ($transactions, $company): float {
                $rows   = $transactions->get($company->id, collect())
                    ->groupBy('week_key')
                    ->get($week['key'], collect());
                $debit  = (float) ($rows->firstWhere('type', 'debit')?->total  ?? 0);
                $credit = (float) ($rows->firstWhere('type', 'credit')?->total ?? 0);
                return $debit > 0 ? round(($credit / $debit) * 100, 0) : 0.0;
            })->values()->all(),
        ]);

        return [
            'labels'      => $weeks->pluck('label')->values()->all(),
            'datasets'    => $datasets->values()->all(),
            'granularity' => 'weekly',
        ];
    }

    // دالة جديدة: التجميع الشهري (المنطق القديم مفصولاً)
    private function buildMonthlyTrend(Collection $topCompanies, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $months = $this->monthsBetween($from, $to);

        $transactions = CustomerTransaction::query()
            ->join('customers', 'customers.id', '=', 'customer_transactions.customer_id')
            ->whereNull('customers.deleted_at')
            ->whereNull('customer_transactions.deleted_at')
            ->whereIn('customers.company_id', $topCompanies->pluck('id'))
            ->whereBetween('customer_transactions.transaction_date', [$from->toDateString(), $to->toDateString()])
            ->select([
                'customers.company_id',
                DB::raw("DATE_FORMAT(customer_transactions.transaction_date, '%Y-%m') as month_key"),
                'customer_transactions.type',
                DB::raw('SUM(customer_transactions.amount) as total'),
            ])
            ->groupBy('customers.company_id', 'month_key', 'customer_transactions.type')
            ->get()
            ->groupBy('company_id');

        $datasets = $topCompanies->map(fn($company): array => [
            'label' => $company->name,
            'data'  => $months->map(function (string $month) use ($transactions, $company): float {
                $rows   = $transactions->get($company->id, collect())->groupBy('month_key')->get($month, collect());
                $debit  = (float) ($rows->firstWhere('type', 'debit')?->total  ?? 0);
                $credit = (float) ($rows->firstWhere('type', 'credit')?->total ?? 0);
                return $debit > 0 ? round(($credit / $debit) * 100, 0) : 0.0;
            })->values()->all(),
        ]);

        return [
            'labels'      => $months->values()->all(),
            'datasets'    => $datasets->values()->all(),
            'granularity' => 'monthly',
        ];
    }

    // دالة مساعدة جديدة: تُعيد الأسابيع بين تاريخين
    private function weeksBetween(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $effectiveTo = $to->greaterThan(CarbonImmutable::now()) ? CarbonImmutable::now() : $to;
        $weeks       = collect();
        $cursor      = $from->startOfWeek();

        while ($cursor->lessThanOrEqualTo($effectiveTo)) {
            $weekEnd = $cursor->endOfWeek()->min($effectiveTo);
            $weeks->push([
                'key'   => (int) $cursor->format('oW'),   // YEARWEEK(...,1) equivalent
                'label' => $cursor->format('d/m') . ' - ' . $weekEnd->format('d/m'),
            ]);
            $cursor = $cursor->addWeek();
        }

        return $weeks;
    }

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    public function clearReportCache(?int $companyId = null, ?int $portfolioId = null): void
    {
        $prefix = $this->cachePrefix();

        Cache::forget("{$prefix}:chart:customer-distribution");

        foreach (self::PERIODS as $period) {
            Cache::forget("{$prefix}:stats:global:{$period}");

            foreach (range(1, 20) as $limit) {
                Cache::forget("{$prefix}:chart:top-debt:{$limit}:{$period}");
                Cache::forget("{$prefix}:chart:trend:{$limit}:{$period}");
            }

            if ($companyId !== null) {
                Cache::forget("{$prefix}:stats:company:{$companyId}:{$period}");
                Cache::forget("{$prefix}:portfolios:list:{$companyId}");
            }

            if ($portfolioId !== null) {
                Cache::forget("{$prefix}:stats:portfolio:{$portfolioId}:{$period}");
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ❶ وضع الشركات — Company model بدلاً من DB::table
    |--------------------------------------------------------------------------
    */
    private function fetchGlobalCompanyCounts(): array
    {
        // استعلام واحد يجمع كل الإحصائيات
        $row = Company::withTrashed()
            ->selectRaw("
                SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END)                            AS total,
                SUM(CASE WHEN deleted_at IS NULL AND status = 'active'   THEN 1 ELSE 0 END)    AS active,
                SUM(CASE WHEN deleted_at IS NULL AND status = 'inactive' THEN 1 ELSE 0 END)    AS inactive,
                SUM(CASE WHEN deleted_at IS NOT NULL                      THEN 1 ELSE 0 END)   AS archived,
                SUM(CASE WHEN deleted_at IS NULL AND DATE(created_at) >= ? THEN 1 ELSE 0 END)  AS new_this_month
            ", [CarbonImmutable::now()->startOfMonth()->toDateString()])
            ->first();

        return [
            'total'          => (int) ($row->total          ?? 0),
            'active'         => (int) ($row->active         ?? 0),
            'inactive'       => (int) ($row->inactive       ?? 0),
            'archived'       => (int) ($row->archived       ?? 0),
            'new_this_month' => (int) ($row->new_this_month ?? 0),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ❷ صحة المحافظ — Portfolio model (إجمالي)
    |--------------------------------------------------------------------------
    */
    private function fetchGlobalPortfolioCounts(): array
    {
        // استعلام واحد على الـ Portfolio model مباشرةً
        $row = Portfolio::selectRaw("
            COUNT(*)                                                        AS total,
            SUM(CASE WHEN status = 'active'   THEN 1 ELSE 0 END)           AS active,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END)           AS inactive
        ")->first();

        $total        = (int) ($row->total ?? 0);
        $companyCount = max(Company::count(), 1);

        return [
            'total'           => $total,
            'active'          => (int) ($row->active   ?? 0),
            'inactive'        => (int) ($row->inactive ?? 0),
            'avg_per_company' => round($total / $companyCount, 0),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ❷ صحة المحافظ — العلاقة $company->portfolios()
    |--------------------------------------------------------------------------
    */
    private function fetchCompanyPortfolioCounts(Company $company): array
    {
        // نجلب المحافظ مع عدد عملائها بـ withCount
        $portfolios = $company->portfolios()
            ->withCount(['customers' => fn($q) => $q->whereNull('customers.deleted_at')])
            ->get(['id', 'status', 'customers_count']);

        $total = $portfolios->count();

        return [
            'total'                       => $total,
            'active'                      => $portfolios->where('status', 'active')->count(),
            'inactive'                    => $portfolios->where('status', 'inactive')->count(),
            'avg_per_company'             => null,
            'avg_customers_per_portfolio' => $total > 0
                ? round($portfolios->avg('customers_count'), 0)
                : 0,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ❷ بيانات محفظة محددة — Portfolio model
    |--------------------------------------------------------------------------
    */
    private function fetchPortfolioCounts(Portfolio $portfolio): array
    {
        // نحمّل عدد العملاء عبر العلاقة مباشرةً
        $portfolio->loadCount(['customers' => fn($q) => $q->whereNull('customers.deleted_at')]);

        return [
            'total'           => 1,
            'active'          => $portfolio->status === 'active' ? 1 : 0,
            'inactive'        => $portfolio->status !== 'active' ? 1 : 0,
            'avg_per_company' => null,
            'name'            => $portfolio->name,
            'status'          => $portfolio->status,
            'customers_count' => $portfolio->customers_count,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ❸ قاعدة العملاء — Customer model
    |--------------------------------------------------------------------------
    */
    private function fetchCustomerCounts(float $totalDebt, ?int $companyId = null, ?int $portfolioId = null): array
    {
        $total = Customer::query()
            ->when($companyId   !== null, fn($q) => $q->where('company_id',   $companyId))
            ->when($portfolioId !== null, fn($q) => $q->where('portfolio_id', $portfolioId))
            ->count();

        // متوسط العملاء لكل محفظة (فقط في الإجمالي ومستوى الشركة)
        $avgPerPortfolio = null;
        if ($portfolioId === null) {
            $portfolioCount = Portfolio::query()
                ->when($companyId !== null, fn($q) => $q->where('company_id', $companyId))
                ->count();

            $avgPerPortfolio = $portfolioCount > 0 ? round($total / $portfolioCount, 0) : 0;
        }

        return [
            'total'             => $total,
            'avg_per_portfolio' => $avgPerPortfolio,
            'avg_debt'          => $total > 0 ? round($totalDebt / $total, 0) : 0.0,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ❹ الأداء مقابل الهدف — Company model + collection (إجمالي)
    |--------------------------------------------------------------------------
    | بدلاً من joinSub المعقد: نجلب الشركات ذات الأهداف + معدلاتها في query واحد
    */
    private function fetchGlobalTargetPerformance(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $totalCompanies = Company::count();

        // الشركات التي لديها هدف فعّال في الفترة
        $companiesWithTarget = Company::has('currentTarget')->with('currentTarget:id,company_id,target_percentage')->get(['id']);

        if ($companiesWithTarget->isEmpty()) {
            return [
                'total_with_target' => 0,
                'total'             => 0,
                'on_target'         => 0,
                'below_target'      => 0,
                'no_target'         => $totalCompanies,
            ];
        }

        // معدلات التحصيل لهذه الشركات في query واحد
        $rates = CustomerTransaction::query()
            ->join('customers', 'customers.id', '=', 'customer_transactions.customer_id')
            ->whereNull('customers.deleted_at')
            ->whereNull('customer_transactions.deleted_at')
            ->whereIn('customers.company_id', $companiesWithTarget->pluck('id'))
            ->whereBetween('customer_transactions.transaction_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('customers.company_id')
            ->select([
                'customers.company_id',
                DB::raw("SUM(CASE WHEN type='debit'  THEN amount ELSE 0 END) as total_debt"),
                DB::raw("SUM(CASE WHEN type='credit' THEN amount ELSE 0 END) as total_credit"),
            ])
            ->get()
            ->keyBy('company_id');

        // المقارنة في الذاكرة (collection) بدلاً من SQL معقد
        [$onTarget, $belowTarget] = $companiesWithTarget->partition(function (Company $company) use ($rates): bool {
            $target = (float) $company->currentTarget->target_percentage;
            $r      = $rates->get($company->id);
            $debt   = (float) ($r->total_debt   ?? 0);
            $credit = (float) ($r->total_credit ?? 0);
            $rate   = $debt > 0 ? ($credit / $debt) * 100 : 0.0;

            return $rate >= $target;
        });

        return [
            'total_with_target' => $companiesWithTarget->count(),
            'total'             => $companiesWithTarget->count(),
            'on_target'         => $onTarget->count(),
            'below_target'      => $belowTarget->count(),
            'no_target'         => max($totalCompanies - $companiesWithTarget->count(), 0),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ❹ هدف شركة محددة — $company->currentTarget (علاقة مباشرة)
    |--------------------------------------------------------------------------
    */
    private function fetchCompanyTargetPerformance(Company $company, float $rate, CarbonImmutable $from, CarbonImmutable $to): array
    {
        // نستخدم العلاقة الموجودة على الـ Model مباشرةً
        $target = $company->currentTarget?->target_percentage !== null ? (float) $company->currentTarget->target_percentage : null;

        $onTarget = $target !== null && $rate >= $target;

        return [
            'total_with_target' => $target !== null ? 1 : 0,
            'total'             => $target !== null ? 1 : 0,
            'on_target'         => $onTarget ? 1 : 0,
            'below_target'      => $target !== null && ! $onTarget ? 1 : 0,
            'no_target'         => $target === null ? 1 : 0,
            'target_percentage' => $target,
            'achieved_rate'     => $rate,
            'gap'               => $target !== null ? round($rate - $target, 0) : null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ❺❻❼ المالية — منطق مفصول لكل مؤشر
    |--------------------------------------------------------------------------
    |
    | ❺ المديونية  → تراكمية: كل الـ debit حتى نهاية الفترة
    | ❻ التحصيل   → الفترة فقط: credit بين $from و $to
    | ❼ المتبقي   → تراكمي: كل الـ debit - كل الـ credit حتى نهاية الفترة
    |
    | هذا الفصل يمنع المشكلة التالية:
    | إذا أُضيفت المديونية في 2023 والتحصيل في 2024،
    | تصفية الفترتين معاً تُعطي: debt=0, credit=20000
    | وهو مُضلِّل — لأن المديونية موجودة، فقط تاريخها سابق للفترة.
    |
    */
    private function fetchFinancials(
        string $period,
        ?int   $companyId   = null,
        ?int   $portfolioId = null,
    ): array {
        [$from, $to]                 = self::resolvePeriod($period);
        [$previousFrom, $previousTo] = $this->previousPeriod($from, $to);

        $baseConstraints = function ($query) use ($companyId, $portfolioId): void {
            $query->whereNull('customer_transactions.deleted_at')
                ->whereNull('customers.deleted_at')
                ->when($companyId   !== null, fn($q) => $q->where('customers.company_id',   $companyId))
                ->when($portfolioId !== null, fn($q) => $q->where('customers.portfolio_id', $portfolioId));
        };

        /*
         * ❺ المديونية التراكمية — كل الـ debit حتى نهاية الفترة
         *    لا نقيّد بـ $from — نريد الصورة الكاملة للديون المتراكمة
         */
        $debtRow = CustomerTransaction::query()
            ->join('customers', 'customers.id', '=', 'customer_transactions.customer_id')
            ->tap($baseConstraints)
            ->where('customer_transactions.type', 'debit')
            ->where('customer_transactions.transaction_date', '<=', $to->toDateString())
            ->selectRaw('SUM(amount) as total')
            ->first();

        /*
         * ❻ التحصيل — داخل الفترة المختارة فقط (للمتابعة الدورية)
         *    + الفترة السابقة للمقارنة
         */
        $creditRow = CustomerTransaction::query()
            ->join('customers', 'customers.id', '=', 'customer_transactions.customer_id')
            ->tap($baseConstraints)
            ->where('customer_transactions.type', 'credit')
            ->selectRaw("
                SUM(CASE WHEN transaction_date BETWEEN ? AND ? THEN amount ELSE 0 END) AS current_credit,
                SUM(CASE WHEN transaction_date BETWEEN ? AND ? THEN amount ELSE 0 END) AS previous_credit
            ", [
                $from->toDateString(),
                $to->toDateString(),
                $previousFrom->toDateString(),
                $previousTo->toDateString(),
            ])
            ->first();

        /*
         * ❼ المتبقي التراكمي — الرصيد الحقيقي حتى نهاية الفترة
         *    = كل الـ debit حتى $to - كل الـ credit حتى $to
         */
        $allCreditRow = CustomerTransaction::query()
            ->join('customers', 'customers.id', '=', 'customer_transactions.customer_id')
            ->tap($baseConstraints)
            ->where('customer_transactions.type', 'credit')
            ->where('customer_transactions.transaction_date', '<=', $to->toDateString())
            ->selectRaw('SUM(amount) as total')
            ->first();

        // ── حساب الفترة السابقة للمقارنة ──────────────────────────────────
        $prevDebtRow = CustomerTransaction::query()
            ->join('customers', 'customers.id', '=', 'customer_transactions.customer_id')
            ->tap($baseConstraints)
            ->where('customer_transactions.type', 'debit')
            ->where('customer_transactions.transaction_date', '<=', $previousTo->toDateString())
            ->selectRaw('SUM(amount) as total')
            ->first();

        // ── القيم النهائية ─────────────────────────────────────────────────
        $debt           = (float) ($debtRow->total          ?? 0); // ❺ تراكمي حتى نهاية الفترة
        $credit         = (float) ($creditRow->current_credit  ?? 0); // ❻ داخل الفترة فقط
        $allCredit      = (float) ($allCreditRow->total      ?? 0); // للمتبقي التراكمي
        $prevDebt       = (float) ($prevDebtRow->total       ?? 0);
        $prevCredit     = (float) ($creditRow->previous_credit ?? 0);

        $safi           = $debt - $allCredit; // ❼ الرصيد الحقيقي المتبقي
        $rate           = $debt > 0 ? round(($allCredit / $debt) * 100, 0) : 0.0;

        // نسبة التحصيل مقارنة بالفترة السابقة
        $prevAllCredit  = CustomerTransaction::query()
            ->join('customers', 'customers.id', '=', 'customer_transactions.customer_id')
            ->tap($baseConstraints)
            ->where('customer_transactions.type', 'credit')
            ->where('customer_transactions.transaction_date', '<=', $previousTo->toDateString())
            ->sum('customer_transactions.amount');

        $prevRate       = $prevDebt > 0 ? round(((float) $prevAllCredit / $prevDebt) * 100, 0) : 0.0;

        $months         = $this->periodMonthCount($from, $to);
        $monthlyCredit  = $months > 0 ? $credit / $months : 0;

        return [
            // ❺
            'totalDebt'         => round($debt, 0),
            // ❻
            'totalCredit'       => round($credit,    0),   // تحصيل الفترة للعرض
            'totalCreditAll'    => round($allCredit, 0),   // إجمالي التحصيل لحساب الـ rate
            // ❼
            'safi'              => round($safi, 0),
            'rate'              => $rate,
            'debtChange'        => $prevDebt > 0
                ? round((($debt - $prevDebt) / $prevDebt) * 100, 0)
                : null,
            'rateChange'        => round($rate - $prevRate, 0),
            'estimatedMonths'   => $safi > 0 && $monthlyCredit > 0
                ? round($safi / $monthlyCredit, 0)
                : null,
            'period'            => [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | تفصيل المحافظ — Portfolio model مع withSum
    |--------------------------------------------------------------------------
    */

    private function fetchPortfolioBreakdown(
        Company $company,
        CarbonImmutable $from,
        CarbonImmutable $to
    ): array {
        $fromDate = $from->toDateString();
        $toDate   = $to->toDateString();

        $portfolioTable = (new Portfolio())->getTable();

        $transactionSum = function (string $type) use ($fromDate, $toDate, $portfolioTable) {
            return DB::table('customer_transactions')
                ->join('customers', 'customers.id', '=', 'customer_transactions.customer_id')
                ->selectRaw('COALESCE(SUM(customer_transactions.amount), 0)')
                ->whereColumn('customers.portfolio_id', "{$portfolioTable}.id")
                ->whereNull('customers.deleted_at')
                ->whereNull('customer_transactions.deleted_at')
                ->where('customer_transactions.type', $type)
                ->whereBetween('customer_transactions.transaction_date', [$fromDate, $toDate]);
        };

        return $company->portfolios()
            ->withCount([
                'customers' => fn($q) => $q->whereNull('customers.deleted_at'),
            ])
            ->addSelect([
                'total_debt'   => $transactionSum('debit'),
                'total_credit' => $transactionSum('credit'),
            ])
            ->orderByDesc('total_debt')
            ->get()
            ->map(function (Portfolio $p): array {
                $debt   = (float) ($p->total_debt ?? 0);
                $credit = (float) ($p->total_credit ?? 0);

                return [
                    'id'              => $p->id,
                    'name'            => $p->name,
                    'status'          => $p->status,
                    'customers_count' => (int) $p->customers_count,
                    'totalDebt'       => round($debt, 0),
                    'totalCredit'     => round($credit, 0),
                    'safi'            => round($debt - $credit, 0),
                    'rate'            => $debt > 0 ? round(($credit / $debt) * 100, 0) : 0,
                ];
            })
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helpers
    |--------------------------------------------------------------------------
    */

    private function companyReportQuery(array $filters = []): Builder
    {
        $portfolioSummary = DB::table('portfolios')
            ->whereNull('deleted_at')
            ->groupBy('company_id')
            ->selectRaw("
                company_id,
                COUNT(*) as total_portfolios,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_portfolios,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_portfolios
            ");

        $customerSummary = DB::table('customers')
            ->whereNull('deleted_at')
            ->groupBy('company_id')
            ->selectRaw('company_id, COUNT(*) as customers_count');

        $financialSummary = DB::table('customers')
            ->leftJoin('customer_transactions', function ($join): void {
                $join->on('customer_transactions.customer_id', '=', 'customers.id')
                    ->whereNull('customer_transactions.deleted_at');
            })
            ->whereNull('customers.deleted_at')
            ->groupBy('customers.company_id')
            ->selectRaw("
                customers.company_id,
                SUM(CASE WHEN customer_transactions.type = 'debit' THEN customer_transactions.amount ELSE 0 END) as total_debt,
                SUM(CASE WHEN customer_transactions.type = 'credit' THEN customer_transactions.amount ELSE 0 END) as total_credit
            ");

        $targetSummary = DB::table('company_targets')
            ->whereNull('effective_to')
            ->groupBy('company_id')
            ->selectRaw('company_id, MAX(target_percentage) as target_percentage');

        $query = Company::query()
            ->leftJoinSub($portfolioSummary, 'portfolio_summary', 'portfolio_summary.company_id', '=', 'companies.id')
            ->leftJoinSub($customerSummary, 'customer_summary', 'customer_summary.company_id', '=', 'companies.id')
            ->leftJoinSub($financialSummary, 'financial_summary', 'financial_summary.company_id', '=', 'companies.id')
            ->leftJoinSub($targetSummary, 'target_summary', 'target_summary.company_id', '=', 'companies.id')
            ->select([
                'companies.id',
                'companies.name',
                'companies.status',
                'companies.created_at',
                DB::raw('COALESCE(portfolio_summary.total_portfolios, 0) as total_portfolios'),
                DB::raw('COALESCE(portfolio_summary.active_portfolios, 0) as active_portfolios'),
                DB::raw('COALESCE(portfolio_summary.inactive_portfolios, 0) as inactive_portfolios'),
                DB::raw('COALESCE(customer_summary.customers_count, 0) as customers_count'),
                DB::raw('COALESCE(financial_summary.total_debt, 0) as total_debt'),
                DB::raw('COALESCE(financial_summary.total_credit, 0) as total_credit'),
                DB::raw("
                    CASE
                        WHEN COALESCE(financial_summary.total_debt, 0) > 0
                        THEN ROUND((COALESCE(financial_summary.total_credit, 0) / financial_summary.total_debt) * 100, 2)
                        ELSE 0
                    END as collection_rate
                "),
                DB::raw('target_summary.target_percentage as target_percentage'),
            ]);

        if (! empty($filters['status'])) {
            $query->where('companies.status', (string) $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('companies.created_at', '>=', (string) $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('companies.created_at', '<=', (string) $filters['date_to']);
        }

        if (! empty($filters['company_id'])) {
            $query->where('companies.id', (int) $filters['company_id']);
        }

        if (! empty($filters['portfolio_id'])) {
            $query->whereExists(function ($exists) use ($filters): void {
                $exists->selectRaw('1')
                    ->from('portfolios')
                    ->whereColumn('portfolios.company_id', 'companies.id')
                    ->whereNull('portfolios.deleted_at')
                    ->where('portfolios.id', (int) $filters['portfolio_id']);
            });
        }

        $search = $filters['search_query'] ?? null;
        if (is_string($search) && $search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('companies.name', 'like', "%{$search}%")
                    ->orWhereExists(function ($exists) use ($search): void {
                        $exists->selectRaw('1')
                            ->from('company_authorized_people')
                            ->whereColumn('company_authorized_people.company_id', 'companies.id')
                            ->whereNull('company_authorized_people.deleted_at')
                            ->where(function ($personQuery) use ($search): void {
                                $personQuery->where('company_authorized_people.name', 'like', "%{$search}%")
                                    ->orWhere('company_authorized_people.phone', 'like', "%{$search}%")
                                    ->orWhere('company_authorized_people.email', 'like', "%{$search}%");
                            });
                    });
            });
        }

        return $query;
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function previousPeriod(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $days = (int) $from->diffInDays($to) + 1;

        return [$from->subDays($days), $from->subDay()];
    }

    private function periodMonthCount(CarbonImmutable $from, CarbonImmutable $to): int
    {
        $effectiveTo = $to->greaterThan(CarbonImmutable::now()) ? CarbonImmutable::now() : $to;

        return max((int) $from->startOfMonth()->diffInMonths($effectiveTo->startOfMonth()) + 1, 1);
    }

    private function monthsBetween(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $effectiveTo = $to->greaterThan(CarbonImmutable::now()) ? CarbonImmutable::now() : $to;
        $months      = collect();
        $cursor      = $from->startOfMonth();

        while ($cursor->lessThanOrEqualTo($effectiveTo->startOfMonth())) {
            $months->push($cursor->format('Y-m'));
            $cursor = $cursor->addMonthNoOverflow();
        }

        return $months;
    }

    private function normalizePeriod(string $period): string
    {
        return self::isValidPeriod($period) ? $period : 'last_6months';
    }

    private function cacheKey(string $key): string
    {
        return $this->cachePrefix() . ':' . $key;
    }

    private function cachePrefix(): string
    {
        $tenantId = function_exists('tenant') && tenant() ? tenant('id') : 'central';

        return "tenant:{$tenantId}:reports:companies";
    }
}
