<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\BulkUpdate;

use App\Http\Controllers\Controller;
use App\Services\Tenants\BusinessCenter\Customer\BulkUpdate\CustomerBulkUpdateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
        $this->middleware('auth');
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

            $portfolios = $this->bulkUpdateService->getPortfoliosForCompany($request->company_id);

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
    public function getUpdatableFields(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'portfolio_id' => 'required|integer|exists:portfolios,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'معرف المحفظة غير صحيح',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fields = $this->bulkUpdateService->getUpdatableFieldsForPortfolio($request->portfolio_id);

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
    public function process(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'temp_file_path' => 'required|string',
                'portfolio_id' => 'required|integer|exists:portfolios,id',
                'match_key' => 'required|string|in:account_number,identity_number',
                'field_mappings' => 'required|array',
                'field_mappings.*.column' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            if (!Storage::exists($request->temp_file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'الملف المؤقت غير موجود أو انتهت صلاحيته'
                ], 404);
            }

            $jobId = $this->bulkUpdateService->queueBulkUpdate(
                $validator->validated(),
                $request->field_mappings
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

            $jobId = $request->job_id;

            // محاولة جلب Progress للتحديث أولاً
            $progress = $this->bulkUpdateService->getProgressStatus($jobId);

            // إذا لم يوجد، نحاول جلب Progress للاستبعاد
            if ($progress['status'] === 'not_found') {
                $progress = $this->bulkUpdateService->getExclusionProgressStatus($jobId);
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
    public function downloadTemplate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'portfolio_id' => 'required|integer|exists:portfolios,id',
                'match_key' => 'required|string|in:account_number,identity_number',
                'update_fields' => 'required|array|min:1',
                'update_fields.*' => 'string',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $file = $this->bulkUpdateService->generateTemplate($validator->validated());

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
    public function downloadErrorReport(Request $request): BinaryFileResponse
    {
        try {
            $path = $request->query('path');

            if (!$path) {
                abort(400, 'مسار الملف غير محدد');
            }

            if (!Storage::disk('local')->exists($path)) {
                abort(404, 'ملف التقرير غير موجود أو انتهت صلاحيته');
            }

            $fullPath = storage_path('app/' . $path);
            $fileName = 'error_report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            return response()->download($fullPath, $fileName);
        } catch (\Exception $e) {
            abort(500, 'فشل في تحميل تقرير الأخطاء');
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

            $validation = $this->bulkUpdateService->validateFileRowCount($request->file('file'));

            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validation['message']
                ], 422);
            }

            $tempPath = $request->file('file')->store('temp_updates');

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
    public function processExclusion(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'temp_file_path' => 'required|string',
                'portfolio_id' => 'required|integer|exists:portfolios,id',
                'exclusion_key' => 'required|string|in:account_number,identity_number',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            if (!Storage::exists($request->temp_file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'الملف المؤقت غير موجود أو انتهت صلاحيته'
                ], 404);
            }

            $jobId = $this->bulkUpdateService->queueBulkExclusion(
                $validator->validated()
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
    public function downloadExclusionTemplate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'exclusion_key' => 'required|string|in:account_number,identity_number',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            $file = $this->bulkUpdateService->generateExclusionTemplate($validator->validated());

            return response()->download($file['path'], $file['name'])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return back()->with('error', 'فشل في إنشاء النموذج: ' . $e->getMessage());
        }
    }
}
