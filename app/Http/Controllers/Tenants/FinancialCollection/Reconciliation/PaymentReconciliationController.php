<?php

namespace App\Http\Controllers\Tenants\FinancialCollection\Reconciliation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\FinancialCollection\Reconciliation\AnalyzeReconciliationFileRequest;
use App\Http\Requests\Tenants\FinancialCollection\Reconciliation\GetReconciliationPortfoliosRequest;
use App\Http\Requests\Tenants\FinancialCollection\Reconciliation\GetReconciliationProgressRequest;
use App\Http\Requests\Tenants\FinancialCollection\Reconciliation\ProcessReconciliationRequest;
use App\Http\Requests\Tenants\FinancialCollection\Reconciliation\ReconciliationRequest;
use App\Http\Requests\Tenants\FinancialCollection\Reconciliation\StoreReconciliationFileRequest;
use App\Models\Tenants\FinancialCollection\Reconciliation\PaymentReconciliation;
use App\Services\Tenants\FinancialCollection\Reconciliation\PaymentReconciliationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
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
        $this->authorizeResource(PaymentReconciliation::class, 'reconciliation');
        $this->middleware(['auth:tenant', 'can:reconciliation.view']);
        $this->middleware('can:view,reconciliation')->only([
            'downloadSummaryReport',
            'downloadUnmatchedReport',
            'downloadDiscrepanciesReport',
            'downloadSystemOnlyReport',
            'downloadEmployeePerformanceReport',
            'downloadErrorsReport',
        ]);
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
    public function getPortfolios(GetReconciliationPortfoliosRequest $request): JsonResponse
    {
        try {
            $portfolios = $this->reconciliationService->getPortfoliosForCompany($request->integer('company_id'));

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
    public function analyzeHeaders(AnalyzeReconciliationFileRequest $request): JsonResponse
    {
        try {
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
    public function storeTemporaryFile(StoreReconciliationFileRequest $request): JsonResponse
    {
        try {
            $result = $this->reconciliationService->storeTemporaryFile($request->file('file'));
            Cache::put(
                ReconciliationRequest::temporaryFileOwnerKey($result['temp_file_path']),
                $request->user('tenant')->getKey(),
                now()->addHours(2),
            );

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
    public function process(ProcessReconciliationRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $jobId = $this->reconciliationService->queueReconciliationProcess(
                $validated,
                $validated['field_mappings']
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
    public function getProgress(GetReconciliationProgressRequest $request): JsonResponse
    {
        try {
            $progress = $this->reconciliationService->getProgressStatus(
                $request->string('job_id')->toString(),
                (int) $request->user('tenant')->getKey(),
            );

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
