<?php

namespace App\Http\Controllers\Tenants\Reports\Performance;

use App\DataTables\Tenants\Reports\Performance\EmployeePerformanceDataTable;
use App\Http\Controllers\Controller;
use App\Services\Tenants\Reports\Performance\EmployeePerformanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeePerformanceReportController extends Controller
{
    public function __construct(private readonly EmployeePerformanceService $service)
    {
        $this->middleware('can:reports.performance.view');
    }

    public function index(EmployeePerformanceDataTable $dataTable): mixed
    {
        $filters = $this->extractFilters();
        $performance = $this->service->getEmployeesPerformance($filters);

        return $dataTable->render('tenants.reports.performance.employees', [
            ...$this->service->getFormData(),
            'employees_statistics' => $this->service->getStatistics($performance),
            'employees_distribution' => $this->service->getDistribution($performance),
            'top_performers' => $this->service->getTopPerformers($performance, 10),
            'chart_data' => $this->service->getChartData($performance),
        ]);
    }

    public function getFilteredStats(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $performance = $this->service->getEmployeesPerformance($filters);

        return response()->json([
            'success' => true,
            'statistics' => $this->service->getStatistics($performance),
            'chart_data' => $this->service->getChartData($performance),
        ]);
    }

    public function exportPDF(Request $request): BinaryFileResponse
    {
        return $this->service->exportPerformanceReportToPDF(   // ← service
            $this->extractFilters($request)
        );
    }

    public function showExportFieldsModal(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $count = $this->service->getRecordsCount($filters);  // ← service

        return response()->json([
            'success' => true,
            'count' => $count,
            'fields' => $this->service->getAvailableExportFields(),
            'is_large_export' => $count > 5000,
        ]);
    }

    public function exportExcel(Request $request): JsonResponse
    {
        $filters = $this->extractFilters($request);
        $selectedFields = $request->input('selected_fields', []);
        $count = $this->service->getRecordsCount($filters);  // ← service

        try {
            if ($count > 5000) {
                $result = $this->service->startAsyncExport($filters, $selectedFields, auth()->id());

                return response()->json([...$result, 'is_async' => true]);
            }

            $fileName = $this->service->exportFilteredPerformanceToExcel($filters, $selectedFields);

            return response()->json([
                'success' => true,
                'is_async' => false,
                'message' => 'تم إنشاء الملف بنجاح',
                'download_url' => tenant_route_name('reports.employees.download', ['file' => $fileName]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: '.$e->getMessage(),
            ], 500);
        }
    }

    public function checkExportProgress(Request $request): JsonResponse
    {
        $exportId = $request->input('export_id');

        if (! $exportId) {
            return response()->json(['success' => false, 'message' => 'معرف التصدير مطلوب'], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $this->service->getExportProgress($exportId),  // ← service
        ]);
    }

    public function checkRecordsCount(Request $request): JsonResponse
    {
        $count = $this->service->getRecordsCount($this->extractFilters($request));  // ← service

        return response()->json([
            'success' => true,
            'count' => $count,
            'message' => "تم العثور على {$count} سجل",
        ]);
    }

    public function downloadFile(string $file): BinaryFileResponse
    {
        $disk = Storage::disk('tenant_public');
        $path = 'exports/'.$file;

        abort_unless($disk->exists($path), 404, 'الملف غير موجود');

        return response()->download($disk->path($path), $file)->deleteFileAfterSend();
    }

    // ── Helper ──────────────────────────────────────────────────────
    private function extractFilters(?Request $request = null): array
    {
        $req = $request ?? request();

        return array_filter(
            $req->only([
                'date_from',
                'date_to',
                'company_id',
                'portfolio_id',
                'team_id',
                'user_id',
            ]),
            fn ($v) => $v !== null && $v !== ''
        );
    }
}
