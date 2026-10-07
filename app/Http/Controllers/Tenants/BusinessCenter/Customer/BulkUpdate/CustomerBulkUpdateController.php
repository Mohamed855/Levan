<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\BulkUpdate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate\AnalyzeBulkUpdateFileRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate\BulkUpdateRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate\DownloadBulkExclusionTemplateRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate\DownloadBulkUpdateErrorReportRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate\DownloadBulkUpdateTemplateRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate\GetBulkUpdateFieldsRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate\GetBulkUpdatePortfoliosRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate\GetBulkUpdateProgressRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate\ProcessBulkExclusionRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate\ProcessBulkUpdateRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\BulkUpdate\StoreBulkUpdateFileRequest;
use App\Services\Tenants\BusinessCenter\Customer\BulkUpdate\CustomerBulkUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerBulkUpdateController extends Controller
{
    protected CustomerBulkUpdateService $bulkUpdateService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(CustomerBulkUpdateService $bulkUpdateService)
    {
        $this->bulkUpdateService = $bulkUpdateService;
        $this->middleware(['auth:tenant', 'can:customers.update']);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Bulk Update View
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        try {
            $viewData = $this->bulkUpdateService->getInitialViewData();
            return view('tenants.business_center.customers.bulk-update.index', $viewData);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'فشل في تحميل الصفحة');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Portfolios By Company
    |--------------------------------------------------------------------------
    */
    public function getPortfolios(GetBulkUpdatePortfoliosRequest $request): JsonResponse
    {
        try {
            $portfolios = $this->bulkUpdateService->getPortfoliosForCompany($request->integer('company_id'));

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
    | Get Updatable Fields
    |--------------------------------------------------------------------------
    */
    public function getUpdatableFields(GetBulkUpdateFieldsRequest $request): JsonResponse
    {
        try {
            $fields = $this->bulkUpdateService->getUpdatableFieldsForPortfolio($request->integer('portfolio_id'));

            return response()->json([
                'success' => true,
                'fields' => $fields
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تحميل الحقول المتاحة'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Analyze File Headers
    |--------------------------------------------------------------------------
    */
    public function analyzeHeaders(AnalyzeBulkUpdateFileRequest $request): JsonResponse
    {
        try {
            $headers = $this->bulkUpdateService->analyzeFileHeaders($request->file('file'));

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
    | Process Bulk Update
    |--------------------------------------------------------------------------
    */
    public function process(ProcessBulkUpdateRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            if (! Storage::exists($validated['temp_file_path'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'الملف المؤقت غير موجود أو انتهت صلاحيته'
                ], 404);
            }

            $jobId = $this->bulkUpdateService->queueBulkUpdate(
                $validated,
                $validated['field_mappings']
            );

            return response()->json([
                'success' => true,
                'job_id' => $jobId,
                'message' => 'تم بدء عملية التحديث المجمع'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تنفيذ التحديث: ' . $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Progress Status
    |--------------------------------------------------------------------------
    */
    public function getProgress(GetBulkUpdateProgressRequest $request): JsonResponse
    {
        try {
            $jobId = $request->string('job_id')->toString();

            // محاولة جلب Progress للتحديث أولاً
            $progress = $this->bulkUpdateService->getProgressStatus($jobId, (int) $request->user('tenant')->getKey());

            // إذا لم يوجد، نحاول جلب Progress للاستبعاد
            if ($progress['status'] === 'not_found') {
                $progress = $this->bulkUpdateService->getExclusionProgressStatus($jobId, (int) $request->user('tenant')->getKey());
            }

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
    | Download Customized Template
    |--------------------------------------------------------------------------
    */
    public function downloadTemplate(DownloadBulkUpdateTemplateRequest $request)
    {
        try {
            $file = $this->bulkUpdateService->generateTemplate($request->validated());

            return response()->download($file['path'], $file['name'])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return back()->with('error', 'فشل في إنشاء النموذج: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Download Error Report
    |--------------------------------------------------------------------------
    */
    public function downloadErrorReport(DownloadBulkUpdateErrorReportRequest $request): BinaryFileResponse
    {
        $path = $request->string('path')->toString();

        if (! Storage::disk('local')->exists($path)) {
            abort(404, 'ملف التقرير غير موجود أو انتهت صلاحيته');
        }

        $fullPath = storage_path('app/' . $path);
        $fileName = 'error_report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return response()->download($fullPath, $fileName);
    }

    /*
    |--------------------------------------------------------------------------
    | Store Temporary File
    |--------------------------------------------------------------------------
    */
    public function storeTemporaryFile(StoreBulkUpdateFileRequest $request): JsonResponse
    {
        try {
            $validation = $this->bulkUpdateService->validateFileRowCount($request->file('file'));

            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validation['message']
                ], 422);
            }

            $tempPath = $request->file('file')->store('temp_updates');
            Cache::put(
                BulkUpdateRequest::temporaryFileOwnerKey($tempPath),
                $request->user('tenant')->getKey(),
                now()->addHours(2),
            );

            return response()->json([
                'success' => true,
                'temp_file_path' => $tempPath,
                'total_rows' => $validation['total_rows']
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
    | Process Bulk Exclusion
    |--------------------------------------------------------------------------
    */
    public function processExclusion(ProcessBulkExclusionRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            if (! Storage::exists($validated['temp_file_path'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'الملف المؤقت غير موجود أو انتهت صلاحيته'
                ], 404);
            }

            $jobId = $this->bulkUpdateService->queueBulkExclusion(
                $validated
            );

            return response()->json([
                'success' => true,
                'job_id' => $jobId,
                'message' => 'تم بدء عملية استبعاد العملاء'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تنفيذ الاستبعاد: ' . $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Download Exclusion Template
    |--------------------------------------------------------------------------
    */
    public function downloadExclusionTemplate(DownloadBulkExclusionTemplateRequest $request)
    {
        try {
            $file = $this->bulkUpdateService->generateExclusionTemplate($request->validated());

            return response()->download($file['path'], $file['name'])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return back()->with('error', 'فشل في إنشاء النموذج: ' . $e->getMessage());
        }
    }
}
