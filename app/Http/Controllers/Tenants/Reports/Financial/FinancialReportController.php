<?php

namespace App\Http\Controllers\Tenants\Reports\Financial;

use App\DataTables\Tenants\Reports\Financial\FinancialReportsDataTable;
use App\Http\Controllers\Controller;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Services\Tenants\Reports\Financial\FinancialReportService;
use App\Services\Tenants\Reports\TenantExportAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinancialReportController extends Controller
{
    protected FinancialReportService $financialReportService;

    protected TenantExportAccess $exportAccess;

    public function __construct(FinancialReportService $financialReportService, TenantExportAccess $exportAccess)
    {
        $this->financialReportService = $financialReportService;
        $this->exportAccess = $exportAccess;
    }

    /*
    |--------------------------------------------------------------------------
    | عرض تقارير المعاملات المالية مع DataTable
    |--------------------------------------------------------------------------
    */
    public function index(FinancialReportsDataTable $dataTable)
    {
        try {
            $reportData = $this->financialReportService->getReportStatistics();
            $formData = $this->financialReportService->getFormData();

            return $dataTable->render('tenants.reports.financial.index', array_merge($reportData, $formData));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تصدير تقرير المعاملات المالية إلى Excel
    |--------------------------------------------------------------------------
    */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'created_from',
            'created_to',
            'customer_id',
            'status',
            'company_id',
            'portfolio_id',
            'created_by',
            'amount_from',
            'amount_to',
        ]);

        return $this->financialReportService->exportFinancialReport($filters);
    }

    /*
    |--------------------------------------------------------------------------
    | تصدير تقرير المعاملات المالية إلى PDF
    |--------------------------------------------------------------------------
    */
    public function exportPDF(Request $request): BinaryFileResponse
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'created_from',
            'created_to',
            'customer_id',
            'type',
            'created_by',
            'amount_from',
            'amount_to',
            'related_type',
        ]);

        return $this->financialReportService->exportFinancialReportToPDF($filters);
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
            'created_from',
            'created_to',
            'customer_id',
            'status',
            'created_by',
            'amount_from',
            'amount_to',
            'company_id',
            'portfolio_id',
        ]);

        $count = $this->financialReportService->getTransactionsCount($filters);
        $fields = $this->financialReportService->getAvailableExportFields();

        return response()->json([
            'success' => true,
            'count' => $count,
            'fields' => $fields,
            'is_large_export' => $count > 5000,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | تصدير تقرير المعاملات المالية إلى Excel مع الحقول المخصصة
    |--------------------------------------------------------------------------
    */
    public function exportExcel(Request $request): JsonResponse
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'created_from',
            'created_to',
            'customer_id',
            'status',
            'created_by',
            'amount_from',
            'amount_to',
            'company_id',
            'portfolio_id',
        ]);

        $selectedFields = $request->input('selected_fields', []);
        $count = $this->financialReportService->getTransactionsCount($filters);

        try {
            if ($count > 5000) {
                $result = $this->financialReportService->startAsyncExport(
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
                $fileName = $this->financialReportService->exportFilteredFinancialToExcel(
                    $filters,
                    $selectedFields
                );

                return response()->json([
                    'success' => true,
                    'download_url' => tenant_route_name('reports.financial.download', ['file' => $fileName]),
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

        $progress = $this->financialReportService->getExportProgress($exportId);

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
                'created_from',
                'created_to',
                'customer_id',
                'type',
                'created_by',
                'amount_from',
                'amount_to',
                'related_type',
            ]);

            $count = $this->financialReportService->getTransactionsCount($filters);

            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => "تم العثور على {$count} معاملة حسب الفلاتر المحددة",
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
        $this->exportAccess->authorizeFile((string) $file, request()->user('tenant')?->id);

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

    /*
    |--------------------------------------------------------------------------
    | Search Customers
    |--------------------------------------------------------------------------
    */
    public function searchCustomers(Request $request): JsonResponse
    {
        $search = $request->get('q', '');
        $customers = $this->financialReportService->searchCustomers($search);

        return response()->json($customers);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Customer
    |--------------------------------------------------------------------------
    */
    public function getCustomer(Customer $customer): JsonResponse
    {
        $customerData = $this->financialReportService->getCustomerById($customer->id);

        if (! $customerData) {
            return response()->json(null, 404);
        }

        return response()->json($customerData);
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function getPortfoliosByCompany(Request $request): JsonResponse
    {
        $companyId = $request->get('company_id');

        if (! $companyId) {
            return response()->json([]);
        }

        $portfolios = Portfolio::where('company_id', $companyId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(function ($portfolio) {
                return [
                    'id' => $portfolio->id,
                    'name' => $portfolio->name,
                ];
            });

        return response()->json($portfolios);
    }
}
