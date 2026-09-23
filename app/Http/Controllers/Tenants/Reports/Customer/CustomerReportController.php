<?php

namespace App\Http\Controllers\Tenants\Reports\Customer;

use App\Http\Controllers\Controller;
use App\DataTables\Tenants\Reports\Customer\CustomerReportsDataTable;
use App\Services\Tenants\Reports\Customer\CustomerReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerReportController extends Controller
{
    protected CustomerReportService $customerReportService;

    public function __construct(CustomerReportService $customerReportService)
    {
        $this->customerReportService = $customerReportService;
        $this->middleware('can:تقارير العملاء');
    }

    /*
    |--------------------------------------------------------------------------
    | Display Customer Reports With DataTable
    |--------------------------------------------------------------------------
    */
    public function index(CustomerReportsDataTable $dataTable)
    {
        try {
            $reportData = $this->customerReportService->getReportStatistics();
            $formData = $this->customerReportService->getFormData();

            return $dataTable->render('tenants.reports.customers.index', array_merge($reportData, $formData));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Export Customer Report To Excel
    |--------------------------------------------------------------------------
    */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'customer_status_id',
            'customer_type',
            'company_id',
            'portfolio_id',
            'nationality',
            'relationship_manager_id',
            'contact_result_id',
            'debt_from',
            'debt_to'
        ]);

        return $this->customerReportService->exportCustomersReport($filters);
    }

    /*
    |--------------------------------------------------------------------------
    | Export Customer Report To PDF
    |--------------------------------------------------------------------------
    */
    public function exportPDF(Request $request): BinaryFileResponse
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'customer_status_id',
            'customer_type',
            'company_id',
            'portfolio_id',
            'nationality',
            'relationship_manager_id',
            'contact_result_id',
            'debt_from',
            'debt_to'
        ]);

        return $this->customerReportService->exportCustomersReportToPDF($filters);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Export Fields Modal
    |--------------------------------------------------------------------------
    */
    public function showExportFieldsModal(Request $request): JsonResponse
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'customer_status_id',
            'customer_type',
            'company_id',
            'portfolio_id',
            'nationality',
            'relationship_manager_id',
            'contact_result_id',
            'debt_from',
            'debt_to',
            'has_debt'
        ]);

        $count = $this->customerReportService->getCustomersCount($filters);
        $fields = $this->customerReportService->getAvailableExportFields();

        return response()->json([
            'success' => true,
            'count' => $count,
            'fields' => $fields,
            'is_large_export' => $count > 5000
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Export Customer Report To Excel - Enhanced
    |--------------------------------------------------------------------------
    */
    public function exportExcel(Request $request): JsonResponse
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'customer_status_id',
            'customer_type',
            'company_id',
            'portfolio_id',
            'nationality',
            'relationship_manager_id',
            'contact_result_id',
            'debt_from',
            'debt_to',
            'has_debt'
        ]);

        $selectedFields = $request->input('selected_fields', []);
        $count = $this->customerReportService->getCustomersCount($filters);

        try {
            if ($count > 5000) {
                $result = $this->customerReportService->startAsyncExport(
                    $filters,
                    $selectedFields,
                    auth()->id()
                );

                return response()->json([
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'export_id' => $result['export_id'] ?? null,
                    'is_async' => true,
                    'count' => $result['count']
                ]);
            } else {
                $fileName = $this->customerReportService->exportFilteredCustomersToExcel($filters, $selectedFields);

                return response()->json([
                    'success' => true,
                    'download_url' => tenant_route_name('reports.customers.download', ['file' => $fileName]),
                    'message' => 'تم إنشاء الملف بنجاح',
                    'is_async' => false
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الملف: ' . $e->getMessage()
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

        if (!$exportId) {
            return response()->json([
                'success' => false,
                'message' => 'معرف التصدير مطلوب'
            ], 400);
        }

        $progress = $this->customerReportService->getExportProgress($exportId);

        return response()->json([
            'success' => true,
            'data' => $progress
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Check Records Count
    |--------------------------------------------------------------------------
    */
    public function checkRecordsCount(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'date_from',
                'date_to',
                'customer_status_id',
                'customer_type',
                'company_id',
                'portfolio_id',
                'nationality',
                'relationship_manager_id',
                'contact_result_id',
                'debt_from',
                'debt_to'
            ]);

            $count = $this->customerReportService->getCustomersCount($filters);

            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => "تم العثور على {$count} عميل حسب الفلاتر المحددة",
                'is_large_export' => $count > 5000
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب عدد السجلات'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Download File
    |--------------------------------------------------------------------------
    */
    public function downloadFile($file)
    {
        $disk = Storage::disk('tenant_public');
        $relative = 'exports/' . $file;

        if (!$disk->exists($relative)) {
            return redirect()->back()->with('error', 'الملف غير موجود أو تم حذفه.');
        }

        $absolute = $disk->path($relative);

        return response()->download($absolute)->deleteFileAfterSend(true);
    }
}
