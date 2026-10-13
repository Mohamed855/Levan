<?php

namespace App\Jobs\Tenants\Reports\Financial;

use App\Exports\Tenants\Reports\Financial\FinancialReportExport;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/*
|--------------------------------------------------------------------------
| Export Financial Collections Job
|--------------------------------------------------------------------------
| Handles the export of financial collections reports to Excel format asynchronously.
*/

class ExportFinancialCollectionsJob implements ShouldQueue
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
        $this->exportId = uniqid('financial_export_', true);
        $this->fileName = 'financial_collections_export_'.now()->format('Y_m_d_H_i_s').'_'.$this->exportId.'.xlsx';

        // تحديد الطابور المخصص
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
            $this->updateProgress(0, 'بدء عملية تصدير التحصيلات...');

            $query = $this->buildQuery();
            $totalCount = $query->count();

            if ($totalCount === 0) {
                $this->updateProgress(100, 'لا توجد بيانات للتصدير');

                return;
            }

            $this->updateProgress(10, "جاري تصدير {$totalCount} تحصيلة...");

            $collections = collect();
            $chunkSize = 1000;
            $processedCount = 0;

            $query->chunk($chunkSize, function ($chunk) use (&$collections, &$processedCount, $totalCount) {
                $collections = $collections->merge($chunk);
                $processedCount += $chunk->count();

                $progress = 10 + (($processedCount / $totalCount) * 70);
                $this->updateProgress($progress, "معالجة {$processedCount} من {$totalCount} تحصيلة...");
            });

            $this->updateProgress(85, 'إنشاء ملف Excel...');

            Excel::store(
                new FinancialReportExport($collections, $this->filters, $this->selectedFields),
                'exports/'.$this->fileName,
                'tenant_public'
            );

            $fileSize = Storage::disk('tenant_public')->size('exports/'.$this->fileName);

            Cache::put("financial_export_result_{$this->exportId}", [
                'status' => 'completed',
                'file_name' => $this->fileName, // فقط الاسم
                'file_size' => $this->formatBytes($fileSize),
                'total_records' => $totalCount,
                'created_at' => now()->toDateTimeString(),
            ], now()->addHours(24));

            $this->updateProgress(100, 'تم التصدير بنجاح');
        } catch (\Exception $e) {
            Cache::put("financial_export_result_{$this->exportId}", [
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
    | Build Query
    |--------------------------------------------------------------------------
    */
    private function buildQuery()
    {
        $query = Collection::with([
            'customer',
            'customer.company',
            'customer.portfolio',
            'paymentMethod',
            'addedBy',
        ]);

        foreach ($this->filters as $key => $value) {
            if (empty($value)) {
                continue;
            }

            switch ($key) {
                case 'date_from':
                    $query->whereDate('collection_date', '>=', $value);
                    break;
                case 'date_to':
                    $query->whereDate('collection_date', '<=', $value);
                    break;
                case 'created_from':
                    $query->whereDate('created_at', '>=', $value);
                    break;
                case 'created_to':
                    $query->whereDate('created_at', '<=', $value);
                    break;
                case 'customer_id':
                    $query->where('customer_id', $value);
                    break;
                case 'status':
                    $query->where('status', $value);
                    break;
                case 'created_by':
                    $query->where('added_by', $value);
                    break;
                case 'amount_from':
                    $query->where('collected_amount', '>=', $value);
                    break;
                case 'amount_to':
                    $query->where('collected_amount', '<=', $value);
                    break;
                case 'company_id':
                    $query->whereHas('customer', function ($q) use ($value) {
                        $q->where('company_id', $value);
                    });
                    break;
                case 'portfolio_id':
                    $query->whereHas('customer', function ($q) use ($value) {
                        $q->where('portfolio_id', $value);
                    });
                    break;
            }
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Update Progress
    |--------------------------------------------------------------------------
    */
    private function updateProgress($percentage, $message)
    {
        Cache::put("financial_export_progress_{$this->exportId}", [
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
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
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
        Cache::put("financial_export_result_{$this->exportId}", [
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
