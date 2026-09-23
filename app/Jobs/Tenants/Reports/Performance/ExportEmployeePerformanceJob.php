<?php

namespace App\Jobs\Tenants\Reports\Performance;

use App\Exports\Tenants\Reports\Performance\EmployeePerformanceExport;
use App\Services\Tenants\Reports\Performance\EmployeePerformanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportEmployeePerformanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800;
    public $tries = 3;
    public $maxExceptions = 1;

    protected $filters;
    protected $selectedFields;
    protected $userId;
    protected $exportId;
    protected $fileName;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct($filters, $selectedFields, $userId)
    {
        $this->filters = $filters;
        $this->selectedFields = $selectedFields;
        $this->userId = $userId;
        $this->exportId = uniqid('employee_performance_export_', true);
        $this->fileName = 'employee_performance_report_export_' . now()->format('Y_m_d_H_i_s') . '_' . $this->exportId . '.xlsx';
        $this->onQueue('exports');
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Job
    |--------------------------------------------------------------------------
    */
    public function handle(EmployeePerformanceService $employeePerformanceService)
    {
        try {
            $this->updateProgress(0, 'بدء عملية تصدير تقرير أداء الموظفين...');

            $employeesPerformance = $employeePerformanceService->getEmployeesPerformance($this->filters);
            $totalCount = count($employeesPerformance);

            if ($totalCount === 0) {
                $this->updateProgress(100, 'لا توجد بيانات للتصدير');
                return;
            }

            $this->updateProgress(10, "جاري تصدير {$totalCount} سجل...");
            $this->updateProgress(50, 'إنشاء ملف Excel...');

            Excel::store(
                new EmployeePerformanceExport($employeesPerformance, $this->filters, $this->selectedFields),
                'exports/' . $this->fileName,
                'tenant_public'
            );

            $fileSize = Storage::disk('tenant_public')->size('exports/' . $this->fileName);

            Cache::put("employee_performance_export_result_{$this->exportId}", [
                'status'        => 'completed',
                'file_name'     => $this->fileName,
                'file_size'     => $this->formatBytes($fileSize),
                'total_records' => $totalCount,
                'created_at'    => now()->toDateTimeString()
            ], now()->addHours(24));

            $this->updateProgress(100, 'تم التصدير بنجاح');
        } catch (\Exception $e) {
            Cache::put("employee_performance_export_result_{$this->exportId}", [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'created_at' => now()->toDateTimeString()
            ], now()->addHours(24));

            $this->updateProgress(-1, 'فشل في التصدير: ' . $e->getMessage());
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Progress
    |--------------------------------------------------------------------------
    */
    private function updateProgress($percentage, $message)
    {
        Cache::put("employee_performance_export_progress_{$this->exportId}", [
            'percentage' => $percentage,
            'message' => $message,
            'updated_at' => now()->toDateTimeString()
        ], now()->addHours(2));
    }

    /*
    |--------------------------------------------------------------------------
    | Format Bytes
    |--------------------------------------------------------------------------
    */
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision) . ' ' . $units[$i];
    }

    /*
    |--------------------------------------------------------------------------
    | Failed Job Handler
    |--------------------------------------------------------------------------
    */
    public function failed(\Throwable $exception)
    {
        Cache::put("employee_performance_export_result_{$this->exportId}", [
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'created_at' => now()->toDateTimeString()
        ], now()->addHours(24));
    }

    /*
    |--------------------------------------------------------------------------
    | Get Export ID
    |--------------------------------------------------------------------------
    */
    public function getExportId()
    {
        return $this->exportId;
    }
}
