<?php

namespace App\Http\Controllers\Tenants\FinancialCollection\Import;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\FinancialCollection\Import\CollectionImportProgressRequest;
use App\Http\Requests\Tenants\FinancialCollection\Import\CollectionImportRequest;
use App\Http\Requests\Tenants\FinancialCollection\Import\CancelCollectionImportRequest;
use App\Http\Requests\Tenants\FinancialCollection\Import\DownloadCollectionImportErrorReportRequest;
use App\Http\Requests\Tenants\FinancialCollection\Import\ProcessCollectionImportRequest;
use App\Http\Requests\Tenants\FinancialCollection\Import\UploadCollectionImportRequest;
use App\Services\Tenants\FinancialCollection\Import\CollectionImportService;
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
        $this->middleware(['auth:tenant', 'can:collections.create']);
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
    public function uploadAndAnalyze(UploadCollectionImportRequest $request)
    {
        $result = $this->importService->uploadAndAnalyzeFile($request->file('import_file'));

        if ($result['success']) {
            Cache::put(
                CollectionImportRequest::temporaryFileOwnerKey($result['data']['temp_path']),
                $request->user('tenant')->getKey(),
                now()->addHours(2),
            );
        }

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /*
    |--------------------------------------------------------------------------
    | Process Import
    |--------------------------------------------------------------------------
    */
    public function processImport(ProcessCollectionImportRequest $request)
    {
        $importConfig = $request->validated();
        $importConfig['current_user_id'] = $request->user('tenant')->getKey();

        $result = $this->importService->processImportFile($importConfig);

        $reportPath = $result['data']['error_report_path'] ?? null;
        if ($result['success'] && is_string($reportPath)) {
            Cache::put(
                CollectionImportRequest::errorReportOwnerKey($reportPath),
                $request->user('tenant')->getKey(),
                now()->addHours(24),
            );
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Import Progress
    |--------------------------------------------------------------------------
    */
    public function getImportProgress(CollectionImportProgressRequest $request)
    {
        $progress = Cache::get('import_progress_' . $request->validated('job_id'));

        if (! is_array($progress) || (int) ($progress['requested_by'] ?? 0) !== (int) $request->user('tenant')->getKey()) {
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
    public function downloadErrorReport(DownloadCollectionImportErrorReportRequest $request): BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $reportPath = $request->validated('report_path');

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
    public function cancelImport(CancelCollectionImportRequest $request)
    {
        $tempPath = $request->validated('temp_path');
        Storage::disk('tenant_public')->delete($tempPath);
        return response()->json(['success' => true]);
    }
}
