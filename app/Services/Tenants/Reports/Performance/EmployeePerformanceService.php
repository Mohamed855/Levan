<?php

namespace App\Services\Tenants\Reports\Performance;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use App\Models\Tenants\BusinessCenter\Customers\Task\Task;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Exports\Tenants\Reports\Performance\EmployeePerformanceExport;
use App\Jobs\Tenants\Reports\Performance\ExportEmployeePerformanceJob;
use App\Models\Tenants\BusinessCenter\Company\CompanyTarget;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\FinancialCollection\Target\CollectionTarget;
use Carbon\Carbon;
use Illuminate\Support\Collection as IlluminateCollection;


class EmployeePerformanceService extends BasePerformanceService
{

    // المدخل الرئيسي — يُنفّذ 5 استعلامات فقط بغض النظر عن عدد الموظفين.
    public function getEmployeesPerformance(array $filters = []): array
    {
        $range = $this->normalizeDateRange($filters);
        $users = $this->queryUsers($filters)->pluck('name', 'id');

        if ($users->isEmpty()) {
            return [];
        }

        $ids = $users->keys()->all();


        $portfolio   = $this->queryPortfolio($ids, $filters); // عدد العملاء + إجمالي المحفظة + إجمالي الديون الأصلية لكل موظف
        $collections = $this->queryCollections($ids, $filters, $range['from'], $range['to']); // إجمالي التحصيل + عدد العملاء المسددين + متوسط السداد.
        $follows     = $this->queryFollows($ids, $filters, $range['start_dt'], $range['end_dt']); // المتابعات + الوعود + غير المتواصل معهم + نسبة تحقيق الوعد.
        $overdueTasks = $this->queryOverdueTasks($ids, $filters, $range['from'], $range['to']);
        $companies   = $this->queryCompanyNames($ids, $filters); // أسماء الشركات المرتبطة بكل موظف (للفلاتر التي تشمل أكثر من شركة/محفظة)
        $targets     = $this->queryTargets($ids, $filters, $range['from'], $range['to']); // المستهدف الشهري لكل موظف بناءً على المحفظة والشركة (مع الأخذ بعين الاعتبار التغيرات خلال الفترة)
        // ─────────────────────────────────────────────────────────────

        return $users->map(function (string $name, int $uid) use ($portfolio, $collections, $follows, $overdueTasks, $companies, $targets): array {
            $portfolioArr     = $portfolio[$uid]   ?? [];
            $collectionsArr   = $collections[$uid] ?? [];
            $followsArr       = $follows[$uid]     ?? [];
            $totalCollection = (float) ($collectionsArr['total'] ?? 0);
            $target          = (float) ($targets[$uid] ?? 0);

            return [
                'employee_name'            => $name,
                'company_name'             => $companies[$uid] ?? 'غير محدد',
                'customers_count'          => (int)   ($portfolioArr['customers_count'] ?? 0),
                'total_portfolio'          => (float) ($portfolioArr['net_portfolio']   ?? 0),
                'total_gross_debt'         => (float) ($portfolioArr['gross_debt']      ?? 0),

                'total_collection'         => $totalCollection, // إجمالي التحصيل
                'avg_payment'              => (float) ($collectionsArr['avg_payment']     ?? 0), // متوسط السداد
                'paid_customers'           => (int)   ($collectionsArr['paid_customers']  ?? 0), // عدد العملاء المسددين

                'follows_count'            => (int)   ($followsArr['follows_count']    ?? 0),
                'avg_calls'                => (float) ($followsArr['avg_calls']       ?? 0), // متوسط المتابعات
                'not_contacted'            => (int)   ($followsArr['not_contacted']   ?? 0), // غير المتواصل معهم
                'overdue_tasks'            => (int)   ($overdueTasks[$uid]            ?? 0),
                'promises_count'           => (int)   ($followsArr['promises_count']  ?? 0), // عدد الوعود
                'promises_amount'          => (float) ($followsArr['promises_amount'] ?? 0), // مبلغ الوعود
                'promise_achievement_rate' => (float) ($followsArr['promise_rate']    ?? 0), // نسبة تحقيق الوعد

                'target_amount'           => $target,
                'target_achievement_rate' => $target > 0
                    ? round(($totalCollection / $target) * 100, 2)
                    : 0.0,

            ];
        })->values()->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Employees Statistics - إحصائيات الموظفين
    |--------------------------------------------------------------------------
    */
    public function getStatistics(array $performance): array
    {
        if (empty($performance)) {
            return array_fill_keys([
                'total_employees',
                'total_customers',
                'total_portfolio',
                'total_collection',
                'collection_percentage',
                'employees_achieving_target',
                'average_performance',
            ], 0);
        }

        $data            = collect($performance);
        $totalGrossDebt  = $data->sum('total_gross_debt');
        $totalCollection = $data->sum('total_collection');

        return [
            'total_employees'            => $data->count(),
            'total_customers'            => $data->sum('customers_count'),
            'total_portfolio'            => $data->sum('total_portfolio'),
            'total_collection'           => $totalCollection,
            'collection_percentage'      => $totalGrossDebt > 0 ? round(($totalCollection / $totalGrossDebt) * 100, 2) : 0.0, // نسبة التحصيل من إجمالي الديون الأصلية
            'employees_achieving_target' => $data->filter(fn($e) => $e['target_achievement_rate'] >= 100)->count(), // عدد الموظفين الذين حققوا المستهدف أو تجاوزوه
            'average_performance'        => round((float) $data->avg('target_achievement_rate'), 2), // متوسط نسبة تحقيق المستهدف بين جميع الموظفين
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Get Employees Distribution - توزيع الموظفين حسب الأداء
    |--------------------------------------------------------------------------
    */
    public function getDistribution(array $performance): array
    {
        if (empty($performance)) {
            return ['excellent' => 0, 'good' => 0, 'average' => 0, 'poor' => 0];
        }

        $employees = collect($performance);

        return [
            'excellent' => $employees->filter(fn($e) => $e['target_achievement_rate'] >= 100)->count(),
            'good'      => $employees->filter(fn($e) => $e['target_achievement_rate'] >= 75
                && $e['target_achievement_rate'] < 100)->count(),
            'average'   => $employees->filter(fn($e) => $e['target_achievement_rate'] >= 50
                && $e['target_achievement_rate'] < 75)->count(),
            'poor'      => $employees->filter(fn($e) => $e['target_achievement_rate'] < 50)->count(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Get Top Performers - أفضل الموظفين أداءً
    |--------------------------------------------------------------------------
    */
    public function getTopPerformers(array $performance, int $limit = 10): array
    {
        return collect($performance)
            ->sortByDesc('target_achievement_rate')
            ->take($limit)
            ->values()
            ->all();
    }


    // ── أضفهما في قسم Public Methods ──────────────────────────────────────────

    public function getChartData(array $performance): array
    {
        $data = collect($performance);

        // أعلى 10 بالتحصيل
        $byCollection = $data->sortByDesc('total_collection')->take(10);

        // أعلى 10 بعدد المتابعات الفعلي من جدول customer_follows
        $byFollows = $data
            ->filter(fn($e) => ($e['follows_count'] ?? 0) > 0)
            ->sortByDesc('follows_count')
            ->take(10);

        // أعلى 10 بالعملاء غير المتواصل معهم
        $byNotContacted = $data->sortByDesc('not_contacted')->take(10);

        // أعلى 10 بالمهام المتأخرة حسب تعريف النظام
        $byOverdueTasks = $data
            ->filter(fn($e) => ($e['overdue_tasks'] ?? 0) > 0)
            ->sortByDesc('overdue_tasks')
            ->take(10);

        // أعلى 10 بتحقيق الهدف — فقط من لديهم هدف
        $byTargets = $data
            ->filter(fn($e) => ($e['target_amount'] ?? 0) > 0)
            ->sortByDesc('target_achievement_rate')
            ->take(10);

        return [
            'collections'   => $this->chartSeries($byCollection,  'employee_name', 'total_collection'),
            'follows'       => $this->chartSeries($byFollows,      'employee_name', 'follows_count'),
            'not_contacted' => $this->chartSeries($byNotContacted, 'employee_name', 'not_contacted'),
            'overdue_tasks' => $this->chartSeries($byOverdueTasks, 'employee_name', 'overdue_tasks'),
            'targets'       => $this->chartSeries($byTargets,      'employee_name', 'target_achievement_rate'),
        ];
    }

    private function chartSeries(\Illuminate\Support\Collection $items, string $labelKey, string $valueKey): array
    {
        return [
            'labels' => $items->pluck($labelKey)->values()->all(),
            'data'   => $items->pluck($valueKey)->map(fn($v) => round((float) $v, 2))->values()->all(),
        ];
    }
    /*
    |--------------------------------------------------------------------------
    | Get Available Export Fields - الحقول المتاحة للتصدير
    |--------------------------------------------------------------------------
    */
    public function getAvailableExportFields(): array
    {
        return [
            'fields' => [
                ['key' => 'employee_name', 'label' => 'اسم الموظف', 'selected' => true],
                ['key' => 'company_name', 'label' => 'الشركة', 'selected' => true],
                ['key' => 'customers_count', 'label' => 'عدد العملاء', 'selected' => true],
                ['key' => 'total_portfolio', 'label' => 'إجمالي المحفظة', 'selected' => true],
                ['key' => 'avg_payment', 'label' => 'متوسط السداد', 'selected' => true],
                ['key' => 'paid_customers', 'label' => 'عدد العملاء المسددين', 'selected' => true],
                ['key' => 'avg_calls', 'label' => 'متوسط المكالمات', 'selected' => false],
                ['key' => 'not_contacted', 'label' => 'لم يتم التواصل', 'selected' => false],
                ['key' => 'overdue_tasks', 'label' => 'المهام المتأخرة', 'selected' => false],
                ['key' => 'promises_count', 'label' => 'عدد الوعود', 'selected' => false],
                ['key' => 'promises_amount', 'label' => 'مبلغ الوعد بالسداد', 'selected' => false],
                ['key' => 'promise_achievement_rate', 'label' => 'نسبة تحقيق الوعد', 'selected' => true],
                ['key' => 'target_achievement_rate', 'label' => 'نسبة تحقيق المستهدف', 'selected' => true],
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Records Count - عدد السجلات
    |--------------------------------------------------------------------------
    */
    public function getRecordsCount(array $filters = []): int
    {
        return count($this->getEmployeesPerformance($filters));
    }

    /*
    |--------------------------------------------------------------------------
    | Export Filtered Performance To Excel - تصدير مخصص
    |--------------------------------------------------------------------------
    */
    public function exportFilteredPerformanceToExcel(array $filters, array $selectedFields = []): string
    {
        $employeesPerformance = $this->getEmployeesPerformance($filters);

        $fileName = 'تقرير_أداء_الموظفين_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        Excel::store(
            new EmployeePerformanceExport($employeesPerformance, $filters, $selectedFields),
            'exports/' . $fileName,
            'tenant_public'
        );

        return $fileName;
    }

    /*
    |--------------------------------------------------------------------------
    | Export Performance Report To PDF - تصدير PDF
    |--------------------------------------------------------------------------
    */
    public function exportPerformanceReportToPDF(array $filters): BinaryFileResponse
    {
        try {
            $startDate = $filters['date_from'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = $filters['date_to'] ?? Carbon::now()->format('Y-m-d');

            $employeesPerformance = $this->getEmployeesPerformance($filters);
            $dateRange = $this->getDateRangeText($startDate, $endDate);

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L',
                'orientation' => 'L',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'margin_header' => 0,
                'margin_footer' => 0,
                'default_font' => 'almarai',
                'fontDir' => array_merge(
                    (new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'],
                    [public_path('static/fonts/almarai')]
                ),
                'fontdata' => array_merge(
                    (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'],
                    ['almarai' => [
                        'R' => 'Almarai-Regular.ttf',
                        'B' => 'Almarai-Bold.ttf',
                        'useOTL' => 0xFF,
                        'useKashida' => 75,
                    ]]
                ),
                'default_font_size' => 9,
                'tempDir' => storage_path('app/public/temp'),
            ]);

            $mpdf->SetDirectionality('rtl');
            $mpdf->SetHTMLHeader('');
            $mpdf->SetTopMargin(10);

            $html = view('tenants.reports.performance.employees_pdf', compact(
                'employeesPerformance',
                'dateRange'
            ))->render();

            $mpdf->WriteHTML($html);

            $fileName = 'تقرير_أداء_الموظفين_' . now()->format('Y_m_d_H_i_s') . '.pdf';

            $tempDir = storage_path('app/public/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempPath = $tempDir . '/' . $fileName;
            $mpdf->Output($tempPath, 'F');

            return response()->download($tempPath)->deleteFileAfterSend();
        } catch (\Throwable $e) {
            Log::error('exportEmployeePerformanceToPDF error', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Start Async Export - بدء تصدير غير متزامن
    |--------------------------------------------------------------------------
    */
    public function startAsyncExport(array $filters, array $selectedFields, int $userId): array
    {
        $count = $this->getRecordsCount($filters);

        if ($count === 0) {
            return [
                'success' => false,
                'message' => 'لا توجد بيانات للتصدير',
                'count' => 0
            ];
        }

        $job = new ExportEmployeePerformanceJob($filters, $selectedFields, $userId);
        dispatch($job);

        return [
            'success' => true,
            'export_id' => $job->getExportId(),
            'count' => $count,
            'message' => $count > self::LARGE_EXPORT_THRESHOLD
                ? "سيتم تصدير {$count} سجل. سيتم إشعارك عند الانتهاء"
                : "جاري تصدير {$count} سجل..."
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Export Progress - تتبع تقدم التصدير
    |--------------------------------------------------------------------------
    */
    public function getExportProgress(string $exportId): array
    {
        $progress = Cache::get("employee_performance_export_progress_{$exportId}");
        $result = Cache::get("employee_performance_export_result_{$exportId}");

        if ($result) {
            return array_merge(['export_id' => $exportId], $result);
        }

        if ($progress) {
            return array_merge(['export_id' => $exportId, 'status' => 'processing'], $progress);
        }

        return [
            'export_id' => $exportId,
            'status' => 'not_found',
            'message' => 'لم يتم العثور على عملية التصدير'
        ];
    }


    /*
    |============================================================================
    |============================================================================
    |                         Private Methods
    |============================================================================
    |============================================================================
    */
    private function getNormalizedDateRange(array $filters): array
    {
        $startDate = $filters['date_from'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $filters['date_to'] ?? Carbon::now()->format('Y-m-d');

        return [
            'date_from' => Carbon::parse($startDate)->format('Y-m-d'),
            'date_to' => Carbon::parse($endDate)->format('Y-m-d'),
            'start_of_day' => Carbon::parse($startDate)->startOfDay(),
            'end_of_day' => Carbon::parse($endDate)->endOfDay(),
        ];
    }

    private function getFilteredUsersQuery(array $filters): Builder
    {
        $query = User::select('id', 'name', 'team_id', 'branch_id')
            ->whereHas('roles', fn($q) => $q->where('name', '!=', 'Super Admin'));

        if (!empty($filters['team_id'])) {
            $query->where('team_id', $filters['team_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('id', $filters['user_id']);
        }

        if (!empty($filters['company_id']) || !empty($filters['portfolio_id'])) {
            $query->whereHas('managedCustomers', function ($customerQuery) use ($filters) {
                if (!empty($filters['company_id'])) {
                    $customerQuery->where('company_id', $filters['company_id']);
                }

                if (!empty($filters['portfolio_id'])) {
                    $customerQuery->where('portfolio_id', $filters['portfolio_id']);
                }
            });
        }

        return $query;
    }

    private function getFilteredCustomerQuery(array $filters): Builder
    {
        $query = Customer::query();
        $this->applyFilters($query, $filters);

        return $query;
    }

    private function getFilteredFollowQuery(int $userId, Carbon $startDateTime, Carbon $endDateTime, array $filters): Builder
    {
        $query = CustomerFollow::where('created_by', $userId)
            ->whereBetween('created_at', [$startDateTime, $endDateTime]);

        if (!empty($filters['company_id']) || !empty($filters['portfolio_id'])) {
            $query->whereHas('customer', function ($customerQuery) use ($filters) {
                if (!empty($filters['company_id'])) {
                    $customerQuery->where('company_id', $filters['company_id']);
                }

                if (!empty($filters['portfolio_id'])) {
                    $customerQuery->where('portfolio_id', $filters['portfolio_id']);
                }
            });
        }

        return $query;
    }

    private function calculateEmployeeTarget(int $userId, array $filters, string $startDate, string $endDate): float
    {
        $userFilters = array_merge($filters, ['user_id' => $userId]);

        $portfolioByCompany = $this->getFilteredCustomerQuery($userFilters)
            ->select('company_id')
            ->selectRaw('
                SUM((SELECT COALESCE(SUM(CASE WHEN type="debit" THEN amount WHEN type="credit" THEN -amount ELSE 0 END), 0)
                 FROM customer_transactions
                 WHERE customer_transactions.customer_id = customers.id)) as total_portfolio
            ')
            ->groupBy('company_id')
            ->get();

        $target = 0.0;

        foreach ($portfolioByCompany as $companyPortfolio) {
            $totalPortfolio = (float) $companyPortfolio->total_portfolio;

            if (!$companyPortfolio->company_id || $totalPortfolio <= 0) {
                continue;
            }

            $targetPercentage = $this->getCompanyTargetPercentage((int) $companyPortfolio->company_id, $startDate, $endDate);
            $target += $totalPortfolio * ($targetPercentage / 100);
        }

        return $target;
    }

    private function getEmployeeCompanyNames(int $userId, array $filters): string
    {
        $userFilters = array_merge($filters, ['user_id' => $userId]);

        $companyNames = $this->getFilteredCustomerQuery($userFilters)
            ->with('company:id,name')
            ->get()
            ->pluck('company.name')
            ->filter()
            ->unique()
            ->values();

        return $companyNames->isNotEmpty()
            ? $companyNames->implode(', ')
            : 'غير محدد';
    }















    /*
    |============================================================================
    |============================================================================
    |                               Functions
    |============================================================================
    |============================================================================
    */
    private function normalizeDateRange(array $filters): array
    {
        $today = now()->format('Y-m-d');

        $from = $filters['date_from'] ?? now()->startOfMonth()->format('Y-m-d');
        $to   = $filters['date_to']   ?? $today;

        // الإصلاح: لا تتجاوز اليوم مطلقاً
        // يضمن تطابق "بدون فلتر" مع "الشهر الحالي"
        // ولا يؤثر على الأشهر الماضية (نهايتها قبل اليوم)
        $to = min(Carbon::parse($to)->format('Y-m-d'), $today);

        return [
            'from'     => Carbon::parse($from)->format('Y-m-d'),
            'to'       => $to,
            'start_dt' => Carbon::parse($from)->startOfDay(),
            'end_dt'   => Carbon::parse($to)->endOfDay(),
        ];
    }

    // جلب الموظفين بناءً على الفلاتر (مع إمكانية تصفية حسب الشركة/المحفظة)
    private function queryUsers(array $filters): Builder
    {
        return User::select('id', 'name')
            ->when(!empty($filters['user_id']),   fn($q) => $q->where('id',        $filters['user_id']))
            ->when(!empty($filters['team_id']),   fn($q) => $q->where('team_id',   $filters['team_id']))
            ->when(!empty($filters['branch_id']), fn($q) => $q->where('branch_id', $filters['branch_id']))
            ->when(
                !empty($filters['company_id']) || !empty($filters['portfolio_id']),
                fn($q) => $q->whereHas('managedCustomers', function (Builder $c) use ($filters): void {
                    if (!empty($filters['company_id']))   $c->where('company_id',   $filters['company_id']);
                    if (!empty($filters['portfolio_id'])) $c->where('portfolio_id', $filters['portfolio_id']);
                })
            )
            ->orderBy('name');
    }


    // عدد العملاء + إجمالي المحفظة + إجمالي الديون الأصلية لكل موظف
    private function queryPortfolio(array $userIds, array $filters): array
    {
        return Customer::query()
            ->whereIn('relationship_manager_id', $userIds)
            ->when(!empty($filters['company_id']),   fn($q) => $q->where('company_id',   $filters['company_id']))
            ->when(!empty($filters['portfolio_id']), fn($q) => $q->where('portfolio_id', $filters['portfolio_id']))
            ->leftJoin(
                'customer_transactions',
                fn($j) => $j->on('customer_transactions.customer_id', '=', 'customers.id')->whereNull('customer_transactions.deleted_at')
            )
            ->groupBy('customers.relationship_manager_id')
            ->selectRaw("
                customers.relationship_manager_id                                                  AS user_id,
                COUNT(DISTINCT customers.id)                                                        AS customers_count,
                COALESCE(SUM(CASE WHEN customer_transactions.type = 'debit'
                                  THEN customer_transactions.amount ELSE 0 END), 0)                AS gross_debt,
                COALESCE(SUM(CASE WHEN customer_transactions.type = 'debit'
                                  THEN  customer_transactions.amount
                                  WHEN customer_transactions.type = 'credit'
                                  THEN -customer_transactions.amount
                                  ELSE 0 END), 0)                                                  AS net_portfolio
            ")
            ->get()
            ->keyBy('user_id')
            ->all();
    }


    // استعلام إجمالي التحصيل + عدد العملاء المسددين + متوسط السداد.
    private function queryCollections(array $userIds, array $filters, string $from, string $to): array
    {
        return Collection::withoutGlobalScope(\App\Traits\Tenants\Scopes\UserBranchScope::class)
            ->where('status', 'approved')
            ->whereBetween('collection_date', [$from, $to])
            ->whereIn('added_by', $userIds)
            ->when(
                !empty($filters['company_id']),
                fn($q) => $q->whereHas('customer', fn($c) => $c->where('company_id',   $filters['company_id']))
            )
            ->when(
                !empty($filters['portfolio_id']),
                fn($q) => $q->whereHas('customer', fn($c) => $c->where('portfolio_id', $filters['portfolio_id']))
            )
            ->groupBy('added_by')
            ->selectRaw("
            added_by                                    AS user_id,
            COALESCE(SUM(collected_amount), 0)          AS total,
            COUNT(DISTINCT customer_id)                 AS paid_customers,
            COALESCE(AVG(collected_amount), 0)          AS avg_payment
        ")
            ->get()
            ->keyBy('user_id')
            ->all();
    }

    /**
     * استعلام ٣: المتابعات + الوعود + غير المتواصل معهم + نسبة تحقيق الوعد.
     */
    private function queryFollows(array $userIds, array $filters, Carbon $startDt, Carbon $endDt): array
    {
        // كل المتابعات في الفترة
        $follows = CustomerFollow::query()->with('promisePaymentDetails:id,contact_result_id,promise_amount')
            ->whereIn('created_by', $userIds)->whereBetween('created_at', [$startDt, $endDt])
            ->when(
                !empty($filters['company_id']) || !empty($filters['portfolio_id']),
                fn($q) => $q->whereHas('customer', function (Builder $c) use ($filters): void {
                    if (!empty($filters['company_id']))   $c->where('company_id',   $filters['company_id']);
                    if (!empty($filters['portfolio_id'])) $c->where('portfolio_id', $filters['portfolio_id']);
                })
            )->get(['id', 'created_by', 'customer_id', 'contact_result_id', 'created_at']);

        // عدد العملاء الكلي لكل موظف (لحساب غير المتواصل معهم)
        $customerCounts = Customer::query()
            ->whereIn('relationship_manager_id', $userIds)
            ->when(!empty($filters['company_id']),   fn($q) => $q->where('company_id',   $filters['company_id']))
            ->when(!empty($filters['portfolio_id']), fn($q) => $q->where('portfolio_id', $filters['portfolio_id']))
            ->groupBy('relationship_manager_id')
            ->selectRaw('relationship_manager_id AS user_id, COUNT(*) AS cnt')
            ->pluck('cnt', 'user_id');

        $contactedCounts = CustomerFollow::query()
            ->join('customers', 'customers.id', '=', 'customer_follows.customer_id')
            ->whereIn('customer_follows.created_by', $userIds)
            ->whereBetween('customer_follows.created_at', [$startDt, $endDt])
            ->whereColumn('customers.relationship_manager_id', 'customer_follows.created_by')
            ->whereNull('customers.deleted_at')
            ->when(!empty($filters['company_id']),   fn($q) => $q->where('customers.company_id',   $filters['company_id']))
            ->when(!empty($filters['portfolio_id']), fn($q) => $q->where('customers.portfolio_id', $filters['portfolio_id']))
            ->groupBy('customer_follows.created_by')
            ->selectRaw('customer_follows.created_by AS user_id, COUNT(DISTINCT customer_follows.customer_id) AS cnt')
            ->pluck('cnt', 'user_id');

        // نسب تحقيق الوعود (bulk)
        $promiseRates = $this->calcPromiseRates($follows);

        $result = [];

        foreach ($follows->groupBy('created_by') as $userId => $userFollows) {
            $customerCnt  = (int) ($customerCounts[$userId] ?? 0);
            $contactedCnt = (int) ($contactedCounts[$userId] ?? 0);
            $notContacted = max(0, $customerCnt - $contactedCnt);

            $promises = $userFollows->where('contact_result_id', 3)->filter(fn($f) => $f->promisePaymentDetails !== null);

            $result[$userId] = [
                'follows_count'    => $userFollows->count(),
                'avg_calls'       => $customerCnt > 0 ? round($userFollows->count() / $customerCnt, 2) : 0.0,
                'not_contacted'   => $notContacted,
                'promises_count'  => $promises->count(),
                'promises_amount' => round($promises->sum(fn($f) => (float) ($f->promisePaymentDetails->promise_amount ?? 0)), 2),
                'promise_rate'    => $promiseRates[$userId] ?? 0.0,
            ];
        }

        // الموظفون الذين ليس لديهم أي متابعة
        foreach ($userIds as $uid) {
            $result[$uid] ??= [
                'follows_count'    => 0,
                'avg_calls'       => 0.0,
                'not_contacted'   => (int) ($customerCounts[$uid] ?? 0),
                'promises_count'  => 0,
                'promises_amount' => 0.0,
                'promise_rate'    => 0.0,
            ];
        }

        return $result;
    }

    private function queryOverdueTasks(array $userIds, array $filters, string $from, string $to): array
    {
        $remainingDebtExpr = '(SELECT
            COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0) -
            COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0)
            FROM customer_transactions
            WHERE customer_id = tasks.customer_id)';

        $counts = Task::query()
            ->leftJoin('customers as c', 'c.id', '=', 'tasks.customer_id')
            ->whereIn('c.relationship_manager_id', $userIds)
            ->where('tasks.status', 'pending')
            ->whereDate('tasks.due_date', '<', now()->toDateString())
            ->whereBetween('tasks.due_date', [$from, $to])
            ->whereRaw("$remainingDebtExpr > 0")
            ->when(!empty($filters['company_id']),   fn($q) => $q->where('c.company_id',   $filters['company_id']))
            ->when(!empty($filters['portfolio_id']), fn($q) => $q->where('c.portfolio_id', $filters['portfolio_id']))
            ->groupBy('c.relationship_manager_id')
            ->select('c.relationship_manager_id as user_id', DB::raw('COUNT(DISTINCT tasks.id) as overdue_tasks'))
            ->pluck('overdue_tasks', 'user_id')
            ->map(fn($count) => (int) $count)
            ->all();

        return array_replace(array_fill_keys($userIds, 0), $counts);
    }

    //  أسماء الشركات لكل موظف
    private function queryCompanyNames(array $userIds, array $filters): array
    {
        return Customer::query()
            ->whereIn('relationship_manager_id', $userIds)
            ->when(!empty($filters['company_id']),   fn($q) => $q->where('company_id',   $filters['company_id']))
            ->when(!empty($filters['portfolio_id']), fn($q) => $q->where('portfolio_id', $filters['portfolio_id']))
            ->join('companies', 'companies.id', '=', 'customers.company_id')
            ->selectRaw('customers.relationship_manager_id AS user_id, companies.name')
            ->distinct()
            ->get()
            ->groupBy('user_id')
            ->map(fn($g) => $g->pluck('name')->implode('، '))
            ->all();
    }

    //   الأهداف المرجّحة لكل موظف
    private function queryTargets(array $userIds, array $filters, string $from, string $to): array
    {
        $periodYear  = Carbon::parse($from)->year;
        $periodMonth = Carbon::parse($from)->month;

        $targets = CollectionTarget::query()
            ->whereIn('user_id', $userIds)
            ->where('target_year',  $periodYear)
            ->where('target_month', $periodMonth)
            ->where('target_type',  'monthly')
            ->where('status',       'active')
            ->get(['user_id', 'target_amount'])
            ->keyBy('user_id')
            ->map(fn($t) => (float) $t->target_amount)
            ->all();

        // ✅ الإصلاح: استخدم + بدل array_merge لحفظ مفاتيح user_id
        // + يحافظ على المفاتيح، و$targets يأخذ الأولوية (اليسار يكسب)
        return $targets + array_fill_keys($userIds, 0.0);
    }
    /**
     * حساب نسب تحقيق الوعد — bulk بدون loop على الاستعلامات.
     */
    private function calcPromiseRates(IlluminateCollection $follows): array
    {
        $promises = $follows->where('contact_result_id', 3)->filter(fn($f) => $f->promisePaymentDetails !== null);

        if ($promises->isEmpty()) {
            return [];
        }

        // الوعود المنجزة: متابعة لاحقة بتحصيل معتمد
        $fulfilled = CustomerFollow::query()->whereIn('customer_id', $promises->pluck('customer_id')->unique())->whereIn('contact_result_id', [1, 2])
            ->whereHas('collection', fn($q) => $q->where('status', 'approved'))
            ->get(['customer_id', 'created_at'])
            ->groupBy('customer_id');

        $rates = [];

        foreach ($promises->groupBy('created_by') as $userId => $userPromises) {
            $fulfilledCount = $userPromises->filter(
                fn($p) => ($fulfilled->get($p->customer_id) ?? collect())
                    ->where('created_at', '>', $p->created_at)
                    ->isNotEmpty()
            )->count();

            $rates[$userId] = $userPromises->count() > 0
                ? round(($fulfilledCount / $userPromises->count()) * 100, 2)
                : 0.0;
        }

        return $rates;
    }


    private function weightedTargetPct(IlluminateCollection $targets, string $from, string $to): float
    {
        if ($targets->isEmpty()) {
            return 0.0;
        }

        if ($targets->count() === 1) {
            return (float) $targets->first()->target_percentage;
        }

        $start     = Carbon::parse($from);
        $end       = Carbon::parse($to);
        $totalDays = $start->diffInDays($end) + 1;
        $weighted  = 0.0;

        foreach ($targets as $target) {
            $tStart   = Carbon::parse($target->effective_from)->max($start);
            $tEnd     = $target->effective_to ? Carbon::parse($target->effective_to)->min($end) : $end;
            $days     = max(0, $tStart->diffInDays($tEnd) + 1);
            $weighted += $days * (float) $target->target_percentage;
        }

        return $totalDays > 0 ? round($weighted / $totalDays, 4) : 0.0;
    }
}
