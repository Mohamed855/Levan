<?php

namespace App\Services\Tenants\Reports\Executive;

use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\UserMangment\User\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExecutiveReportService
{
    public function getCount(): array
    {
        return [
            'companiesCount'  => Company::count(),
            'portfoliosCount' => Portfolio::count(),
            'customersCount'  => Customer::count(),
            'usersCount'      => User::count(),
        ];
    }

    public function getFormData(): array
    {
        return [
            'companies'  => Company::select('id', 'name')->orderBy('name')->get(),
            'portfolios' => Portfolio::select('id', 'name', 'company_id')->orderBy('name')->get(),
        ];
    }

    public function getDashboardData(array $filters): array
    {
        [$dateFrom, $dateTo] = $this->parseDateRange($filters);
        $companyId   = !empty($filters['company_id'])   ? (int) $filters['company_id']   : null;
        $portfolioId = !empty($filters['portfolio_id']) ? (int) $filters['portfolio_id'] : null;
        // null = default limits | 0 = no limit (all) | N = specific limit
        $exportLimit = isset($filters['export_limit']) ? (int) $filters['export_limit'] : null;

        if ($portfolioId) {
            $base = $this->getPortfolioData($filters, $exportLimit);
        } elseif ($companyId) {
            $base = $this->getCompanyData($filters, $exportLimit);
        } else {
            $base = $this->getOverviewData($filters, $exportLimit);
        }

        return array_merge(
            $base,
            $this->getAdditionalCharts($dateFrom->toDateString(), $dateTo->toDateString(), $companyId, $portfolioId, $exportLimit),
            ['monthlyTrend' => $this->getMonthlyTrend($dateFrom->toDateString(), $dateTo->toDateString(), $companyId, $portfolioId)],
        );
    }

    private function lim($query, int $default, ?int $exportLimit): mixed
    {
        if ($exportLimit === 0) return $query;
        return $query->limit($exportLimit ?? $default);
    }

    // -----------------------------------------------------------------------
    // Additional charts (employee perf, user collections, debtors)
    // -----------------------------------------------------------------------

    private function getAdditionalCharts(string $from, string $to, ?int $companyId = null, ?int $portfolioId = null, ?int $exportLimit = null): array
    {
        $needCustomerJoin = $companyId || $portfolioId;

        // ── Follow-ups per user ──────────────────────────────────────────────
        $followsQ = DB::table('customer_follows')
            ->join('users', 'customer_follows.created_by', '=', 'users.id')
            ->whereNull('customer_follows.deleted_at')
            ->whereNull('users.deleted_at')
            ->whereBetween(DB::raw('DATE(customer_follows.created_at)'), [$from, $to]);

        if ($needCustomerJoin) {
            $followsQ->join('customers as cf_cust', 'customer_follows.customer_id', '=', 'cf_cust.id')
                ->whereNull('cf_cust.deleted_at');
            if ($companyId)   $followsQ->where('cf_cust.company_id', $companyId);
            if ($portfolioId) $followsQ->where('cf_cust.portfolio_id', $portfolioId);
        }

        $followsByUser = $this->lim(
            $followsQ->groupBy('users.id', 'users.name')
                ->selectRaw('users.id, users.name, COUNT(customer_follows.id) as cnt')
                ->orderByDesc('cnt'),
            20,
            $exportLimit
        )->get();

        // ── Approved collections per user ────────────────────────────────────
        $collectQ = DB::table('collections')
            ->join('users', 'collections.added_by', '=', 'users.id')
            ->whereNull('collections.deleted_at')
            ->whereNull('users.deleted_at')
            ->where('collections.status', 'approved')
            ->whereBetween('collections.collection_date', [$from, $to]);

        if ($needCustomerJoin) {
            $collectQ->join('customers as col_cust', 'collections.customer_id', '=', 'col_cust.id')
                ->whereNull('col_cust.deleted_at');
            if ($companyId)   $collectQ->where('col_cust.company_id', $companyId);
            if ($portfolioId) $collectQ->where('col_cust.portfolio_id', $portfolioId);
        }

        $collectionsByUser = $this->lim(
            $collectQ->groupBy('users.id', 'users.name')
                ->selectRaw('users.id, users.name, COUNT(collections.id) as cnt, SUM(collections.collected_amount) as total')
                ->orderByDesc('total'),
            20,
            $exportLimit
        )->get();

        // ── Employee performance: top 20 by follows ──────────────────────────
        $collectionsMap     = $collectionsByUser->keyBy('id');
        $empPerfCategories  = $followsByUser->pluck('name')->toArray();
        $empPerfFollows     = $followsByUser->pluck('cnt')->map(fn($v) => (int) $v)->toArray();
        $empPerfPayments    = $followsByUser->map(fn($u) => (int) ($collectionsMap->get($u->id)?->cnt ?? 0))->toArray();

        // ── User collections: top 20 by amount ──────────────────────────────
        $userCollCategories = $collectionsByUser->pluck('name')->toArray();
        $userCollAmounts    = $collectionsByUser->map(fn($u) => round((float) $u->total))->toArray();

        // ── Top debtors (net remaining debt) ────────────────────────────────
        $debtQ = DB::table('customer_transactions')
            ->join('customers', 'customer_transactions.customer_id', '=', 'customers.id')
            ->whereNull('customer_transactions.deleted_at')
            ->whereNull('customers.deleted_at');

        if ($companyId)   $debtQ->where('customers.company_id', $companyId);
        if ($portfolioId) $debtQ->where('customers.portfolio_id', $portfolioId);

        $topDebtors = $debtQ
            ->groupBy('customers.id', 'customers.name')
            ->selectRaw("customers.id, customers.name,
                SUM(CASE WHEN customer_transactions.type='debit'  THEN customer_transactions.amount ELSE 0 END) as total_debit,
                SUM(CASE WHEN customer_transactions.type='credit' THEN customer_transactions.amount ELSE 0 END) as total_credit")
            ->havingRaw("SUM(CASE WHEN customer_transactions.type='debit' THEN customer_transactions.amount ELSE 0 END) > SUM(CASE WHEN customer_transactions.type='credit' THEN customer_transactions.amount ELSE 0 END)")
            ->orderByRaw("(SUM(CASE WHEN customer_transactions.type='debit' THEN customer_transactions.amount ELSE 0 END) - SUM(CASE WHEN customer_transactions.type='credit' THEN customer_transactions.amount ELSE 0 END)) DESC");
        $topDebtors = $this->lim($debtQ, 20, $exportLimit)->get();

        $debtorNames     = $topDebtors->pluck('name')->toArray();
        $debtorDebit     = $topDebtors->map(fn($c) => round((float) $c->total_debit))->toArray();
        $debtorRemaining = $topDebtors->map(fn($c) => round(max(0.0, (float) $c->total_debit - (float) $c->total_credit)))->toArray();

        return [
            'employeePerf' => [
                'categories'     => $empPerfCategories,
                'followsSeries'  => $empPerfFollows,
                'paymentsSeries' => $empPerfPayments,
            ],
            'userCollections' => [
                'categories' => $userCollCategories,
                'series'     => $userCollAmounts,
            ],
            'topDebtors' => [
                'categories' => $debtorNames,
                'series'     => $debtorRemaining,
            ],
            'debtVsRemaining' => [
                'categories'      => $debtorNames,
                'debitSeries'     => $debtorDebit,
                'remainingSeries' => $debtorRemaining,
            ],
        ];
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function parseDateRange(array $filters): array
    {
        $parse = fn($val, Carbon $default) => $val ? Carbon::createFromFormat('d/m/Y', $val) : $default;

        $dateFrom = $parse($filters['date_from'] ?? null, Carbon::now()->startOfMonth());
        $dateTo   = $parse($filters['date_to']   ?? null, Carbon::now()->endOfMonth());

        return [$dateFrom->startOfDay(), $dateTo->endOfDay()];
    }

    private function toM(float $amount): float
    {
        return round($amount / 1_000_000, 2);
    }

    private function fmt(float $amount): string
    {
        return number_format($amount, 0, '.', ',') . ' ر.س';
    }

    private function rate(float $due, float $collected): float
    {
        return $due > 0 ? round(($collected / $due) * 100, 1) : 0;
    }

    private function collectedByPortfolio(string $from, string $to, ?int $companyId = null, ?int $portfolioId = null): \Illuminate\Support\Collection
    {
        $q = DB::table('collections')
            ->join('customers', 'collections.customer_id', '=', 'customers.id')
            ->whereNull('collections.deleted_at')
            ->whereNull('customers.deleted_at')
            ->where('collections.status', 'approved')
            ->whereBetween('collections.collection_date', [$from, $to]);

        if ($companyId)   $q->where('customers.company_id', $companyId);
        if ($portfolioId) $q->where('customers.portfolio_id', $portfolioId);

        return $q->groupBy('customers.portfolio_id')
            ->selectRaw('customers.portfolio_id, SUM(collections.collected_amount) as total')
            ->get()->keyBy('portfolio_id');
    }

    private function dueByPortfolio(string $from, string $to, ?int $companyId = null, ?int $portfolioId = null): \Illuminate\Support\Collection
    {
        $q = DB::table('customer_transactions')
            ->join('customers', 'customer_transactions.customer_id', '=', 'customers.id')
            ->whereNull('customer_transactions.deleted_at')
            ->whereNull('customers.deleted_at')
            ->where('customer_transactions.type', 'debit')
            ->whereBetween('customer_transactions.transaction_date', [$from, $to]);

        if ($companyId)   $q->where('customers.company_id', $companyId);
        if ($portfolioId) $q->where('customers.portfolio_id', $portfolioId);

        return $q->groupBy('customers.portfolio_id')
            ->selectRaw('customers.portfolio_id, SUM(customer_transactions.amount) as total')
            ->get()->keyBy('portfolio_id');
    }

    // -----------------------------------------------------------------------
    // Monthly trend chart
    // -----------------------------------------------------------------------

    private function getMonthlyTrend(string $from, string $to, ?int $companyId, ?int $portfolioId): array
    {
        $arabicMonths = [
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

        $collectedQ = DB::table('collections')
            ->join('customers', 'collections.customer_id', '=', 'customers.id')
            ->whereNull('collections.deleted_at')->whereNull('customers.deleted_at')
            ->where('collections.status', 'approved')
            ->whereBetween('collections.collection_date', [$from, $to]);

        $dueQ = DB::table('customer_transactions')
            ->join('customers', 'customer_transactions.customer_id', '=', 'customers.id')
            ->whereNull('customer_transactions.deleted_at')->whereNull('customers.deleted_at')
            ->where('customer_transactions.type', 'debit')
            ->whereBetween('customer_transactions.transaction_date', [$from, $to]);

        if ($companyId) {
            $collectedQ->where('customers.company_id', $companyId);
            $dueQ->where('customers.company_id', $companyId);
        }
        if ($portfolioId) {
            $collectedQ->where('customers.portfolio_id', $portfolioId);
            $dueQ->where('customers.portfolio_id', $portfolioId);
        }

        $collectedByMonth = $collectedQ
            ->groupByRaw("DATE_FORMAT(collection_date, '%Y-%m')")
            ->selectRaw("DATE_FORMAT(collection_date, '%Y-%m') as month, SUM(collected_amount) as total")
            ->orderBy('month')->get()->keyBy('month');

        $dueByMonth = $dueQ
            ->groupByRaw("DATE_FORMAT(transaction_date, '%Y-%m')")
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month, SUM(amount) as total")
            ->orderBy('month')->get()->keyBy('month');

        // بناء قائمة الأشهر ضمن الفترة
        $current = Carbon::parse($from)->startOfMonth();
        $end     = Carbon::parse($to)->startOfMonth();
        $periods = [];
        while ($current <= $end) {
            $periods[] = $current->format('Y-m');
            $current->addMonth();
        }

        $categories = array_map(function (string $period) use ($arabicMonths) {
            [$y, $m] = explode('-', $period);
            return $arabicMonths[(int) $m] . ' ' . substr($y, 2);
        }, $periods);

        return [
            'categories'      => $categories,
            'dueSeries'       => array_map(fn($p) => round((float) ($dueByMonth->get($p)?->total ?? 0)), $periods),
            'collectedSeries' => array_map(fn($p) => round((float) ($collectedByMonth->get($p)?->total ?? 0)), $periods),
        ];
    }

    // -----------------------------------------------------------------------
    // Overview (no filters)
    // -----------------------------------------------------------------------

    private function getOverviewData(array $filters, ?int $exportLimit = null): array
    {
        [$dateFrom, $dateTo] = $this->parseDateRange($filters);
        $from = $dateFrom->toDateString();
        $to   = $dateTo->toDateString();

        $companiesCount  = Company::count();
        $portfoliosCount = Portfolio::count();

        $totalDue = (float) DB::table('customer_transactions')
            ->whereNull('deleted_at')->where('type', 'debit')
            ->whereBetween('transaction_date', [$from, $to])
            ->sum('amount');

        $totalCollected = (float) DB::table('collections')
            ->whereNull('deleted_at')->where('status', 'approved')
            ->whereBetween('collection_date', [$from, $to])
            ->sum('collected_amount');

        $collectionRate = $this->rate($totalDue, $totalCollected);

        // Top 6 companies by collected amount
        $topCollected = DB::table('collections')
            ->join('customers', 'collections.customer_id', '=', 'customers.id')
            ->join('companies', 'customers.company_id', '=', 'companies.id')
            ->whereNull('collections.deleted_at')->whereNull('customers.deleted_at')->whereNull('companies.deleted_at')
            ->where('collections.status', 'approved')
            ->whereBetween('collections.collection_date', [$from, $to])
            ->groupBy('companies.id', 'companies.name')
            ->selectRaw('companies.id, companies.name, SUM(collections.collected_amount) as total_collected')
            ->orderByDesc('total_collected')->limit(6)->get();

        $topDue = DB::table('customer_transactions')
            ->join('customers', 'customer_transactions.customer_id', '=', 'customers.id')
            ->whereNull('customer_transactions.deleted_at')->whereNull('customers.deleted_at')
            ->where('customer_transactions.type', 'debit')
            ->whereBetween('customer_transactions.transaction_date', [$from, $to])
            ->whereIn('customers.company_id', $topCollected->pluck('id'))
            ->groupBy('customers.company_id')
            ->selectRaw('customers.company_id, SUM(customer_transactions.amount) as total')
            ->get()->keyBy('company_id');

        $perfCategories = $topCollected->pluck('name')->toArray();
        $perfCollected  = $topCollected->map(fn($c) => round((float) $c->total_collected))->toArray();
        $perfRates      = $topCollected->map(function ($c) use ($topDue) {
            $due = (float) ($topDue->get($c->id)?->total ?? 0);
            return $this->rate($due, (float) $c->total_collected);
        })->toArray();

        // Portfolio distribution (count per top 5 companies)
        $portDist = DB::table('portfolios')
            ->join('companies', 'portfolios.company_id', '=', 'companies.id')
            ->whereNull('portfolios.deleted_at')->whereNull('companies.deleted_at')
            ->groupBy('companies.id', 'companies.name')
            ->selectRaw('companies.name, COUNT(portfolios.id) as cnt')
            ->orderByDesc('cnt')->limit(5)->get();

        // Top 5 portfolios for amounts chart
        $topPortCollected = DB::table('collections')
            ->join('customers', 'collections.customer_id', '=', 'customers.id')
            ->join('portfolios', 'customers.portfolio_id', '=', 'portfolios.id')
            ->whereNull('collections.deleted_at')->whereNull('customers.deleted_at')->whereNull('portfolios.deleted_at')
            ->where('collections.status', 'approved')
            ->whereBetween('collections.collection_date', [$from, $to])
            ->groupBy('portfolios.id', 'portfolios.name')
            ->selectRaw('portfolios.id, portfolios.name, SUM(collections.collected_amount) as total_collected')
            ->orderByDesc('total_collected')->limit(5)->get();

        $topPortDue = DB::table('customer_transactions')
            ->join('customers', 'customer_transactions.customer_id', '=', 'customers.id')
            ->whereNull('customer_transactions.deleted_at')->whereNull('customers.deleted_at')
            ->where('customer_transactions.type', 'debit')
            ->whereBetween('customer_transactions.transaction_date', [$from, $to])
            ->whereIn('customers.portfolio_id', $topPortCollected->pluck('id'))
            ->groupBy('customers.portfolio_id')
            ->selectRaw('customers.portfolio_id, SUM(customer_transactions.amount) as total')
            ->get()->keyBy('portfolio_id');

        // Insights
        $transactionsCount = DB::table('collections')
            ->whereNull('deleted_at')->where('status', 'approved')
            ->whereBetween('collection_date', [$from, $to])->count();

        $avgDebtAge = (int) DB::table('customers')
            ->whereNull('deleted_at')->whereNotNull('default_date')
            ->avg(DB::raw('DATEDIFF(CURDATE(), default_date)'));

        // Summary table (top 8 companies)
        $summaryCollected = DB::table('collections')
            ->join('customers', 'collections.customer_id', '=', 'customers.id')
            ->join('companies', 'customers.company_id', '=', 'companies.id')
            ->whereNull('collections.deleted_at')->whereNull('customers.deleted_at')->whereNull('companies.deleted_at')
            ->where('collections.status', 'approved')
            ->whereBetween('collections.collection_date', [$from, $to])
            ->groupBy('companies.id', 'companies.name')
            ->selectRaw('companies.id, companies.name, SUM(collections.collected_amount) as total_collected')
            ->orderByDesc('total_collected');
        $summaryCollected = $this->lim($summaryCollected, 8, $exportLimit)->get();

        $summaryDue = DB::table('customer_transactions')
            ->join('customers', 'customer_transactions.customer_id', '=', 'customers.id')
            ->whereNull('customer_transactions.deleted_at')->whereNull('customers.deleted_at')
            ->where('customer_transactions.type', 'debit')
            ->whereBetween('customer_transactions.transaction_date', [$from, $to])
            ->whereIn('customers.company_id', $summaryCollected->pluck('id'))
            ->groupBy('customers.company_id')
            ->selectRaw('customers.company_id, SUM(customer_transactions.amount) as total')
            ->get()->keyBy('company_id');

        $summaryRows = $summaryCollected->map(function ($c) use ($summaryDue) {
            $due       = (float) ($summaryDue->get($c->id)?->total ?? 0);
            $collected = (float) $c->total_collected;
            return [$c->name, number_format($due), number_format($collected), $this->rate($due, $collected) . '%'];
        })->toArray();

        // Details table: top 8 portfolios
        $allPorts = DB::table('portfolios')
            ->join('companies', 'portfolios.company_id', '=', 'companies.id')
            ->whereNull('portfolios.deleted_at')->whereNull('companies.deleted_at')
            ->select('portfolios.id', 'portfolios.name', DB::raw('companies.name as company_name'))
            ->get();

        $portCollected = $this->collectedByPortfolio($from, $to);
        $portDue2      = $this->dueByPortfolio($from, $to);
        $portCustCount = DB::table('customers')->whereNull('deleted_at')
            ->groupBy('portfolio_id')->selectRaw('portfolio_id, COUNT(*) as cnt')
            ->get()->keyBy('portfolio_id');

        $takeN       = $exportLimit === 0 ? PHP_INT_MAX : ($exportLimit ?? 8);
        $detailsRows = $allPorts
            ->filter(fn($p) => $portDue2->has($p->id) || $portCollected->has($p->id))
            ->sortByDesc(fn($p) => (float) ($portDue2->get($p->id)?->total ?? 0))
            ->take($takeN)->map(function ($p) use ($portDue2, $portCollected, $portCustCount) {
                $due       = (float) ($portDue2->get($p->id)?->total ?? 0);
                $collected = (float) ($portCollected->get($p->id)?->total ?? 0);
                return [
                    $p->name,
                    $p->company_name,
                    number_format((int) ($portCustCount->get($p->id)?->cnt ?? 0)),
                    number_format($due),
                    $this->rate($due, $collected) . '%',
                ];
            })->values()->toArray();

        return [
            'kpis' => [
                ['title' => 'عدد الشركات',               'value' => number_format($companiesCount),              'trend' => 'neutral', 'icon' => 'ti-building',     'color' => 'bg-kpi-orange'],
                ['title' => 'عدد المحافظ',                'value' => number_format($portfoliosCount),             'trend' => 'neutral', 'icon' => 'ti-folders',      'color' => 'bg-kpi-blue'],
                ['title' => 'إجمالي المبالغ المستحقة',   'value' => $this->fmt($totalDue),                       'trend' => 'neutral', 'icon' => 'ti-file-invoice', 'color' => 'bg-kpi-red'],
                ['title' => 'إجمالي المبالغ المحصلة',    'value' => $this->fmt($totalCollected),                 'trend' => 'neutral', 'icon' => 'ti-wallet',       'color' => 'bg-kpi-green'],
                ['title' => 'نسبة التحصيل',              'value' => $collectionRate . '%',                       'trend' => 'neutral', 'icon' => 'ti-chart-pie',    'color' => 'bg-kpi-purple'],
            ],
            'chartTitles'           => ['أداء التحصيل حسب الشركات', 'توزيع المحافظ حسب الشركات', 'المبالغ المستحقة والمحصلة حسب المحافظ'],
            'tableTitles'           => ['ملخص الشركات', 'تفاصيل المحافظ'],
            'performanceCategories' => $perfCategories,
            'performanceSeries'     => [
                ['name' => 'المحصل',       'type' => 'column', 'data' => $perfCollected],
                ['name' => 'نسبة التحصيل', 'type' => 'line',   'data' => $perfRates],
            ],
            'distributionLabels'    => $portDist->pluck('name')->toArray(),
            'distributionSeries'    => $portDist->pluck('cnt')->map(fn($v) => (int) $v)->toArray(),
            'amountCategories'      => $topPortCollected->pluck('name')->toArray(),
            'dueSeries'             => $topPortCollected->map(fn($p) => round((float) ($topPortDue->get($p->id)?->total ?? 0)))->toArray(),
            'collectedSeries'       => $topPortCollected->map(fn($p) => round((float) $p->total_collected))->toArray(),
            'insights'              => [
                ['label' => 'إجمالي المبالغ المستحقة', 'value' => $this->fmt($totalDue),         'icon' => 'ti-file-invoice',   'color' => 'bg-kpi-red'],
                ['label' => 'إجمالي المبالغ المحصلة',  'value' => $this->fmt($totalCollected),    'icon' => 'ti-wallet',         'color' => 'bg-kpi-green'],
                ['label' => 'متوسط عمر المستحقات',      'value' => $avgDebtAge . ' يوم',           'icon' => 'ti-calendar-time',  'color' => 'bg-kpi-orange'],
                ['label' => 'عدد المعاملات',            'value' => number_format($transactionsCount), 'icon' => 'ti-clipboard-list', 'color' => 'bg-kpi-blue'],
            ],
            'summaryHead'           => ['الشركة', 'إجمالي المستحق', 'إجمالي المحصل', 'نسبة التحصيل'],
            'summaryRows'           => $summaryRows,
            'detailsHead'           => ['المحفظة', 'الشركة', 'عدد العملاء', 'المستحق', 'نسبة التحصيل'],
            'detailsRows'           => $detailsRows,
        ];
    }

    // -----------------------------------------------------------------------
    // Company view
    // -----------------------------------------------------------------------

    private function getCompanyData(array $filters, ?int $exportLimit = null): array
    {
        [$dateFrom, $dateTo] = $this->parseDateRange($filters);
        $from      = $dateFrom->toDateString();
        $to        = $dateTo->toDateString();
        $companyId = (int) $filters['company_id'];

        $company = Company::find($companyId);
        if (!$company) {
            return $this->getOverviewData($filters);
        }

        $portfoliosCount = Portfolio::where('company_id', $companyId)->count();
        $customersCount  = Customer::where('company_id', $companyId)->count();

        $totalDue = (float) DB::table('customer_transactions')
            ->join('customers', 'customer_transactions.customer_id', '=', 'customers.id')
            ->whereNull('customer_transactions.deleted_at')->whereNull('customers.deleted_at')
            ->where('customer_transactions.type', 'debit')->where('customers.company_id', $companyId)
            ->whereBetween('customer_transactions.transaction_date', [$from, $to])
            ->sum('customer_transactions.amount');

        $totalCollected = (float) DB::table('collections')
            ->join('customers', 'collections.customer_id', '=', 'customers.id')
            ->whereNull('collections.deleted_at')->whereNull('customers.deleted_at')
            ->where('collections.status', 'approved')->where('customers.company_id', $companyId)
            ->whereBetween('collections.collection_date', [$from, $to])
            ->sum('collections.collected_amount');

        $collectionRate = $this->rate($totalDue, $totalCollected);

        $portfolios = Portfolio::where('company_id', $companyId)->select('id', 'name')->get();
        $portIds    = $portfolios->pluck('id');

        $portCollected = $this->collectedByPortfolio($from, $to, $companyId);
        $portDue       = $this->dueByPortfolio($from, $to, $companyId);

        $custCountByPort = DB::table('customers')->whereNull('deleted_at')
            ->whereIn('portfolio_id', $portIds)
            ->groupBy('portfolio_id')->selectRaw('portfolio_id, COUNT(*) as cnt')
            ->get()->keyBy('portfolio_id');

        $sorted     = $portfolios->sortByDesc(fn($p) => (float) ($portCollected->get($p->id)?->total ?? 0));
        $top6       = $sorted->take(6);

        $perfCategories = $top6->pluck('name')->values()->toArray();
        $perfCollected  = $top6->map(fn($p) => round((float) ($portCollected->get($p->id)?->total ?? 0)))->values()->toArray();
        $perfRates      = $top6->map(function ($p) use ($portCollected, $portDue) {
            $due       = (float) ($portDue->get($p->id)?->total ?? 0);
            $collected = (float) ($portCollected->get($p->id)?->total ?? 0);
            return $this->rate($due, $collected);
        })->values()->toArray();

        $distLabels  = $sorted->take(5)->pluck('name')->values()->toArray();
        $distSeries  = $sorted->take(5)->map(fn($p) => (int) ($custCountByPort->get($p->id)?->cnt ?? 0))->values()->toArray();

        $lateCount = $portfolios->filter(function ($p) use ($portCollected, $portDue) {
            $due       = (float) ($portDue->get($p->id)?->total ?? 0);
            $collected = (float) ($portCollected->get($p->id)?->total ?? 0);
            return $due > 0 && $this->rate($due, $collected) < 50;
        })->count();

        $avgCollectionPeriod = (int) DB::table('customers')
            ->whereNull('deleted_at')->where('company_id', $companyId)->whereNotNull('default_date')
            ->avg(DB::raw('DATEDIFF(CURDATE(), default_date)'));

        $avgPeriodByPort = DB::table('customers')
            ->whereNull('deleted_at')->whereIn('portfolio_id', $portIds)->whereNotNull('default_date')
            ->groupBy('portfolio_id')->selectRaw('portfolio_id, AVG(DATEDIFF(CURDATE(), default_date)) as avg_days')
            ->get()->keyBy('portfolio_id');

        $takeN       = $exportLimit === 0 ? PHP_INT_MAX : ($exportLimit ?? 8);
        $summaryRows = $sorted->take($takeN)->map(function ($p) use ($portDue, $portCollected) {
            $due       = (float) ($portDue->get($p->id)?->total ?? 0);
            $collected = (float) ($portCollected->get($p->id)?->total ?? 0);
            return [$p->name, number_format($due), number_format($collected), $this->rate($due, $collected) . '%'];
        })->values()->toArray();

        $detailsRows = $sorted->take($takeN)->map(function ($p) use ($portDue, $portCollected, $custCountByPort, $avgPeriodByPort) {
            $due       = (float) ($portDue->get($p->id)?->total ?? 0);
            $collected = (float) ($portCollected->get($p->id)?->total ?? 0);
            return [
                $p->name,
                number_format((int) ($custCountByPort->get($p->id)?->cnt ?? 0)),
                number_format($due),
                number_format($collected),
                (int) ($avgPeriodByPort->get($p->id)?->avg_days ?? 0) . ' يوم',
            ];
        })->values()->toArray();

        return [
            'kpis' => [
                ['title' => 'عدد المحافظ التابعة',       'value' => number_format($portfoliosCount),  'trend' => 'neutral', 'icon' => 'ti-folders',      'color' => 'bg-kpi-orange'],
                ['title' => 'عدد العملاء',                'value' => number_format($customersCount),   'trend' => 'neutral', 'icon' => 'ti-users',        'color' => 'bg-kpi-blue'],
                ['title' => 'إجمالي المبالغ المستحقة',   'value' => $this->fmt($totalDue),            'trend' => 'neutral', 'icon' => 'ti-file-invoice', 'color' => 'bg-kpi-red'],
                ['title' => 'إجمالي المبالغ المحصلة',    'value' => $this->fmt($totalCollected),      'trend' => 'neutral', 'icon' => 'ti-wallet',       'color' => 'bg-kpi-green'],
                ['title' => 'نسبة التحصيل',              'value' => $collectionRate . '%',            'trend' => 'neutral', 'icon' => 'ti-chart-pie',    'color' => 'bg-kpi-purple'],
            ],
            'chartTitles'           => ['أداء المحافظ داخل الشركة', 'توزيع العملاء في المحافظ', 'المبالغ حسب محافظ الشركة'],
            'tableTitles'           => ['ملخص المحافظ التابعة للشركة', 'تفاصيل المحافظ'],
            'performanceCategories' => $perfCategories,
            'performanceSeries'     => [
                ['name' => 'المحصل',       'type' => 'column', 'data' => $perfCollected],
                ['name' => 'نسبة التحصيل', 'type' => 'line',   'data' => $perfRates],
            ],
            'distributionLabels'    => $distLabels,
            'distributionSeries'    => $distSeries,
            'amountCategories'      => $top6->pluck('name')->values()->toArray(),
            'dueSeries'             => $top6->map(fn($p) => round((float) ($portDue->get($p->id)?->total ?? 0)))->values()->toArray(),
            'collectedSeries'       => $top6->map(fn($p) => round((float) ($portCollected->get($p->id)?->total ?? 0)))->values()->toArray(),
            'insights'              => [
                ['label' => 'متوسط فترة التحصيل', 'value' => $avgCollectionPeriod . ' يوم',    'icon' => 'ti-clock',               'color' => 'bg-kpi-purple'],
                ['label' => 'المحافظ المتأخرة',    'value' => $lateCount . ' محافظ',            'icon' => 'ti-alert-triangle',      'color' => 'bg-kpi-red'],
                ['label' => 'أفضل محفظة أداء',    'value' => $sorted->first()?->name ?? '-',   'icon' => 'ti-trophy',              'color' => 'bg-kpi-green'],
                ['label' => 'أعلى محفظة مخاطرة',  'value' => $sorted->last()?->name ?? '-',    'icon' => 'ti-shield-exclamation',  'color' => 'bg-kpi-orange'],
            ],
            'summaryHead'           => ['المحفظة', 'المستحق', 'المحصل', 'نسبة التحصيل'],
            'summaryRows'           => $summaryRows,
            'detailsHead'           => ['المحفظة', 'عدد العملاء', 'المستحق', 'المحصل', 'متوسط التحصيل'],
            'detailsRows'           => $detailsRows,
        ];
    }

    // -----------------------------------------------------------------------
    // Portfolio view
    // -----------------------------------------------------------------------

    private function getPortfolioData(array $filters, ?int $exportLimit = null): array
    {
        [$dateFrom, $dateTo] = $this->parseDateRange($filters);
        $from        = $dateFrom->toDateString();
        $to          = $dateTo->toDateString();
        $portfolioId = (int) $filters['portfolio_id'];

        $portfolio = Portfolio::with('company')->find($portfolioId);
        if (!$portfolio) {
            return $this->getOverviewData($filters);
        }

        $customersCount = Customer::where('portfolio_id', $portfolioId)->count();

        $totalDue = (float) DB::table('customer_transactions')
            ->join('customers', 'customer_transactions.customer_id', '=', 'customers.id')
            ->whereNull('customer_transactions.deleted_at')->whereNull('customers.deleted_at')
            ->where('customer_transactions.type', 'debit')->where('customers.portfolio_id', $portfolioId)
            ->whereBetween('customer_transactions.transaction_date', [$from, $to])
            ->sum('customer_transactions.amount');

        $totalCollected = (float) DB::table('collections')
            ->join('customers', 'collections.customer_id', '=', 'customers.id')
            ->whereNull('collections.deleted_at')->whereNull('customers.deleted_at')
            ->where('collections.status', 'approved')->where('customers.portfolio_id', $portfolioId)
            ->whereBetween('collections.collection_date', [$from, $to])
            ->sum('collections.collected_amount');

        $collectionRate = $this->rate($totalDue, $totalCollected);

        // Daily/weekly trend
        $dailyCollected = DB::table('collections')
            ->join('customers', 'collections.customer_id', '=', 'customers.id')
            ->whereNull('collections.deleted_at')->whereNull('customers.deleted_at')
            ->where('collections.status', 'approved')->where('customers.portfolio_id', $portfolioId)
            ->whereBetween('collections.collection_date', [$from, $to])
            ->groupBy('collections.collection_date')
            ->selectRaw('collection_date, SUM(collected_amount) as total')
            ->orderBy('collection_date')->get();

        $dailyDue = DB::table('customer_transactions')
            ->join('customers', 'customer_transactions.customer_id', '=', 'customers.id')
            ->whereNull('customer_transactions.deleted_at')->whereNull('customers.deleted_at')
            ->where('customer_transactions.type', 'debit')->where('customers.portfolio_id', $portfolioId)
            ->whereBetween('customer_transactions.transaction_date', [$from, $to])
            ->groupBy('customer_transactions.transaction_date')
            ->selectRaw('transaction_date, SUM(amount) as total')
            ->orderBy('transaction_date')->get()->keyBy('transaction_date');

        $diffDays   = $dateTo->diffInDays($dateFrom);
        $useWeekly  = $diffDays > 30;

        if ($useWeekly) {
            $grouped = $dailyCollected->groupBy(fn($d) => Carbon::parse($d->collection_date)->startOfWeek()->format('d/m/Y'));
        } else {
            $grouped = $dailyCollected->groupBy(fn($d) => Carbon::parse($d->collection_date)->format('d/m'));
        }

        $trendCategories = $grouped->keys()->values()->toArray();
        $trendCollected  = $grouped->map(fn($items) => round((float) $items->sum('total')))->values()->toArray();
        $trendRates      = $grouped->map(function ($items) use ($dailyDue) {
            $totalC = (float) $items->sum('total');
            $totalD = 0.0;
            foreach ($items as $item) {
                $totalD += (float) ($dailyDue->get($item->collection_date)?->total ?? 0);
            }
            return $this->rate($totalD, $totalC);
        })->values()->toArray();

        // Customer status distribution
        $statuses = DB::table('customers')
            ->whereNull('deleted_at')->where('portfolio_id', $portfolioId)
            ->selectRaw("
                SUM(CASE WHEN default_date IS NULL THEN 1 ELSE 0 END) as regular,
                SUM(CASE WHEN default_date IS NOT NULL AND DATEDIFF(CURDATE(), default_date) BETWEEN 1 AND 30 THEN 1 ELSE 0 END) as late30,
                SUM(CASE WHEN default_date IS NOT NULL AND DATEDIFF(CURDATE(), default_date) BETWEEN 31 AND 60 THEN 1 ELSE 0 END) as late60,
                SUM(CASE WHEN default_date IS NOT NULL AND DATEDIFF(CURDATE(), default_date) BETWEEN 61 AND 90 THEN 1 ELSE 0 END) as late90,
                SUM(CASE WHEN default_date IS NOT NULL AND DATEDIFF(CURDATE(), default_date) > 90 THEN 1 ELSE 0 END) as late90plus
            ")->first();

        $lateCount = DB::table('customers')
            ->whereNull('deleted_at')->where('portfolio_id', $portfolioId)->whereNotNull('default_date')->count();

        $avgDebtAge = (int) DB::table('customers')
            ->whereNull('deleted_at')->where('portfolio_id', $portfolioId)->whereNotNull('default_date')
            ->avg(DB::raw('DATEDIFF(CURDATE(), default_date)'));

        // Highest debt customer (net = debit - credit)
        $topDebtCust = DB::table('customer_transactions')
            ->join('customers', 'customer_transactions.customer_id', '=', 'customers.id')
            ->whereNull('customer_transactions.deleted_at')->whereNull('customers.deleted_at')
            ->where('customers.portfolio_id', $portfolioId)
            ->groupBy('customers.id')
            ->selectRaw("customers.id,
                SUM(CASE WHEN type='debit'  THEN amount ELSE 0 END) -
                SUM(CASE WHEN type='credit' THEN amount ELSE 0 END) as net_debt")
            ->orderByDesc('net_debt')->first();

        $highestDebt = $topDebtCust ? max(0.0, (float) $topDebtCust->net_debt) : 0.0;

        $pendingCollections = (float) DB::table('collections')
            ->join('customers', 'collections.customer_id', '=', 'customers.id')
            ->whereNull('collections.deleted_at')->whereNull('customers.deleted_at')
            ->where('collections.status', 'pending')->where('customers.portfolio_id', $portfolioId)
            ->sum('collections.collected_amount');

        // Amounts by range
        $netDebts = DB::table('customer_transactions')
            ->join('customers', 'customer_transactions.customer_id', '=', 'customers.id')
            ->whereNull('customer_transactions.deleted_at')->whereNull('customers.deleted_at')
            ->where('customers.portfolio_id', $portfolioId)
            ->groupBy('customers.id')
            ->selectRaw("SUM(CASE WHEN type='debit' THEN amount ELSE 0 END) -
                         SUM(CASE WHEN type='credit' THEN amount ELSE 0 END) as net")
            ->get()->pluck('net')->map(fn($v) => max(0.0, (float) $v));

        $ranges       = [[0, 100000], [100000, 500000], [500000, 1000000], [1000000, 5000000], [5000000, PHP_INT_MAX]];
        $rangeLabels  = ['حتى 100K', '100K - 500K', '500K - 1M', '1M - 5M', 'أكثر من 5M'];
        $rangeDue     = array_map(fn($r) => round($netDebts->filter(fn($d) => $d >= $r[0] && $d < $r[1])->sum()), $ranges);
        $rangeCollect = array_map(fn($v) => round($v * ($collectionRate / 100), 2), $rangeDue);

        // Top customers summary
        $topCustomers = DB::table('customer_transactions')
            ->join('customers', 'customer_transactions.customer_id', '=', 'customers.id')
            ->whereNull('customer_transactions.deleted_at')->whereNull('customers.deleted_at')
            ->where('customers.portfolio_id', $portfolioId)
            ->groupBy('customers.id', 'customers.name')
            ->selectRaw("customers.id, customers.name,
                SUM(CASE WHEN customer_transactions.type='debit'  THEN customer_transactions.amount ELSE 0 END) as total_debit,
                SUM(CASE WHEN customer_transactions.type='credit' THEN customer_transactions.amount ELSE 0 END) as total_credit")
            ->havingRaw("SUM(CASE WHEN customer_transactions.type='debit' THEN customer_transactions.amount ELSE 0 END) > SUM(CASE WHEN customer_transactions.type='credit' THEN customer_transactions.amount ELSE 0 END)")
            ->orderByRaw("(SUM(CASE WHEN customer_transactions.type='debit' THEN customer_transactions.amount ELSE 0 END) - SUM(CASE WHEN customer_transactions.type='credit' THEN customer_transactions.amount ELSE 0 END)) DESC");
        $topCustomers = $this->lim($topCustomers, 8, $exportLimit)->get();

        $ageByCustomer = DB::table('customers')
            ->whereNull('deleted_at')->where('portfolio_id', $portfolioId)
            ->whereIn('id', $topCustomers->pluck('id'))->whereNotNull('default_date')
            ->selectRaw('id, DATEDIFF(CURDATE(), default_date) as debt_age')
            ->get()->keyBy('id');

        $summaryRows = $topCustomers->map(function ($c) use ($ageByCustomer) {
            $due       = (float) $c->total_debit;
            $collected = (float) $c->total_credit;
            return [
                $c->name,
                number_format(max(0, $due - $collected)),
                $this->rate($due, $collected) . '%',
                (int) ($ageByCustomer->get($c->id)?->debt_age ?? 0) . ' يوم',
            ];
        })->toArray();

        $dailyRate = $diffDays > 0 ? $this->fmt($totalCollected / $diffDays) : $this->fmt(0);

        return [
            'kpis' => [
                ['title' => 'الشركة المالكة للمحفظة',  'value' => $portfolio->company?->name ?? '-', 'trend' => 'neutral', 'icon' => 'ti-building-bank', 'color' => 'bg-kpi-orange'],
                ['title' => 'عدد العملاء',              'value' => number_format($customersCount),    'trend' => 'neutral', 'icon' => 'ti-users',         'color' => 'bg-kpi-blue'],
                ['title' => 'إجمالي المبالغ المستحقة', 'value' => $this->fmt($totalDue),             'trend' => 'neutral', 'icon' => 'ti-file-invoice',  'color' => 'bg-kpi-red'],
                ['title' => 'إجمالي المبالغ المحصلة',  'value' => $this->fmt($totalCollected),       'trend' => 'neutral', 'icon' => 'ti-wallet',        'color' => 'bg-kpi-green'],
                ['title' => 'نسبة التحصيل',            'value' => $collectionRate . '%',             'trend' => 'neutral', 'icon' => 'ti-chart-pie',     'color' => 'bg-kpi-purple'],
            ],
            'chartTitles'           => ['اتجاه التحصيل للمحفظة', 'توزيع العملاء حسب حالة السداد', 'المستحق والمحصل  '],
            'tableTitles'           => ['أعلى العملاء في المحفظة', 'ملخص المحفظة'],
            'performanceCategories' => $trendCategories,
            'performanceSeries'     => [
                ['name' => 'المحصل',       'type' => 'column', 'data' => $trendCollected],
                ['name' => 'نسبة التحصيل', 'type' => 'line',   'data' => $trendRates],
            ],
            'distributionLabels'    => ['منتظم', 'متأخر 30 يوم', 'متأخر 60 يوم', 'متأخر 90 يوم', 'أكثر من 90 يوم'],
            'distributionSeries'    => [
                (int) ($statuses->regular   ?? 0),
                (int) ($statuses->late30    ?? 0),
                (int) ($statuses->late60    ?? 0),
                (int) ($statuses->late90    ?? 0),
                (int) ($statuses->late90plus ?? 0),
            ],
            'amountCategories'      => $rangeLabels,
            'dueSeries'             => $rangeDue,
            'collectedSeries'       => $rangeCollect,
            'insights'              => [
                ['label' => 'العملاء المتأخرون',    'value' => number_format($lateCount),        'icon' => 'ti-file-alert',    'color' => 'bg-kpi-red'],
                ['label' => 'متوسط عمر المديونية',  'value' => $avgDebtAge . ' يوم',             'icon' => 'ti-calendar-time', 'color' => 'bg-kpi-orange'],
                ['label' => 'أعلى مديونية عميل',   'value' => $this->fmt($highestDebt),         'icon' => 'ti-user-dollar',   'color' => 'bg-kpi-purple'],
                ['label' => 'المبالغ تحت التحصيل', 'value' => $this->fmt($pendingCollections),  'icon' => 'ti-cash-banknote', 'color' => 'bg-kpi-green'],
            ],
            'summaryHead'           => ['العميل', 'صافي المديونية', 'نسبة التحصيل', 'عمر المديونية'],
            'summaryRows'           => $summaryRows,
            'detailsHead'           => ['المؤشر', 'القيمة'],
            'detailsRows'           => [
                ['إجمالي العملاء المنتظمين',  number_format((int) ($statuses->regular ?? 0))],
                ['إجمالي العملاء المتأخرين',  number_format($lateCount)],
                ['معدل التحصيل اليومي',       $dailyRate],
                ['إجمالي تحت المعالجة',       $this->fmt($pendingCollections)],
            ],
        ];
    }
}
