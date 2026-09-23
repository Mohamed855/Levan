<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\Import;

use App\Http\Controllers\Controller;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Services\Tenants\BusinessCenter\Customer\Import\CustomerImportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CustomerImportController extends Controller
{
    protected const VIEW_PATH   = 'tenants.business_center.customers.import';
    // protected const ROUTE_NAME  = 'opponents';

    public function __construct(private CustomerImportService $importService) {}

    public function index(): View
    {
        $companies = Company::active()->select('id', 'name')->orderBy('name')->get();

        return view(self::VIEW_PATH . '.index', compact('companies'));
    }

    // تحميل القالب بالعناوين حسب المحفظة
    public function downloadTemplate(Request $request)
    {
        $request->validate(['portfolio_id' => 'required|exists:portfolios,id']);

        return $this->importService->generateImportTemplate(Portfolio::findOrFail($request->portfolio_id));
    }

    // معاينة للعناوين والحقول حسب المحفظة
    public function previewTemplate(Request $request): JsonResponse
    {
        $request->validate(['portfolio_id' => 'required|exists:portfolios,id']);

        return response()->json([
            'success' => true,
            'data'    => $this->importService->getTemplatePreview(Portfolio::findOrFail($request->portfolio_id))
        ]);
    }

    // تحليل الملف المرفوع قبل بدء عملية الاستيراد
    public function uploadAndAnalyze(Request $request): JsonResponse
    {
        $request->validate(['import_file' => 'required|file|mimes:xlsx,xls', 'portfolio_id' => 'required|exists:portfolios,id']);

        $result = $this->importService->analyzeImportFile($request->file('import_file'), $request->portfolio_id);

        return response()->json($result);
    }

    // بدء عملية الاستيراد
    public function processImport(Request $request): JsonResponse
    {
        $request->validate([
            'temp_path'    => 'required|string',
            'portfolio_id' => 'required|exists:portfolios,id'
        ]);

        try {
            $result = $this->importService->startImport($request->temp_path, $request->portfolio_id);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'session_id'   => $result['session_id'],
                        'total_rows'   => $result['total_rows'],
                        'total_chunks' => $result['total_chunks'],
                        'chunk_size'   => $result['chunk_size'],
                        'use_polling'  => true
                    ],
                    'message' => 'تم بدء عملية الاستيراد بنجاح'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ داخلي في الخادم: ' . $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Check Import Status
    |--------------------------------------------------------------------------
    | Get current status of an import session.
    */
    public function checkImportStatus(Request $request): JsonResponse
    {
        $request->validate(['session_id' => 'required|string']);
        $result = $this->importService->getImportStatus($request->session_id);

        if (!$result['found']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $result['data']
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Import
    |--------------------------------------------------------------------------
    | Cancel an ongoing import process.
    */
    public function cancelImport(Request $request): JsonResponse
    {
        $request->validate(['session_id' => 'required|string']);
        $result = $this->importService->cancelImport($request->session_id);
        return response()->json($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Download Error Report
    |--------------------------------------------------------------------------
    | Generate and download Excel error report.
    */
    public function downloadErrorReport(Request $request)
    {
        $request->validate(['session_id' => 'required|string']);
        return $this->importService->generateErrorReport($request->session_id);
    }
}
