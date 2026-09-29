<?php

namespace App\Http\Controllers\Tenants\FinancialCollection\Import;

use App\Http\Controllers\Controller;
use App\Services\Tenants\FinancialCollection\Import\CollectionImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CollectionImportController extends Controller
{
    protected CollectionImportService $importService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(CollectionImportService $importService)
    {
        $this->importService = $importService;
    }

    /*
    |--------------------------------------------------------------------------
    | Show Import Form
    |--------------------------------------------------------------------------
    */
    public function showImportForm()
    {
        $systemOptions = $this->importService->getSystemOptions();
        return view('tenants.financial_collection.import.index', compact('systemOptions'));
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Excel Template
    |--------------------------------------------------------------------------
    */
    public function generateTemplate(): BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $result = $this->importService->generateImportTemplate();

        if ($result['success']) {
            return response()->download($result['file_path'], $result['file_name'])->deleteFileAfterSend(true);
        }

        return response()->json(['success' => false, 'message' => $result['message']], 500);
    }

    /*
    |--------------------------------------------------------------------------
    | Upload And Analyze File
    |--------------------------------------------------------------------------
    */
    public function uploadAndAnalyze(Request $request)
    {
        $request->validate(['import_file' => 'required|mimes:xlsx,xls|max:10240']);

        $result = $this->importService->uploadAndAnalyzeFile($request->file('import_file'));

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /*
    |--------------------------------------------------------------------------
    | Process Import
    |--------------------------------------------------------------------------
    */
    public function processImport(Request $request)
    {
        $request->validate([
            'temp_path' => 'required|string',
            'account_column' => 'required|integer|min:0',
            'amount_column' => 'required|integer|min:0',
            'date_column' => 'required|integer|min:0',
            'date_format' => 'required|string',
            'payment_method_id' => 'required|integer|exists:settings_payment_methods,id',
            'collection_type' => 'required|string|in:cash,scheduled',
            'payment_type' => 'required|string|in:settlement,without_settlement',
            'assigned_user_id' => 'nullable|integer|exists:users,id',
            'notes_column' => 'nullable|integer|min:0'
        ]);

        $importConfig = $request->only([
            'temp_path', 'account_column', 'amount_column', 'date_column',
            'date_format', 'payment_method_id', 'collection_type',
            'payment_type', 'assigned_user_id', 'notes_column'
        ]);

        $importConfig['current_user_id'] = auth()->id();

        $result = $this->importService->processImportFile($importConfig);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Import Progress
    |--------------------------------------------------------------------------
    */
    public function getImportProgress(Request $request)
    {
        $request->validate(['job_id' => 'required|string']);

        $progress = Cache::get("import_progress_{$request->job_id}");

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على معلومات العملية'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $progress
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Download Error Report
    |--------------------------------------------------------------------------
    */
    public function downloadErrorReport(Request $request): BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $reportPath = $request->get('report_path');

        if (!$reportPath || !Storage::disk('local')->exists($reportPath)) {
            return response()->json(['success' => false, 'message' => 'ملف التقرير غير موجود.'], 404);
        }

        return response()->download(storage_path('app/' . $reportPath))->deleteFileAfterSend(true);
    }

    /*
    |--------------------------------------------------------------------------
    | Cancel Import
    |--------------------------------------------------------------------------
    */
    public function cancelImport(Request $request)
    {
        $tempPath = $request->input('temp_path');
        if ($tempPath) {
            Storage::disk('tenant_public')->delete($tempPath);
        }
        return response()->json(['success' => true]);
    }
}