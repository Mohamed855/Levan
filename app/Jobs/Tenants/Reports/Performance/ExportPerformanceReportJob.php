<?php

namespace App\Jobs\Tenants\Reports\Performance;

use App\Exports\Tenants\Reports\Performance\PerformanceReportExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/*
|--------------------------------------------------------------------------
| ✅ Export Performance Report Job - UPDATED
|--------------------------------------------------------------------------
*/

class ExportPerformanceReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800;

    public $tries = 3;

    public $maxExceptions = 1;

    protected $filters;

    protected $selectedFields;

    protected $userId;

    protected $reportType;

    protected $exportId;

    protected $fileName;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct($filters, $selectedFields, $userId, $reportType = 'companies')
    {
        $this->filters = $filters;
        $this->selectedFields = $selectedFields;
        $this->userId = $userId;
        $this->reportType = $reportType;
        $this->exportId = uniqid('performance_export_', true);
        $this->fileName = 'تقرير_أداء_الشركات_'.now()->format('Y_m_d_H_i_s').'.xlsx';

        $this->onQueue('exports');
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Job
    |--------------------------------------------------------------------------
    */
    public function handle()
    {
        try {
            $this->updateProgress(0, 'بدء عملية تصدير تقرير الأداء...');

            // ✅ جلب البيانات حسب نوع التقرير
            $service = app('App\\Services\\Tenants\\Reports\\Performance\\CompanyPerformanceService');
            $data = $service->getCompaniesPerformance($this->filters);

            $totalCount = count($data);

            if ($totalCount === 0) {
                $this->updateProgress(100, 'لا توجد بيانات للتصدير');

                Cache::put("performance_export_result_{$this->exportId}", [
                    'status' => 'completed',
                    'message' => 'لا توجد بيانات للتصدير',
                    'total_records' => 0,
                    'created_at' => now()->toDateTimeString(),
                ], now()->addHours(24));

                return;
            }

            $this->updateProgress(10, "جاري تصدير {$totalCount} سجل...");

            $this->updateProgress(50, 'إنشاء ملف Excel...');

            // ✅ تصدير الملف
            Excel::store(
                new PerformanceReportExport($data, $this->filters, $this->selectedFields, $this->reportType),
                'exports/'.$this->fileName,
                'tenant_public'
            );

            $fileSize = Storage::disk('tenant_public')->size('exports/'.$this->fileName);

            // ✅ حفظ النتيجة
            Cache::put("performance_export_result_{$this->exportId}", [
                'status' => 'completed',
                'file_name' => $this->fileName,
                'file_size' => $this->formatBytes($fileSize),
                'total_records' => $totalCount,
                'download_url' => tenant_route_name('reports.performance.download', ['file' => $this->fileName]),
                'created_at' => now()->toDateTimeString(),
            ], now()->addHours(24));

            $this->updateProgress(100, 'تم التصدير بنجاح');

            Log::info('Performance export completed', [
                'export_id' => $this->exportId,
                'records' => $totalCount,
                'file_size' => $this->formatBytes($fileSize),
            ]);

        } catch (\Exception $e) {
            Log::error('Performance export failed', [
                'export_id' => $this->exportId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Cache::put("performance_export_result_{$this->exportId}", [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'created_at' => now()->toDateTimeString(),
            ], now()->addHours(24));

            $this->updateProgress(-1, 'فشل في التصدير: '.$e->getMessage());

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
        Cache::put("performance_export_progress_{$this->exportId}", [
            'percentage' => $percentage,
            'message' => $message,
            'updated_at' => now()->toDateTimeString(),
        ], now()->addHours(2));
    }

    /*
    |--------------------------------------------------------------------------
    | Format Bytes
    |--------------------------------------------------------------------------
    */
    private function formatBytes($size, $precision = 2)
    {
        if ($size <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($size > 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, $precision).' '.$units[$i];
    }

    /*
    |--------------------------------------------------------------------------
    | Failed Job Handler
    |--------------------------------------------------------------------------
    */
    public function failed(\Throwable $exception)
    {
        Log::error('Performance export job failed', [
            'export_id' => $this->exportId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        Cache::put("performance_export_result_{$this->exportId}", [
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'created_at' => now()->toDateTimeString(),
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
