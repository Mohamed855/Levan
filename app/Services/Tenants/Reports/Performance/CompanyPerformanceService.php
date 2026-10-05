<?php

namespace App\Services\Tenants\Reports\Performance;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\GeneralSetting\ContactResult\ContactResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Exports\Tenants\Reports\Performance\PerformanceReportExport;
use App\Jobs\Tenants\Reports\Performance\ExportPerformanceReportJob;
use Carbon\Carbon;

class CompanyPerformanceService extends BasePerformanceService
{
    /*
    |--------------------------------------------------------------------------
    | ⚡ OPTIMIZED - Get Overall Statistics (للكاردات فقط)
    |--------------------------------------------------------------------------
    */
    public function getOverallStatistics(array $filters = []): array
    {
        $startDate = $filters['date_from'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $filters['date_to'] ?? Carbon::now()->format('Y-m-d');

        // ✅ استعلام واحد مُحسّن للحصول على جميع الإحصائيات
        $stats = DB::table('customers')
            ->when(!empty($filters['company_id']), fn($q) => $q->where('company_id', $filters['company_id']))
            ->when(!empty($filters['portfolio_id']), fn($q) => $q->where('portfolio_id', $filters['portfolio_id']))
            ->when(!empty($filters['user_id']), fn($q) => $q->where('relationship_manager_id', $filters['user_id']))
            ->when(!empty($filters['team_id']), function ($q) use ($filters) {
                $q->whereHas('relationshipManager', fn($sub) => $sub->where('team_id', $filters['team_id']));
            })
            ->selectRaw('
                COUNT(DISTINCT company_id) as companies_count,
                SUM((SELECT COALESCE(SUM(CASE WHEN type="debit" THEN amount WHEN type="credit" THEN -amount ELSE 0 END), 0)
                     FROM customer_transactions
                     WHERE customer_transactions.customer_id = customers.id)) as total_portfolio
            ')
            ->first();

        // ✅ استعلام منفصل للتحصيلات
        $collectionsQuery = DB::table('collections')
            ->where('status', 'approved')
            ->whereBetween('collection_date', [$startDate, $endDate]);

        if (!empty($filters['company_id'])) {
            $collectionsQuery->whereHas('customer', fn($q) => $q->where('company_id', $filters['company_id']));
        }
        if (!empty($filters['portfolio_id'])) {
            $collectionsQuery->whereHas('customer', fn($q) => $q->where('portfolio_id', $filters['portfolio_id']));
        }
        if (!empty($filters['user_id'])) {
            $collectionsQuery->where('added_by', $filters['user_id']);
        }
        if (!empty($filters['team_id'])) {
            $collectionsQuery->whereHas('addedBy', fn($q) => $q->where('team_id', $filters['team_id']));
        }

        $collectionsStats = $collectionsQuery->selectRaw('
            SUM(collected_amount) as total_collections,
            COUNT(DISTINCT customer_id) as paid_customers_count
        ')->first();

        $totalPortfolio = (float) ($stats->total_portfolio ?? 0);
        $totalCollections = (float) ($collectionsStats->total_collections ?? 0);
        $paidCustomersCount = (int) ($collectionsStats->paid_customers_count ?? 0);
        $companiesCount = (int) ($stats->companies_count ?? 0);

        // ✅ حساب المستهدف بشكل مُحسّن
        $monthlyTarget = $this->calculateOverallMonthlyTargetOptimized($filters, $startDate, $endDate);

        // ✅ حساب المتبقي الفعلي (المديونية - التحصيل)
        $totalRemaining = $totalPortfolio - $totalCollections;

        $collectionPercentage = $totalPortfolio > 0
            ? ($totalCollections / $totalPortfolio) * 100
            : 0;

        return [
            'total_portfolio' => $totalPortfolio,
            'total_collections' => $totalCollections,
            'total_remaining' => $totalRemaining, // ✅ المتبقي الفعلي
            'paid_customers_count' => $paidCustomersCount,
            'companies_count' => $companiesCount,
            'monthly_target' => $monthlyTarget,
            'collection_percentage' => $collectionPercentage,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ⚡ OPTIMIZED - Calculate Overall Monthly Target
    |--------------------------------------------------------------------------
    */
    private function calculateOverallMonthlyTargetOptimized(array $filters, string $startDate, string $endDate): float
    {
        // ✅ استعلام واحد للحصول على جميع الأهداف
        $targets = DB::table('company_targets')
            ->select('company_id', 'target_percentage', 'effective_from', 'effective_to')
            ->where('effective_from', '<=', $endDate)
            ->where(function ($query) use ($startDate) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $startDate);
            })
            ->get()
            ->groupBy('company_id');

        // ✅ استعلام واحد للحصول على محافظ الشركات
        $portfoliosQuery = DB::table('customers')
            ->select('company_id', DB::raw('
                SUM((SELECT COALESCE(SUM(CASE WHEN type="debit" THEN amount WHEN type="credit" THEN -amount ELSE 0 END), 0)
                     FROM customer_transactions
                     WHERE customer_transactions.customer_id = customers.id)) as portfolio
            '))
            ->groupBy('company_id');

        if (!empty($filters['user_id'])) {
            $portfoliosQuery->where('relationship_manager_id', $filters['user_id']);
        }
        if (!empty($filters['team_id'])) {
            $portfoliosQuery->whereHas('relationshipManager', fn($q) => $q->where('team_id', $filters['team_id']));
        }
        if (!empty($filters['company_id'])) {
            $portfoliosQuery->where('company_id', $filters['company_id']);
        }

        $portfolios = $portfoliosQuery->get()->keyBy('company_id');

        $totalTarget = 0;
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $totalDays = $start->diffInDays($end) + 1;

        foreach ($portfolios as $companyId => $portfolio) {
            $companyPortfolio = (float) $portfolio->portfolio;

            if ($companyPortfolio <= 0) {
                continue;
            }

            $companyTargets = $targets->get($companyId);

            if (!$companyTargets || $companyTargets->isEmpty()) {
                continue;
            }

            // حساب النسبة المرجحة
            if ($companyTargets->count() === 1) {
                $targetPercentage = (float) $companyTargets->first()->target_percentage;
            } else {
                $weightedSum = 0;
                foreach ($companyTargets as $target) {
                    $effectiveStart = Carbon::parse($target->effective_from)->max($start);
                    $effectiveEnd = $target->effective_to ? Carbon::parse($target->effective_to)->min($end) : $end;
                    $daysInEffect = $effectiveStart->diffInDays($effectiveEnd) + 1;

                    if ($daysInEffect > 0) {
                        $weightedSum += ($daysInEffect * $target->target_percentage);
                    }
                }
                $targetPercentage = $weightedSum / $totalDays;
            }

            $totalTarget += ($companyPortfolio * ($targetPercentage / 100));
        }

        return $totalTarget;
    }

    /*
    |--------------------------------------------------------------------------
    | ⚡ OPTIMIZED - Get Companies Performance (للجدول فقط)
    |--------------------------------------------------------------------------
    */
    public function getCompaniesPerformance(array $filters = []): array
    {
        $startDate = $filters['date_from'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $filters['date_to'] ?? Carbon::now()->format('Y-m-d');

        // ✅ استعلام واحد مُحسّن للغاية
        $companiesData = DB::table('companies')
            ->leftJoin('customers', 'companies.id', '=', 'customers.company_id')
            ->when(!empty($filters['company_id']), fn($q) => $q->where('companies.id', $filters['company_id']))
            ->when(!empty($filters['portfolio_id']), fn($q) => $q->where('customers.portfolio_id', $filters['portfolio_id']))
            ->when(!empty($filters['user_id']), fn($q) => $q->where('customers.relationship_manager_id', $filters['user_id']))
            ->when(!empty($filters['team_id']), function ($q) use ($filters) {
                $q->whereExists(function ($sub) use ($filters) {
                    $sub->select(DB::raw(1))
                        ->from('users')
                        ->whereColumn('users.id', 'customers.relationship_manager_id')
                        ->where('users.team_id', $filters['team_id']);
                });
            })
            ->select(
                'companies.id',
                'companies.name',
                DB::raw('COUNT(DISTINCT customers.id) as customers_count'),
                DB::raw('SUM((SELECT COALESCE(SUM(CASE WHEN type="debit" THEN amount WHEN type="credit" THEN -amount ELSE 0 END), 0)
                         FROM customer_transactions
                         WHERE customer_transactions.customer_id = customers.id)) as total_portfolio')
            )
            ->groupBy('companies.id', 'companies.name')
            ->having('customers_count', '>', 0)
            ->get();

        // ✅ استعلام واحد للحصول على جميع التحصيلات
        $collectionsData = DB::table('collections')
            ->join('customers', 'collections.customer_id', '=', 'customers.id')
            ->where('collections.status', 'approved')
            ->whereBetween('collections.collection_date', [$startDate, $endDate])
            ->when(!empty($filters['company_id']), fn($q) => $q->where('customers.company_id', $filters['company_id']))
            ->when(!empty($filters['portfolio_id']), fn($q) => $q->where('customers.portfolio_id', $filters['portfolio_id']))
            ->when(!empty($filters['user_id']), fn($q) => $q->where('collections.added_by', $filters['user_id']))
            ->when(!empty($filters['team_id']), function ($q) use ($filters) {
                $q->whereExists(function ($sub) use ($filters) {
                    $sub->select(DB::raw(1))
                        ->from('users')
                        ->whereColumn('users.id', 'collections.added_by')
                        ->where('users.team_id', $filters['team_id']);
                });
            })
            ->select(
                'customers.company_id',
                DB::raw('SUM(collections.collected_amount) as total_payment'),
                DB::raw('SUM(CASE WHEN collections.payment_type = "settlement" THEN collections.collected_amount ELSE 0 END) as total_settlements')
            )
            ->groupBy('customers.company_id')
            ->get()
            ->keyBy('company_id');

        // ✅ استعلام واحد للأهداف
        $targetsData = DB::table('company_targets')
            ->select('company_id', 'target_percentage', 'effective_from', 'effective_to')
            ->where('effective_from', '<=', $endDate)
            ->where(function ($query) use ($startDate) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $startDate);
            })
            ->get()
            ->groupBy('company_id');

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $totalDays = $start->diffInDays($end) + 1;

        $result = [];

        foreach ($companiesData as $company) {
            $totalPortfolio = (float) $company->total_portfolio;
            $customersCount = (int) $company->customers_count;

            // حساب النسبة المستهدفة
            $targetPercentage = 0;
            $companyTargets = $targetsData->get($company->id);

            if ($companyTargets && $companyTargets->isNotEmpty()) {
                if ($companyTargets->count() === 1) {
                    $targetPercentage = (float) $companyTargets->first()->target_percentage;
                } else {
                    $weightedSum = 0;
                    foreach ($companyTargets as $target) {
                        $effectiveStart = Carbon::parse($target->effective_from)->max($start);
                        $effectiveEnd = $target->effective_to ? Carbon::parse($target->effective_to)->min($end) : $end;
                        $daysInEffect = $effectiveStart->diffInDays($effectiveEnd) + 1;

                        if ($daysInEffect > 0) {
                            $weightedSum += ($daysInEffect * $target->target_percentage);
                        }
                    }
                    $targetPercentage = $weightedSum / $totalDays;
                }
            }

            $companyMonthlyTarget = $totalPortfolio * ($targetPercentage / 100);

            $collectionData = $collectionsData->get($company->id);
            $totalPayment = $collectionData ? (float) $collectionData->total_payment : 0;
            $totalSettlements = $collectionData ? (float) $collectionData->total_settlements : 0;

            $achievementRate = $companyMonthlyTarget > 0
                ? ($totalPayment / $companyMonthlyTarget) * 100
                : 0;

            $result[] = [
                'company_name' => $company->name,
                'customers_count' => $customersCount,
                'total_portfolio' => $totalPortfolio,
                'customer_target' => $companyMonthlyTarget,
                'total_payment' => $totalPayment,
                'total_settlements' => $totalSettlements,
                // ✅ حذف total_collected لأنه تكرار مع total_payment
                'achievement_rate' => $achievementRate,
            ];
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Payment Comparison Data
    |--------------------------------------------------------------------------
    */
    public function getPaymentComparisonData(array $filters = []): array
    {
        $startDate = $filters['date_from'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $filters['date_to'] ?? Carbon::now()->format('Y-m-d');

        $totalPortfolio = $this->getTotalPortfolio($filters);
        $monthlyTarget = $this->calculateOverallMonthlyTargetOptimized($filters, $startDate, $endDate);
        $totalPayment = $this->getTotalCollections($startDate, $endDate, $filters);

        $achievedPercentage = $monthlyTarget > 0
            ? ($totalPayment / $monthlyTarget) * 100
            : 0;

        $remainingPercentage = 100 - $achievedPercentage;

        return [
            'monthly_target' => $monthlyTarget,
            'total_payment' => $totalPayment,
            'total_remaining' => $monthlyTarget - $totalPayment,
            'achieved_percentage' => $achievedPercentage,
            'remaining_percentage' => $remainingPercentage,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Current Month Performance
    |--------------------------------------------------------------------------
    */
    public function getCurrentMonthPerformance(array $filters = []): array
    {
        $currentMonth = Carbon::now()->format('Y-m');
        $previousMonth = Carbon::now()->subMonth()->format('Y-m');
        $lastYear = Carbon::now()->subYear()->format('Y-m');

        $currentMonthPayment = $this->getTotalCollections(
            Carbon::parse($currentMonth)->startOfMonth()->format('Y-m-d'),
            Carbon::parse($currentMonth)->endOfMonth()->format('Y-m-d'),
            $filters
        );

        $previousMonthPayment = $this->getTotalCollections(
            Carbon::parse($previousMonth)->startOfMonth()->format('Y-m-d'),
            Carbon::parse($previousMonth)->endOfMonth()->format('Y-m-d'),
            $filters
        );

        $lastYearPayment = $this->getTotalCollections(
            Carbon::parse($lastYear)->startOfMonth()->format('Y-m-d'),
            Carbon::parse($lastYear)->endOfMonth()->format('Y-m-d'),
            $filters
        );

        $previousMonthChange = $previousMonthPayment > 0
            ? (($currentMonthPayment - $previousMonthPayment) / $previousMonthPayment) * 100
            : 0;

        $lastYearChange = $lastYearPayment > 0
            ? (($currentMonthPayment - $lastYearPayment) / $lastYearPayment) * 100
            : 0;

        return [
            'current_month_payment' => $currentMonthPayment,
            'previous_month_change' => $previousMonthChange,
            'last_year_change' => $lastYearChange,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Contact Communication Statistics
    |--------------------------------------------------------------------------
    */
    public function getContactCommunicationStats(array $filters = []): array
    {
        $allContactResults = ContactResult::active()->get()->keyBy('id');

        $query = CustomerFollow::query();

        if (!empty($filters['company_id'])) {
            $query->whereHas('customer', fn($q) => $q->where('company_id', $filters['company_id']));
        }

        if (!empty($filters['portfolio_id'])) {
            $query->whereHas('customer', fn($q) => $q->where('portfolio_id', $filters['portfolio_id']));
        }

        if (!empty($filters['user_id'])) {
            $query->where('created_by', $filters['user_id']);
        }

        if (!empty($filters['team_id'])) {
            $query->whereHas('createdBy', fn($q) => $q->where('team_id', $filters['team_id']));
        }

        $latestFollows = $query->select('customer_id', DB::raw('MAX(id) as latest_follow_id'))
            ->groupBy('customer_id')
            ->pluck('latest_follow_id');

        $followStats = CustomerFollow::whereIn('id', $latestFollows)
            ->select('contact_result_id', DB::raw('COUNT(*) as count'))
            ->groupBy('contact_result_id')
            ->pluck('count', 'contact_result_id');

        $categoryCounts = [
            'positive' => 0,
            'negative' => 0,
            'other' => 0
        ];

        $breakdownData = [];

        foreach ($followStats as $resultId => $count) {
            $result = $allContactResults->get($resultId);

            if ($result) {
                if (isset($categoryCounts[$result->category])) {
                    $categoryCounts[$result->category] += $count;
                }

                $breakdownData[] = [
                    'name' => $result->name,
                    'category' => $result->category,
                    'count' => $count,
                    'id' => $resultId
                ];
            }
        }

        $totalFollows = $followStats->sum();

        $customersQuery = Customer::query();
        $this->applyFilters($customersQuery, $filters);
        $totalCustomers = $customersQuery->count();
        $contactedCustomers = $latestFollows->count();
        $notContactedCount = $totalCustomers - $contactedCustomers;

        return [
            'positive_percentage' => $totalFollows > 0 ? ($categoryCounts['positive'] / $totalFollows) * 100 : 0,
            'negative_percentage' => $totalFollows > 0 ? ($categoryCounts['negative'] / $totalFollows) * 100 : 0,
            'other_percentage' => $totalFollows > 0 ? ($categoryCounts['other'] / $totalFollows) * 100 : 0,
            'not_contacted_percentage' => $totalCustomers > 0 ? ($notContactedCount / $totalCustomers) * 100 : 0,
            'positive_count' => $categoryCounts['positive'],
            'negative_count' => $categoryCounts['negative'],
            'other_count' => $categoryCounts['other'],
            'not_contacted_count' => $notContactedCount,
            'breakdown' => $breakdownData,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Companies Count
    |--------------------------------------------------------------------------
    */
    private function getCompaniesCount(array $filters = []): int
    {
        $query = Customer::query();
        $this->applyFilters($query, $filters);
        return $query->distinct('company_id')->count('company_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Get Available Export Fields
    |--------------------------------------------------------------------------
    */
    public function getAvailableExportFields(): array
    {
        return [
            'fields' => [
                ['key' => 'company_name', 'label' => 'الشركة', 'selected' => true],
                ['key' => 'customers_count', 'label' => 'عدد العملاء', 'selected' => true],
                ['key' => 'total_portfolio', 'label' => 'إجمالي المديونية', 'selected' => true],
                ['key' => 'customer_target', 'label' => 'مستهدف العميل', 'selected' => false],
                ['key' => 'total_payment', 'label' => 'إجمالي السداد', 'selected' => true],
                ['key' => 'total_settlements', 'label' => 'إجمالي التسويات', 'selected' => false],
                // ✅ حذف total_collected
                ['key' => 'achievement_rate', 'label' => 'نسبة الإنجاز', 'selected' => true],
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Records Count
    |--------------------------------------------------------------------------
    */
    public function getRecordsCount(array $filters = []): int
    {
        return count($this->getCompaniesPerformance($filters));
    }

    /*
    |--------------------------------------------------------------------------
    | Export Filtered Performance To Excel
    |--------------------------------------------------------------------------
    */
    public function exportFilteredPerformanceToExcel(array $filters, array $selectedFields = []): string
    {
        $data = $this->getCompaniesPerformance($filters);

        $fileName = 'تقرير_أداء_الشركات_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        Excel::store(
            new PerformanceReportExport($data, $filters, $selectedFields, 'companies'),
            'exports/' . $fileName,
            'tenant_public'
        );

        return $fileName;
    }

    /*
    |--------------------------------------------------------------------------
    | Export Performance Report To PDF
    |--------------------------------------------------------------------------
    */
    public function exportPerformanceReportToPDF(array $filters): BinaryFileResponse
    {
        try {
            $startDate = $filters['date_from'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = $filters['date_to'] ?? Carbon::now()->format('Y-m-d');

            $data = $this->getCompaniesPerformance($filters);
            $statistics = $this->getOverallStatistics($filters);
            $dateRange = $this->getDateRangeText($startDate, $endDate);

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4', // ✅ Portrait (عمودي)
                'orientation' => 'P', // ✅ P = Portrait
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
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
                'default_font_size' => 10,
                'tempDir' => storage_path('app/public/temp'),
            ]);

            $mpdf->SetDirectionality('rtl');

            $html = view('tenants.reports.performance.pdf', compact(
                'data',
                'statistics',
                'dateRange'
            ))->render();

            $mpdf->WriteHTML($html);

            $fileName = 'تقرير_أداء_الشركات_' . now()->format('Y_m_d_H_i_s') . '.pdf';

            $tempDir = storage_path('app/public/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempPath = $tempDir . '/' . $fileName;
            $mpdf->Output($tempPath, 'F');

            return response()->download($tempPath)->deleteFileAfterSend();
        } catch (\Throwable $e) {
            Log::error('exportPerformanceReportToPDF error', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Start Async Export
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

        $job = new ExportPerformanceReportJob($filters, $selectedFields, $userId, 'companies');
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
    | Get Export Progress
    |--------------------------------------------------------------------------
    */
    public function getExportProgress(string $exportId): array
    {
        $progress = Cache::get("performance_export_progress_{$exportId}");
        $result = Cache::get("performance_export_result_{$exportId}");

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
}
