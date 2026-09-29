<?php

namespace App\Http\Controllers\Tenants\Reports\CustomerFollow;

use App\Http\Controllers\Controller;
use App\DataTables\Tenants\Reports\CustomerFollow\CustomerFollowReportsDataTable;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Services\Tenants\Reports\CustomerFollow\CustomerFollowReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerFollowReportController extends Controller
{
    protected CustomerFollowReportService $customerFollowReportService;

    public function __construct(CustomerFollowReportService $customerFollowReportService)
    {
        $this->customerFollowReportService = $customerFollowReportService;
        // $this->middleware('can:تقارير متابعات العملاء');
    }

    /*
    |--------------------------------------------------------------------------
    | عرض تقارير متابعات العملاء مع DataTable
    |--------------------------------------------------------------------------
    */
    public function index(CustomerFollowReportsDataTable $dataTable)
    {
        try {
            $reportData = $this->customerFollowReportService->getReportStatistics();
            $formData = $this->customerFollowReportService->getFormData();

            return $dataTable->render('tenants.reports.customer_follows.index', array_merge($reportData, $formData));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تصدير تقرير متابعات العملاء إلى Excel
    |--------------------------------------------------------------------------
    */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'next_contact_from',
            'next_contact_to',
            'customer_id',
            'company_id',
            'portfolio_id',
            'contact_method_id',
            'customer_status_id',
            'contact_result_id',
            'created_by',
            'has_upcoming'
        ]);

        return $this->customerFollowReportService->exportFollowsReport($filters);
    }

    /*
    |--------------------------------------------------------------------------
    | تصدير تقرير متابعات العملاء إلى PDF
    |--------------------------------------------------------------------------
    */
    public function exportPDF(Request $request): BinaryFileResponse
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'next_contact_from',
            'next_contact_to',
            'customer_id',
            'company_id',
            'portfolio_id',
            'contact_method_id',
            'customer_status_id',
            'contact_result_id',
            'created_by',
            'has_upcoming'
        ]);

        return $this->customerFollowReportService->exportFollowsReportToPDF($filters);
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
            'next_contact_from',
            'next_contact_to',
            'customer_id',
            'company_id',
            'portfolio_id',
            'contact_method_id',
            'customer_status_id',
            'contact_result_id',
            'created_by',
            'has_upcoming'
        ]);

        $count = $this->customerFollowReportService->getFollowsCount($filters);
        $fields = $this->customerFollowReportService->getAvailableExportFields();

        return response()->json([
            'success' => true,
            'count' => $count,
            'fields' => $fields,
            'is_large_export' => $count > 5000
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Export Customer Follow Report To Excel - Enhanced
    |--------------------------------------------------------------------------
    */
    public function exportExcel(Request $request): JsonResponse
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'next_contact_from',
            'next_contact_to',
            'customer_id',
            'company_id',
            'portfolio_id',
            'contact_method_id',
            'customer_status_id',
            'contact_result_id',
            'created_by',
            'has_upcoming'
        ]);

        $selectedFields = $request->input('selected_fields', []);
        $count = $this->customerFollowReportService->getFollowsCount($filters);

        try {
            if ($count > 5000) {
                $result = $this->customerFollowReportService->startAsyncExport(
                    $filters,
                    $selectedFields,
                    auth()->id()
                );

                return response()->json([
                    'success'     => $result['success'],
                    'message'     => $result['message'],
                    'export_id'   => $result['export_id'] ?? null,
                    'total_parts' => $result['total_parts'] ?? 1,
                    'is_async'    => true,
                    'count'       => $result['count'],
                ]);
            } else {
                $fileName = $this->customerFollowReportService->exportFilteredFollowsToExcel($filters, $selectedFields);

                return response()->json([
                    'success' => true,
                    'download_url' => tenant_route_name('reports.customer_follows.download', ['file' => $fileName]),
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
    | Check Export Progress - تعديل مهم
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

        $progress = $this->customerFollowReportService->getExportProgress($exportId);

        return response()->json([
            'success' => true,
            'data' => $progress
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
                'next_contact_from',
                'next_contact_to',
                'customer_id',
                'contact_method_id',
                'customer_status_id',
                'contact_result_id',
                'created_by',
                'has_upcoming'
            ]);

            $count = $this->customerFollowReportService->getFollowsCount($filters);

            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => "تم العثور على {$count} متابعة حسب الفلاتر المحددة"
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
    | إلغاء التصدير
    |--------------------------------------------------------------------------
    */
    public function cancelExport(Request $request): JsonResponse
    {
        $exportId = $request->input('export_id');

        if (!$exportId) {
            return response()->json(['success' => false, 'message' => 'معرف التصدير مطلوب'], 400);
        }

        $this->customerFollowReportService->cancelExport($exportId);

        return response()->json(['success' => true, 'message' => 'تم إرسال طلب الإلغاء']);
    }

    /*
    |--------------------------------------------------------------------------
    | تحميل الملف
    |--------------------------------------------------------------------------
    */
    public function downloadFile($file)
    {
        $disk = Storage::disk('tenant_public');
        $relativePath = 'exports/' . $file;

        if (!$disk->exists($relativePath)) {
            return redirect()->back()->with('error', 'الملف غير موجود أو تم حذفه.');
        }

        $absolutePath = $disk->path($relativePath);

        return response()->download($absolutePath);
    }


    /*
    |--------------------------------------------------------------------------
    | Search Customers
    |--------------------------------------------------------------------------
    */
    public function searchCustomers(Request $request): JsonResponse
    {
        $search = $request->get('q', '');
        $customers = $this->customerFollowReportService->searchCustomers($search);

        return response()->json($customers);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Customer
    |--------------------------------------------------------------------------
    */
    public function getCustomer(Customer $customer): JsonResponse
    {
        $customerData = $this->customerFollowReportService->getCustomerById($customer->id);

        if (!$customerData) {
            return response()->json(null, 404);
        }

        return response()->json($customerData);
    }

    /*
    |--------------------------------------------------------------------------
    | إضافة endpoint لجلب المحافظ حسب الشركة
    |--------------------------------------------------------------------------
    */
    public function getPortfoliosByCompany(Request $request): JsonResponse
    {
        $companyId = $request->get('company_id');

        if (!$companyId) {
            return response()->json([]);
        }

        $portfolios = Portfolio::where('company_id', $companyId)
            ->where('status', 'active')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($portfolios);
    }
}
