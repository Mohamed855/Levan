<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\Import;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\BusinessCenter\Customer\Import\CustomerImportPortfolioRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\Import\CustomerImportSessionRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\Import\ImportCustomersRequest;
use App\Http\Requests\Tenants\BusinessCenter\Customer\Import\StartCustomerImportRequest;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Services\Tenants\BusinessCenter\Customer\Import\CustomerImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CustomerImportController extends Controller
{
    protected const VIEW_PATH = 'tenants.business_center.customers.import';
    // protected const ROUTE_NAME  = 'opponents';

    public function __construct(private CustomerImportService $importService)
    {
        $this->middleware('can:customers.create');
    }

    public function index(): View
    {
        $companies = Company::active()->select('id', 'name')->orderBy('name')->get();

        return view(self::VIEW_PATH.'.index', compact('companies'));
    }

    // تحميل القالب بالعناوين حسب المحفظة
    public function downloadTemplate(CustomerImportPortfolioRequest $request)
    {
        return $this->importService->generateImportTemplate(Portfolio::findOrFail($request->validated('portfolio_id')));
    }

    // معاينة للعناوين والحقول حسب المحفظة
    public function previewTemplate(CustomerImportPortfolioRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->importService->getTemplatePreview(Portfolio::findOrFail($request->validated('portfolio_id'))),
        ]);
    }

    // تحليل الملف المرفوع قبل بدء عملية الاستيراد
    public function uploadAndAnalyze(ImportCustomersRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->importService->analyzeImportFile($request->file('import_file'), $validated['portfolio_id']);

        return response()->json($result);
    }

    // بدء عملية الاستيراد
    public function processImport(StartCustomerImportRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $result = $this->importService->startImport(
                $validated['temp_path'],
                $validated['portfolio_id'],
                (int) $request->user('tenant')->getKey(),
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'session_id' => $result['session_id'],
                        'total_rows' => $result['total_rows'],
                        'total_chunks' => $result['total_chunks'],
                        'chunk_size' => $result['chunk_size'],
                        'use_polling' => true,
                    ],
                    'message' => 'تم بدء عملية الاستيراد بنجاح',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ داخلي في الخادم: '.$e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Check Import Status
    |--------------------------------------------------------------------------
    | Get current status of an import session.
    */
    public function checkImportStatus(CustomerImportSessionRequest $request): JsonResponse
    {
        $result = $this->importService->getImportStatus(
            $request->validated('session_id'),
            (int) $request->user('tenant')->getKey(),
        );

        if (! $result['found']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Import
    |--------------------------------------------------------------------------
    | Cancel an ongoing import process.
    */
    public function cancelImport(CustomerImportSessionRequest $request): JsonResponse
    {
        $result = $this->importService->cancelImport(
            $request->validated('session_id'),
            (int) $request->user('tenant')->getKey(),
        );

        return response()->json($result);
    }

    public function cancelQueuedImport(CustomerImportSessionRequest $request): JsonResponse
    {
        return $this->cancelImport($request);
    }

    public function getQueueStatus(CustomerImportSessionRequest $request): JsonResponse
    {
        return $this->checkImportStatus($request);
    }

    /*
    |--------------------------------------------------------------------------
    | Download Error Report
    |--------------------------------------------------------------------------
    | Generate and download Excel error report.
    */
    public function downloadErrorReport(CustomerImportSessionRequest $request)
    {
        return $this->importService->generateErrorReport(
            $request->validated('session_id'),
            (int) $request->user('tenant')->getKey(),
        );
    }
}
