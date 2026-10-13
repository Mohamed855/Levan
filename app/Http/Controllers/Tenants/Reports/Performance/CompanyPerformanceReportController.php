<?php

namespace App\Http\Controllers\Tenants\Reports\Performance;

use App\DataTables\Tenants\Reports\Performance\CompanyPerformanceDataTable;
use App\Http\Controllers\Controller;
use App\Services\Tenants\Reports\Performance\CompanyPerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CompanyPerformanceReportController extends Controller
{
    protected CompanyPerformanceService $companyPerformanceService;

    public function __construct(CompanyPerformanceService $companyPerformanceService)
    {
        $this->companyPerformanceService = $companyPerformanceService;
    }

    /*
    |--------------------------------------------------------------------------
    | ⚡ OPTIMIZED - عرض تقارير الأداء (فقط الكاردات + الرسوم)
    |--------------------------------------------------------------------------
    */
    public function index(CompanyPerformanceDataTable $dataTable)
    {
        $filters = request()->only([
            'date_from',
            'date_to',
            'company_id',
            'portfolio_id',
            'team_id',
            'user_id',
        ]);

        // ✅ فقط الإحصائيات للكاردات (سريع جداً)
        $statistics = $this->companyPerformanceService->getOverallStatistics($filters);
        $formData = $this->companyPerformanceService->getFormData();
        $paymentComparison = $this->companyPerformanceService->getPaymentComparisonData($filters);
        $currentMonthPerformance = $this->companyPerformanceService->getCurrentMonthPerformance($filters);
        $contactStats = $this->companyPerformanceService->getContactCommunicationStats($filters);

        // ✅ الجدول سيحمل بياناته عبر AJAX
        return $dataTable->render('tenants.reports.performance.index', array_merge(
            $statistics,
            $formData,
            [
                'payment_comparison' => $paymentComparison,
                'current_month_performance' => $currentMonthPerformance,
                'contact_stats' => $contactStats,
            ]
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | ⚡ OPTIMIZED - جلب بيانات الجدول فقط (AJAX)
    |--------------------------------------------------------------------------
    */
    public function getData(CompanyPerformanceDataTable $dataTable)
    {
        // ✅ DataTables سيستدعي الخدمة مباشرة
        return $dataTable->ajax();
    }

    /*
    |--------------------------------------------------------------------------
    | تصدير تقرير الأداء إلى PDF
    |--------------------------------------------------------------------------
    */
    public function exportPDF(Request $request): BinaryFileResponse
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'company_id',
            'portfolio_id',
            'team_id',
            'user_id',
        ]);

        return $this->companyPerformanceService->exportPerformanceReportToPDF($filters);
    }

    /*
    |--------------------------------------------------------------------------
    | عرض نافذة حقول التصدير
    |--------------------------------------------------------------------------
    */
    public function showExportFieldsModal(Request $request): JsonResponse
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'company_id',
            'portfolio_id',
            'team_id',
            'user_id',
        ]);

        $count = $this->companyPerformanceService->getRecordsCount($filters);
        $fields = $this->companyPerformanceService->getAvailableExportFields();

        return response()->json([
            'success' => true,
            'count' => $count,
            'fields' => $fields,
            'is_large_export' => $count > 5000,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | تصدير تقرير الأداء إلى Excel مع الحقول المخصصة
    |--------------------------------------------------------------------------
    */
    public function exportExcel(Request $request): JsonResponse
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'company_id',
            'portfolio_id',
            'team_id',
            'user_id',
        ]);

        $selectedFields = $request->input('selected_fields', []);
        $count = $this->companyPerformanceService->getRecordsCount($filters);

        try {
            if ($count > 5000) {
                $result = $this->companyPerformanceService->startAsyncExport(
                    $filters,
                    $selectedFields,
                    auth()->id()
                );

                return response()->json([
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'export_id' => $result['export_id'] ?? null,
                    'is_async' => true,
                    'count' => $result['count'],
                ]);
            } else {
                $fileName = $this->companyPerformanceService->exportFilteredPerformanceToExcel(
                    $filters,
                    $selectedFields
                );

                return response()->json([
                    'success' => true,
                    'download_url' => tenant_route_name('reports.performance.download', ['file' => $fileName]),
                    'message' => 'تم إنشاء الملف بنجاح',
                    'is_async' => false,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الملف: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Check Export Progress
    |--------------------------------------------------------------------------
    */
    public function checkExportProgress(Request $request): JsonResponse
    {
        $exportId = $request->input('export_id');

        if (! $exportId) {
            return response()->json([
                'success' => false,
                'message' => 'معرف التصدير مطلوب',
            ], 400);
        }

        $progress = $this->companyPerformanceService->getExportProgress($exportId);

        return response()->json([
            'success' => true,
            'data' => $progress,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | جلب عدد السجلات حسب الفلاتر
    |--------------------------------------------------------------------------
    */
    public function checkRecordsCount(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'date_from',
                'date_to',
                'company_id',
                'portfolio_id',
                'team_id',
                'user_id',
            ]);

            $count = $this->companyPerformanceService->getRecordsCount($filters);

            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => "تم العثور على {$count} سجل حسب الفلاتر المحددة",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب عدد السجلات',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تحميل الملف
    |--------------------------------------------------------------------------
    */
    public function downloadFile($file)
    {
        $disk = Storage::disk('tenant_public');
        $relativePath = 'exports/'.$file;

        if (! $disk->exists($relativePath)) {
            return redirect()->back()->with('error', 'الملف غير موجود أو تم حذفه.');
        }

        $absolutePath = $disk->path($relativePath);

        return response()
            ->download($absolutePath, $file)
            ->deleteFileAfterSend(true);
    }
}
