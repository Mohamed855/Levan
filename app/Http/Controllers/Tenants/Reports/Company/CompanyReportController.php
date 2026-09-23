<?php

namespace App\Http\Controllers\Tenants\Reports\Company;

use App\DataTables\Tenants\Reports\Company\CompanyReportDataTable;
use App\Http\Controllers\Controller;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Services\Tenants\BusinessCenter\Company\CompanyReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CompanyReportController extends Controller
{
    public function __construct(private readonly CompanyReportService $reportService)
    {
        $this->middleware('can:تقارير الشركات');
    }

    public function index(CompanyReportDataTable $dataTable, Request $request)
    {
        $period = $this->validatedPeriod($request);

        return $dataTable->render('tenants.reports.companies.index', [
            'globalStats'          => $this->reportService->getGlobalStatistics($period),
            'topCompaniesByDebt'   => $this->reportService->getTopCompaniesByDebt(5, $period),
            'customerDistribution' => $this->reportService->getCustomerDistribution(),
            'collectionRateTrend'  => $this->reportService->getCollectionRateTrend($period, 5),
            'companiesList'        => Company::query()->orderBy('name')->get(['id', 'name']), // الشركات
            'periods'              => $this->periodLabels(), // خيارات الفترات الزمنية
            'period'               => $period,
        ]);
    }

    public function companyStats(Company $company, Request $request): JsonResponse
    {
        $period = $this->validatedPeriod($request);

        return response()->json([
            'success' => true,
            'data' => $this->reportService->getCompanyStatistics($company, $period),
            'meta' => [
                'level'        => 'company',
                'period'       => $period,
                'company_id'   => (int) $company->id,
                'company_name' => (string) $company->name,
                'company_logo' => $company->logo_url,
            ],
        ]);
    }

    public function companyPortfolios(Company $company): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->reportService->getPortfolioListForCompany($company),
            'meta' => [
                'level'        => 'company',
                'company_id'   => (int) $company->id,
                'company_name' => (string) $company->name,
            ],
        ]);
    }

    public function portfolioStats(Portfolio $portfolio, Request $request): JsonResponse
    {
        $period = $this->validatedPeriod($request);
        $portfolio->loadMissing('company:id,name');

        return response()->json([
            'success' => true,
            'data' => $this->reportService->getPortfolioStatistics($portfolio, $period),
            'meta' => [
                'level'          => 'portfolio',
                'period'         => $period,
                'portfolio_id'   => (int) $portfolio->id,
                'portfolio_name' => (string) $portfolio->name,
                'company_id'     => (int) $portfolio->company_id,
                'company_name'   => (string) ($portfolio->company?->name ?? ''),
            ],
        ]);
    }

    public function showExportFieldsModal(Request $request): JsonResponse
    {
        $filters = $this->extractExportFilters($request);
        $count = $this->reportService->getCompanyReportRecordsCount($filters);

        return response()->json([
            'success' => true,
            'count' => $count,
            'fields' => $this->reportService->getAvailableExportFields(),
            'is_large_export' => $count > CompanyReportService::LARGE_EXPORT_THRESHOLD,
        ]);
    }

    public function exportExcel(Request $request): JsonResponse
    {
        $filters = $this->extractExportFilters($request);
        $selectedFields = $request->input('selected_fields', []);
        $count = $this->reportService->getCompanyReportRecordsCount($filters);

        try {
            if ($count > CompanyReportService::LARGE_EXPORT_THRESHOLD) {
                $result = $this->reportService->startAsyncExport($filters, $selectedFields, auth()->id());

                return response()->json([...$result, 'is_async' => true]);
            }

            $fileName = $this->reportService->exportCompanyReportToExcel($filters, $selectedFields);

            return response()->json([
                'success' => true,
                'is_async' => false,
                'message' => 'تم إنشاء الملف بنجاح',
                'download_url' => tenant_route_name('reports.companies.download', ['file' => $fileName]),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الملف: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function checkExportProgress(Request $request): JsonResponse
    {
        $exportId = $request->input('export_id');

        if (! $exportId) {
            return response()->json([
                'success' => false,
                'message' => 'معرف التصدير مطلوب',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $this->reportService->getExportProgress($exportId),
        ]);
    }

    public function downloadFile(string $file): BinaryFileResponse
    {
        $disk = Storage::disk('tenant_public');
        $path = 'exports/' . $file;

        abort_unless($disk->exists($path), 404, 'الملف غير موجود');

        return response()->download($disk->path($path), $file)->deleteFileAfterSend();
    }


    /*
    |============================================================================
    |============================================================================
    |                        Private Methods
    |============================================================================
    |============================================================================
    */
    private function validatedPeriod(Request $request): string
    {
        $period = (string) $request->query('period', 'last_6months');

        return CompanyReportService::isValidPeriod($period) ? $period : 'last_6months';
    }

    private function periodLabels(): array
    {
        return [
            'this_month'    => 'هذا الشهر',
            'last_month'    => 'الشهر الماضي',
            'this_quarter'  => 'هذا الربع',
            'last_quarter'  => 'الربع الماضي',
            'last_3months'  => 'آخر 3 أشهر',
            'last_6months'  => 'آخر 6 أشهر',
            'last_12months' => 'آخر 12 شهرا',
            'this_year'     => 'هذا العام',
            'last_year'     => 'العام الماضي',
        ];
    }

    private function extractExportFilters(Request $request): array
    {
        $filters = array_filter(
            $request->only([
                'period',
                'company_id',
                'portfolio_id',
                'status',
                'date_from',
                'date_to',
                'search_query',
            ]),
            fn($value) => $value !== null && $value !== ''
        );

        if (isset($filters['period'])) {
            $filters['period'] = CompanyReportService::isValidPeriod((string) $filters['period'])
                ? (string) $filters['period']
                : 'last_6months';
        }

        return $filters;
    }
}
