<?php

namespace App\Jobs\Tenants\Reports\Company;

use App\Exports\Tenants\Reports\Company\CompanyReportExport;
use App\Services\Tenants\BusinessCenter\Company\CompanyReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportCompanyReportJob implements ShouldQueue
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

    public function __construct($filters, $selectedFields, $userId)
    {
        $this->filters = $filters;
        $this->selectedFields = $selectedFields;
        $this->userId = $userId;
        $this->exportId = uniqid('company_report_export_', true);
        $this->fileName = 'تقرير_الشركات_'.now()->format('Y_m_d_H_i_s').'_'.$this->exportId.'.xlsx';
        $this->onQueue('exports');
    }

    public function handle(CompanyReportService $companyReportService): void
    {
        try {
            $this->updateProgress(0, 'بدء عملية تصدير تقرير الشركات...');

            $rows = $companyReportService->getCompanyReportRows($this->filters);
            $totalCount = count($rows);

            if ($totalCount === 0) {
                Cache::put("company_report_export_result_{$this->exportId}", [
                    'status' => 'completed',
                    'message' => 'لا توجد بيانات للتصدير',
                    'total_records' => 0,
                    'created_at' => now()->toDateTimeString(),
                ], now()->addHours(24));

                $this->updateProgress(100, 'لا توجد بيانات للتصدير');

                return;
            }

            $this->updateProgress(10, "جاري تصدير {$totalCount} سجل...");
            $this->updateProgress(50, 'إنشاء ملف Excel...');

            Excel::store(
                new CompanyReportExport($rows, $this->filters, $this->selectedFields),
                'exports/'.$this->fileName,
                'tenant_public'
            );

            $fileSize = Storage::disk('tenant_public')->size('exports/'.$this->fileName);

            Cache::put("company_report_export_result_{$this->exportId}", [
                'status' => 'completed',
                'file_name' => $this->fileName,
                'file_size' => $this->formatBytes($fileSize),
                'total_records' => $totalCount,
                'created_at' => now()->toDateTimeString(),
            ], now()->addHours(24));

            $this->updateProgress(100, 'تم التصدير بنجاح');
        } catch (\Throwable $e) {
            Cache::put("company_report_export_result_{$this->exportId}", [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'created_at' => now()->toDateTimeString(),
            ], now()->addHours(24));

            $this->updateProgress(-1, 'فشل في التصدير: '.$e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Cache::put("company_report_export_result_{$this->exportId}", [
            'status' => 'failed',
            'error' => $exception->getMessage(),
            'created_at' => now()->toDateTimeString(),
        ], now()->addHours(24));
    }

    public function getExportId(): string
    {
        return $this->exportId;
    }

    private function updateProgress($percentage, $message): void
    {
        Cache::put("company_report_export_progress_{$this->exportId}", [
            'percentage' => $percentage,
            'message' => $message,
            'updated_at' => now()->toDateTimeString(),
        ], now()->addHours(2));
    }

    private function formatBytes($size, $precision = 2): string
    {
        if ($size <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($size > 1024 && $index < count($units) - 1) {
            $size /= 1024;
            $index++;
        }

        return round($size, $precision).' '.$units[$index];
    }
}
