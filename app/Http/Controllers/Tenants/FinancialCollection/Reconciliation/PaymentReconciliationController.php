<?php

namespace App\Http\Controllers\Tenants\FinancialCollection\Reconciliation;

use App\Http\Controllers\Controller;
use App\Models\Tenants\FinancialCollection\Reconciliation\PaymentReconciliation;
use App\Services\Tenants\FinancialCollection\Reconciliation\PaymentReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentReconciliationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Properties
    |--------------------------------------------------------------------------
    */
    protected PaymentReconciliationService $reconciliationService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(PaymentReconciliationService $reconciliationService)
    {
        $this->reconciliationService = $reconciliationService;
        $this->middleware('auth');
        $this->middleware('can:مطابقة السدادات');
    }

    /*
    |--------------------------------------------------------------------------
    | Show Reconciliation Page
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        try {
            $viewData = $this->reconciliationService->getInitialViewData();

            return view('tenants.financial_collection.reconciliation.index', $viewData);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'فشل في تحميل الصفحة');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Portfolios By Company
    |--------------------------------------------------------------------------
    */
    public function getPortfolios(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|integer|exists:companies,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صحيحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $portfolios = $this->reconciliationService->getPortfoliosForCompany($request->company_id);

            return response()->json([
                'success' => true,
                'portfolios' => $portfolios->map(function ($portfolio) {
                    return [
                        'id' => $portfolio->id,
                        'name' => $portfolio->name
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تحميل المحافظ'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Analyze File Headers
    |--------------------------------------------------------------------------
    */
    public function analyzeHeaders(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:xlsx,xls|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $headers = $this->reconciliationService->analyzeFileHeaders($request->file('file'));

            return response()->json([
                'success' => true,
                'headers' => $headers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تحليل ملف Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Store Temporary File
    |--------------------------------------------------------------------------
    */
    public function storeTemporaryFile(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|mimes:xlsx,xls|max:51200',
            ], [
                'file.required' => 'الملف مطلوب.',
                'file.file' => 'يجب أن يكون الملف صالحاً.',
                'file.mimes' => 'يجب أن يكون الملف من نوع Excel (xlsx أو xls).',
                'file.max' => 'يجب ألا يتجاوز حجم الملف 50 ميجابايت.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $result = $this->reconciliationService->storeTemporaryFile($request->file('file'));

            return response()->json([
                'success' => true,
                'temp_file_path' => $result['temp_file_path'],
                'total_rows' => $result['total_rows']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في رفع الملف: ' . $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Process Reconciliation
    |--------------------------------------------------------------------------
    */
    public function process(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'temp_file_path' => 'required|string',
                'company_id' => 'required|integer|exists:companies,id',
                'portfolio_id' => 'required|integer|exists:portfolios,id',
                'reconciliation_month' => 'required|date',
                'field_mappings' => 'required|array',
                'field_mappings.account_number' => 'required|array',
                'field_mappings.account_number.column' => 'required|string',
                'field_mappings.collected_amount' => 'required|array',
                'field_mappings.collected_amount.column' => 'required|string',
                'field_mappings.collection_date' => 'required|array',
                'field_mappings.collection_date.column' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $jobId = $this->reconciliationService->queueReconciliationProcess(
                $validator->validated(),
                $request->field_mappings
            );

            return response()->json([
                'success' => true,
                'job_id' => $jobId,
                'message' => 'تم بدء عملية المطابقة'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تنفيذ المطابقة: ' . $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Progress Status
    |--------------------------------------------------------------------------
    */
    public function getProgress(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'job_id' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'معرف المهمة مطلوب'
                ], 422);
            }

            $progress = $this->reconciliationService->getProgressStatus($request->job_id);

            return response()->json([
                'success' => true,
                'progress' => $progress
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب حالة التقدم'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Download Summary Report
    |--------------------------------------------------------------------------
    */
    public function downloadSummaryReport(PaymentReconciliation $reconciliation): BinaryFileResponse
    {
        try {
            $filePath = $this->reconciliationService->generateSummaryReport($reconciliation);

            $disk = Storage::disk('tenant_public');

            if (!$disk->exists($filePath)) {
                abort(404, 'الملف غير موجود');
            }

            $fullPath = $disk->path($filePath);
            $fileName = 'summary_report_' . $reconciliation->id . '_' . now()->format('YmdHis') . '.xlsx';

            return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            abort(500, 'فشل في تحميل التقرير: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Download Unmatched Report
    |--------------------------------------------------------------------------
    */
    public function downloadUnmatchedReport(PaymentReconciliation $reconciliation): BinaryFileResponse
    {
        try {
            $filePath = $this->reconciliationService->generateUnmatchedReport($reconciliation);

            $disk = Storage::disk('tenant_public');

            if (!$disk->exists($filePath)) {
                abort(404, 'الملف غير موجود');
            }

            $fullPath = $disk->path($filePath);
            $fileName = 'unmatched_report_' . $reconciliation->id . '_' . now()->format('YmdHis') . '.xlsx';

            return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            abort(500, 'فشل في تحميل التقرير: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Download Discrepancies Report
    |--------------------------------------------------------------------------
    */
    public function downloadDiscrepanciesReport(PaymentReconciliation $reconciliation): BinaryFileResponse
    {
        try {
            $filePath = $this->reconciliationService->generateDiscrepanciesReport($reconciliation);

            $disk = Storage::disk('tenant_public');

            if (!$disk->exists($filePath)) {
                abort(404, 'الملف غير موجود');
            }

            $fullPath = $disk->path($filePath);
            $fileName = 'discrepancies_report_' . $reconciliation->id . '_' . now()->format('YmdHis') . '.xlsx';

            return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            abort(500, 'فشل في تحميل التقرير: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Download System Only Report
    |--------------------------------------------------------------------------
    */
    public function downloadSystemOnlyReport(PaymentReconciliation $reconciliation): BinaryFileResponse
    {
        try {
            $filePath = $this->reconciliationService->generateSystemOnlyReport($reconciliation);

            $disk = Storage::disk('tenant_public');

            if (!$disk->exists($filePath)) {
                abort(404, 'الملف غير موجود');
            }

            $fullPath = $disk->path($filePath);
            $fileName = 'system_only_report_' . $reconciliation->id . '_' . now()->format('YmdHis') . '.xlsx';

            return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            abort(500, 'فشل في تحميل التقرير: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Download Employee Performance Report
    |--------------------------------------------------------------------------
    */
    public function downloadEmployeePerformanceReport(PaymentReconciliation $reconciliation): BinaryFileResponse
    {
        try {
            $filePath = $this->reconciliationService->generateEmployeePerformanceReport($reconciliation);

            $disk = Storage::disk('tenant_public');

            if (!$disk->exists($filePath)) {
                abort(404, 'الملف غير موجود');
            }

            $fullPath = $disk->path($filePath);
            $fileName = 'employee_performance_' . $reconciliation->id . '_' . now()->format('YmdHis') . '.xlsx';

            return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            abort(500, 'فشل في تحميل التقرير: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Download Errors Report
    |--------------------------------------------------------------------------
    */
    public function downloadErrorsReport(PaymentReconciliation $reconciliation): BinaryFileResponse
    {
        try {
            $filePath = $this->reconciliationService->generateErrorsReport($reconciliation);

            $disk = Storage::disk('tenant_public');

            if (!$disk->exists($filePath)) {
                abort(404, 'الملف غير موجود');
            }

            $fullPath = $disk->path($filePath);
            $fileName = 'errors_report_' . $reconciliation->id . '_' . now()->format('YmdHis') . '.xlsx';

            return response()->download($fullPath, $fileName)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            abort(500, 'فشل في تحميل التقرير: ' . $e->getMessage());
        }
    }
}
