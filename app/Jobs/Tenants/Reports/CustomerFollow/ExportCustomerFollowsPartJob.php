<?php

namespace App\Jobs\Tenants\Reports\CustomerFollow;

use App\Exports\Tenants\Reports\CustomerFollow\CustomerFollowReportExport;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * جوب معزول لمعالجة جزء واحد فقط من التصدير.
 * كل جزء يعمل في عملية PHP مستقلة → ذاكرة نظيفة تماماً.
 * عند الانتهاء يُطلق الجزء التالي تلقائياً.
 */
class ExportCustomerFollowsPartJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800;

    public $tries = 2;

    public $maxExceptions = 1;

    protected string $exportId;

    protected int $partNumber;

    protected int $totalParts;

    protected int $totalCount;

    protected int $startAfterId;

    protected array $completedFiles;

    protected array $fileSizes;

    protected array $filters;

    protected array $selectedFields;

    protected string $timestamp;

    public function __construct(
        string $exportId,
        int $partNumber,
        int $totalParts,
        int $totalCount,
        int $startAfterId,
        array $completedFiles,
        array $fileSizes,
        array $filters,
        array $selectedFields,
        string $timestamp
    ) {
        $this->exportId = $exportId;
        $this->partNumber = $partNumber;
        $this->totalParts = $totalParts;
        $this->totalCount = $totalCount;
        $this->startAfterId = $startAfterId;
        $this->completedFiles = $completedFiles;
        $this->fileSizes = $fileSizes;
        $this->filters = $filters;
        $this->selectedFields = $selectedFields;
        $this->timestamp = $timestamp;
    }

    /*
    |--------------------------------------------------------------------------
    | Handle
    |--------------------------------------------------------------------------
    */
    public function handle(): void
    {
        // تحقق من طلب الإلغاء قبل البدء
        if (Cache::get("export_cancel_{$this->exportId}")) {
            $this->handleCancellation();

            return;
        }

        try {
            $maxRows = ExportCustomerFollowsJob::MAX_ROWS_PER_EXPORT_FILE;

            $partFileName = $this->totalParts === 1
                ? "customer_follows_{$this->timestamp}_{$this->exportId}.xlsx"
                : "customer_follows_part_{$this->partNumber}_{$this->timestamp}_{$this->exportId}.xlsx";

            $rowsForThisPart = min(
                $maxRows,
                $this->totalCount - (($this->partNumber - 1) * $maxRows)
            );

            $progressBase = (int) ((($this->partNumber - 1) / $this->totalParts) * 85);
            $currentFiles = $this->completedFiles;

            $this->updateProgress(
                $progressBase + 3,
                "جاري إنشاء الجزء {$this->partNumber} من {$this->totalParts} ({$rowsForThisPart} سجل)...",
                $currentFiles,
                5  // part_percentage: بدأ للتو
            );

            $export = new CustomerFollowReportExport(
                $this->buildQuery(),
                $this->filters,
                $this->selectedFields,
                $rowsForThisPart,
                function (int $pct, string $msg) use ($progressBase, $currentFiles) {
                    $scaled = $progressBase + (int) ($pct * (85 / $this->totalParts) / 100);
                    $this->updateProgress($scaled, $msg, $currentFiles, $pct);
                },
                $this->startAfterId,
                $maxRows
            );

            Excel::store($export, 'exports/'.$partFileName, 'tenant_public');

            $lastId = $export->getLastExportedId();
            $newCompletedFiles = array_merge($this->completedFiles, [$partFileName]);
            $newFileSizes = array_merge($this->fileSizes, [
                $this->formatBytes(
                    Storage::disk('tenant_public')->size('exports/'.$partFileName)
                ),
            ]);

            $partProgress = (int) (($this->partNumber / $this->totalParts) * 85);
            $this->updateProgress(
                $partProgress,
                "اكتمل الجزء {$this->partNumber} من {$this->totalParts}",
                $newCompletedFiles,
                100  // part_percentage: اكتمل بالكامل
            );

            unset($export);
            gc_collect_cycles();

            if ($this->partNumber < $this->totalParts) {
                // أطلق الجزء التالي (عملية PHP جديدة = ذاكرة نظيفة)
                self::dispatch(
                    $this->exportId,
                    $this->partNumber + 1,
                    $this->totalParts,
                    $this->totalCount,
                    $lastId,
                    $newCompletedFiles,
                    $newFileSizes,
                    $this->filters,
                    $this->selectedFields,
                    $this->timestamp
                )->onQueue('exports');

            } else {
                // آخر جزء → اكتمل التصدير كلياً
                $message = $this->totalParts > 1
                    ? "تم تقسيم التصدير إلى {$this->totalParts} ملفات لأن عدد السجلات {$this->totalCount} يتجاوز الحد الأقصى {$maxRows} سجل لكل ملف."
                    : "تم التصدير بنجاح ({$this->totalCount} سجل).";

                Cache::put("export_result_{$this->exportId}", [
                    'status' => 'completed',
                    'files' => $newCompletedFiles,
                    'file_sizes' => $newFileSizes,
                    'total_records' => $this->totalCount,
                    'total_parts' => $this->totalParts,
                    'message' => $message,
                    'created_at' => now()->toDateTimeString(),
                ], now()->addHours(24));

                $this->updateProgress(100, $message, $newCompletedFiles, 100);
            }

        } catch (\Throwable $e) {
            $this->handleFailure($e);
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Build Query — يحمّل فقط العلاقات المطلوبة حسب الحقول المختارة
    |--------------------------------------------------------------------------
    */
    private function buildQuery()
    {
        $query = CustomerFollow::with($this->getRequiredRelations());

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
                    $query->whereHas('customer', function ($q) use ($value) {
                        $q->where('company_id', $value);
                    });
                    break;
                case 'portfolio_id':
                    $query->whereHas('customer', function ($q) use ($value) {
                        $q->where('portfolio_id', $value);
                    });
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
    | Get Required Relations (selective loading)
    | يُقلّل استهلاك الذاكرة بتحميل العلاقات التي تحتاجها الحقول المختارة فعلاً
    |--------------------------------------------------------------------------
    */
    private function getRequiredRelations(): array
    {
        $fieldRelationMap = [
            'customer_name' => ['customer'],
            'customer_account_number' => ['customer'],
            'customer_identity_number' => ['customer'],
            'company_name' => ['customer.company'],
            'portfolio_name' => ['customer.portfolio'],
            'contact_method' => ['contactMethod'],
            'customer_status' => ['customerStatus'],
            'contact_result' => ['contactResult'],
            'created_by' => ['createdBy'],
            'payment_amount' => ['fullPaymentDetails'],
            'payment_date' => ['fullPaymentDetails'],
            'payment_type' => ['fullPaymentDetails'],
            'partial_payment_amount' => ['partialPaymentDetails'],
            'partial_payment_date' => ['partialPaymentDetails'],
            'promise_amount' => ['promisePaymentDetails'],
            'promise_date' => ['promisePaymentDetails'],
            'callback_date' => ['callbackDetails'],
            'callback_reason' => ['callbackDetails'],
            'documents_date' => ['documentRequestDetails'],
            'documents_reason' => ['documentRequestDetails.documentReason'],
            'objection_reason' => ['objectionDetails'],
            'recovery_reason' => ['recoveryDetails'],
            // الحقول المباشرة (id, contact_location, contacted_person*, notes, created_at)
            // لا تحتاج علاقات
        ];

        $relations = ['customer'];  // customer دائماً ضروري

        foreach ($this->selectedFields as $field) {
            if (! empty($fieldRelationMap[$field])) {
                foreach ($fieldRelationMap[$field] as $relation) {
                    $relations[] = $relation;
                }
            }
        }

        return array_unique($relations);
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
        int $partPercentage = 0
    ): void {
        Cache::put("export_progress_{$this->exportId}", [
            'percentage' => max(-1, min(100, $percentage)),
            'part_percentage' => max(0, min(100, $partPercentage)),
            'message' => $message,
            'current_part' => $this->partNumber,
            'total_parts' => $this->totalParts,
            'completed_files' => $completedFiles,
            'updated_at' => now()->toDateTimeString(),
        ], now()->addHours(2));
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Cancellation
    |--------------------------------------------------------------------------
    */
    private function handleCancellation(): void
    {
        foreach ($this->completedFiles as $file) {
            try {
                Storage::disk('tenant_public')->delete('exports/'.$file);
            } catch (\Throwable $ignore) {
            }
        }

        Cache::put("export_result_{$this->exportId}", [
            'status' => 'cancelled',
            'message' => 'تم إلغاء التصدير من قبل المستخدم',
            'created_at' => now()->toDateTimeString(),
        ], now()->addHours(24));

        $this->updateProgress(-1, 'تم إلغاء التصدير', []);
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Failure
    |--------------------------------------------------------------------------
    */
    private function handleFailure(\Throwable $e): void
    {
        foreach ($this->completedFiles as $file) {
            try {
                Storage::disk('tenant_public')->delete('exports/'.$file);
            } catch (\Throwable $ignore) {
            }
        }

        Log::error("ExportCustomerFollowsPartJob فشل (الجزء {$this->partNumber}/{$this->totalParts})", [
            'export_id' => $this->exportId,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        Cache::put("export_result_{$this->exportId}", [
            'status' => 'failed',
            'error' => $e->getMessage(),
            'created_at' => now()->toDateTimeString(),
        ], now()->addHours(24));

        $this->updateProgress(-1, 'فشل في التصدير: '.$e->getMessage(), $this->completedFiles);
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

        Log::error("ExportCustomerFollowsPartJob فشل نهائياً (الجزء {$this->partNumber}/{$this->totalParts})", [
            'export_id' => $this->exportId,
            'message' => $exception->getMessage(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Format Bytes
    |--------------------------------------------------------------------------
    */
    private function formatBytes(int $size, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision).' '.$units[$i];
    }
}
