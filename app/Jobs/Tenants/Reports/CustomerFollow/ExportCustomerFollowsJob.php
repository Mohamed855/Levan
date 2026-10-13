<?php

namespace App\Jobs\Tenants\Reports\CustomerFollow;

use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * جوب المنسّق: يحسب عدد السجلات والأجزاء، ثم يُطلق الجزء الأول.
 * كل جزء يُطلق الجزء التالي عند اكتماله → عزل كامل للذاكرة بين الأجزاء.
 */
class ExportCustomerFollowsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * الحد الأقصى للسجلات في كل ملف تصدير.
     * 25,000 → آمن مع التحميل الانتقائي للعلاقات ضمن 1 GB.
     */
    public const MAX_ROWS_PER_EXPORT_FILE = 25000;

    public $timeout = 120;

    public $tries = 2;

    public $maxExceptions = 1;

    protected array $filters;

    protected array $selectedFields;

    protected int $userId;

    protected string $exportId;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(array $filters, array $selectedFields, int $userId)
    {
        $this->filters = $filters;
        $this->selectedFields = $selectedFields;
        $this->userId = $userId;
        $this->exportId = uniqid('export_', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Handle - منسّق فقط، لا يعالج Excel
    |--------------------------------------------------------------------------
    */
    public function handle(): void
    {
        try {
            $this->updateProgress(0, 'جاري حساب عدد السجلات...');

            $totalCount = $this->buildCountQuery()->count();

            if ($totalCount === 0) {
                Cache::put("export_result_{$this->exportId}", [
                    'status' => 'completed',
                    'files' => [],
                    'file_sizes' => [],
                    'total_records' => 0,
                    'total_parts' => 0,
                    'message' => 'لا توجد بيانات للتصدير',
                    'created_at' => now()->toDateTimeString(),
                ], now()->addHours(24));

                $this->updateProgress(100, 'لا توجد بيانات للتصدير');

                return;
            }

            $totalParts = (int) ceil($totalCount / self::MAX_ROWS_PER_EXPORT_FILE);
            $timestamp = now()->format('Y_m_d_H_i_s');

            $this->updateProgress(
                3,
                "إجمالي السجلات: {$totalCount}. سيتم إنشاء {$totalParts} ملف...",
                [],
                1,
                $totalParts
            );

            // أطلق الجزء الأول — سيتسلسل بقية الأجزاء تلقائياً
            ExportCustomerFollowsPartJob::dispatch(
                $this->exportId,
                1,
                $totalParts,
                $totalCount,
                0,
                [],
                [],
                $this->filters,
                $this->selectedFields,
                $timestamp
            )->onQueue('exports');

        } catch (\Throwable $e) {
            Log::error('ExportCustomerFollowsJob (coordinator) فشل', [
                'export_id' => $this->exportId,
                'message' => $e->getMessage(),
            ]);

            Cache::put("export_result_{$this->exportId}", [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'created_at' => now()->toDateTimeString(),
            ], now()->addHours(24));

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Count Query (lightweight - no with() or joins)
    |--------------------------------------------------------------------------
    */
    private function buildCountQuery()
    {
        $query = CustomerFollow::query();

        foreach ($this->filters as $key => $value) {
            if (empty($value)) {
                continue;
            }

            switch ($key) {
                case 'date_from':
                    $query->whereDate('created_at', '>=', $value);
                    break;
                case 'date_to':
                    $query->whereDate('created_at', '<=', $value);
                    break;
                case 'customer_id':
                    $query->where('customer_id', $value);
                    break;
                case 'contact_method_id':
                    $query->where('contact_method_id', $value);
                    break;
                case 'customer_status_id':
                    $query->where('customer_status_id', $value);
                    break;
                case 'contact_result_id':
                    $query->where('contact_result_id', $value);
                    break;
                case 'created_by':
                    $query->where('created_by', $value);
                    break;
                case 'company_id':
                    $query->whereHas('customer', fn ($q) => $q->where('company_id', $value));
                    break;
                case 'portfolio_id':
                    $query->whereHas('customer', fn ($q) => $q->where('portfolio_id', $value));
                    break;
                case 'has_upcoming':
                    if ($value == '1') {
                        $query->whereNotNull('next_contact_datetime')
                            ->where('next_contact_datetime', '>', now());
                    } else {
                        $query->whereNull('next_contact_datetime')
                            ->orWhere('next_contact_datetime', '<=', now());
                    }
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
    private function updateProgress(
        int $percentage,
        string $message,
        array $completedFiles = [],
        int $currentPart = 1,
        int $totalParts = 1
    ): void {
        Cache::put("export_progress_{$this->exportId}", [
            'percentage' => $percentage,
            'message' => $message,
            'current_part' => $currentPart,
            'total_parts' => $totalParts,
            'completed_files' => $completedFiles,
            'updated_at' => now()->toDateTimeString(),
        ], now()->addHours(2));
    }

    /*
    |--------------------------------------------------------------------------
    | Failed Job Handler
    |--------------------------------------------------------------------------
    */
    public function failed(\Throwable $exception): void
    {
        Cache::put("export_result_{$this->exportId}", [
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
    public function getExportId(): string
    {
        return $this->exportId;
    }
}
