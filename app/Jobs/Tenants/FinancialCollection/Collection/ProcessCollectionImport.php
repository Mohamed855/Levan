<?php

namespace App\Jobs\Tenants\FinancialCollection\Collection;

use App\Http\Requests\Tenants\FinancialCollection\Import\CollectionImportRequest;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Services\Tenants\FinancialCollection\Import\CollectionImportReportService;
use App\Services\Tenants\FinancialCollection\Import\CollectionImportValidationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessCollectionImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    public $tries = 3;

    public $backoff = [30, 60, 120]; // تأخير متزايد بين المحاولات

    protected string $jobId;

    protected array $importConfig;

    protected array $dataRows;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(string $jobId, array $importConfig, array $dataRows)
    {
        $this->jobId = $jobId;
        $this->importConfig = $importConfig;
        $this->dataRows = $dataRows;
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Job Execution
    |--------------------------------------------------------------------------
    */
    public function handle(
        CollectionImportValidationService $validationService,
        CollectionImportReportService $reportService
    ): void {
        try {
            $this->safeUpdateJobProgress(0, 'جاري بدء عملية الاستيراد...');

            $results = [
                'total_rows' => count($this->dataRows),
                'successful' => 0,
                'failed' => 0,
                'errors' => [],
                'new_collections' => [],
            ];

            DB::beginTransaction();

            try {
                // تحميل البيانات المرجعية مع معالجة الأخطاء
                $this->safeUpdateJobProgress(5, 'جاري تحميل البيانات المرجعية...');
                $validationService->preloadCache($this->dataRows, $this->importConfig);

                // إضافة التحسين الأول هنا
                $totalRows = count($this->dataRows);
                $this->safeUpdateJobProgress(10, 'تم تحميل البيانات المرجعية - جاري معالجة '.number_format($totalRows).' سجل');

                $batchSize = 50;
                $processedRows = 0;

                foreach (array_chunk($this->dataRows, $batchSize, true) as $batchIndex => $batch) {
                    try {
                        $this->processBatch($batch, $validationService, $results, $processedRows, $totalRows);
                    } catch (\Exception $e) {
                        // Log::error("خطأ في معالجة الدفعة {$batchIndex}: " . $e->getMessage(), [
                        //     'job_id' => $this->jobId,
                        //     'batch_index' => $batchIndex,
                        //     'error' => $e->getMessage(),
                        //     'trace' => $e->getTraceAsString()
                        // ]);

                        $this->processRowsIndividually($batch, $validationService, $results, $processedRows, $totalRows);
                    }
                }

                // إضافة التحسين الثاني هنا
                $this->safeUpdateJobProgress(90, 'تم معالجة جميع السجلات - جاري إنشاء التقرير النهائي');

                if ($results['failed'] > 0) {
                    try {
                        $reportData = [
                            'errors' => $results['errors'],
                            'original_data' => $this->dataRows,
                            'import_config' => $this->importConfig,
                        ];
                        $errorReportPath = $reportService->generateImportErrorReport($reportData);
                        $results['error_report_path'] = $errorReportPath;

                        if ($errorReportPath !== '') {
                            Cache::put(
                                CollectionImportRequest::errorReportOwnerKey($errorReportPath),
                                $this->importConfig['current_user_id'],
                                now()->addHours(24),
                            );
                        }
                    } catch (\Exception $e) {
                        // Log::error("خطأ في إنشاء تقرير الأخطاء: " . $e->getMessage(), [
                        //     'job_id' => $this->jobId
                        // ]);
                    }
                }

                $this->safeUpdateJobProgress(95, 'جاري حفظ النتائج النهائية...');

                DB::commit();

                // إضافة التحسين الثالث هنا
                $finalMessage = 'اكتملت العملية - نجح: '.number_format($results['successful']).' | فشل: '.number_format($results['failed']);
                $this->safeUpdateJobProgress(100, $finalMessage, $results);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            // Log::error("فشل في تنفيذ Job الاستيراد: " . $e->getMessage(), [
            //     'job_id' => $this->jobId,
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);

            $this->safeUpdateJobProgress(0, 'فشلت عملية الاستيراد: '.$e->getMessage(), null, true);
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Process Batch Safely
    |--------------------------------------------------------------------------
    */
    private function processBatch(array $batch, CollectionImportValidationService $validationService, array &$results, int &$processedRows, int $totalRows): void
    {
        foreach ($batch as $rowIndex => $row) {
            try {
                $validation = $validationService->validateRowData($row, $this->importConfig, $rowIndex + 2);

                if (! $validation['success']) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'row' => $rowIndex + 2,
                        'errors' => $validation['errors'],
                    ];
                } else {
                    try {
                        $collection = $this->createCollectionFromRow($validation['data']);
                        $results['successful']++;
                        $results['new_collections'][] = $collection->id;
                    } catch (\Exception $e) {
                        $results['failed']++;
                        $results['errors'][] = [
                            'row' => $rowIndex + 2,
                            'errors' => ['خطأ في إنشاء التحصيل: '.$e->getMessage()],
                        ];

                        // Log::error("خطأ في إنشاء التحصيل للصف {$rowIndex}: " . $e->getMessage(), [
                        //     'job_id' => $this->jobId,
                        //     'row_index' => $rowIndex
                        // ]);
                    }
                }

                $processedRows++;

                // إضافة التحسين هنا
                if ($processedRows % 25 === 0) {
                    $progress = 10 + (($processedRows / $totalRows) * 80);
                    $progressMessage = 'تم معالجة '.number_format($processedRows).' من '.number_format($totalRows).' سجل';

                    $this->safeUpdateJobProgress(
                        (int) $progress,
                        $progressMessage
                    );
                }
            } catch (\Exception $e) {
                // Log::error("خطأ في معالجة الصف {$rowIndex}: " . $e->getMessage(), [
                //     'job_id' => $this->jobId,
                //     'row_index' => $rowIndex
                // ]);

                $results['failed']++;
                $results['errors'][] = [
                    'row' => $rowIndex + 2,
                    'errors' => ['خطأ في معالجة الصف: '.$e->getMessage()],
                ];

                $processedRows++;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Process Rows Individually (Fallback)
    |--------------------------------------------------------------------------
    */
    private function processRowsIndividually(array $batch, CollectionImportValidationService $validationService, array &$results, int &$processedRows, int $totalRows): void
    {
        foreach ($batch as $rowIndex => $row) {
            try {
                $this->processBatch([$rowIndex => $row], $validationService, $results, $processedRows, $totalRows);
            } catch (\Exception $e) {
                // Log::error("فشل في معالجة الصف {$rowIndex} حتى فردياً: " . $e->getMessage(), [
                //     'job_id' => $this->jobId,
                //     'row_index' => $rowIndex
                // ]);

                $results['failed']++;
                $results['errors'][] = [
                    'row' => $rowIndex + 2,
                    'errors' => ['فشل كامل في معالجة الصف'],
                ];
                $processedRows++;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Safe Update Job Progress
    |--------------------------------------------------------------------------
    */
    protected function safeUpdateJobProgress(int $progress, string $message, ?array $results = null, bool $failed = false): void
    {
        try {
            $data = [
                'requested_by' => $this->importConfig['current_user_id'],
                'progress' => max(0, min(100, $progress)), // تأكد من أن التقدم بين 0-100
                'message' => $message,
                'completed' => $progress === 100,
                'failed' => $failed,
                'updated_at' => now()->toISOString(),
                'job_id' => $this->jobId, // إضافة معرف الوظيفة للتتبع
            ];

            if ($results !== null) {
                $data['results'] = $results;
            }

            // محاولة حفظ التقدم مع إعادة المحاولة
            $retries = 3;
            while ($retries > 0) {
                try {
                    Cache::put("import_progress_{$this->jobId}", $data, now()->addHours(24));
                    break;
                } catch (\Exception $e) {
                    $retries--;
                    if ($retries === 0) {
                        // Log::error("فشل في تحديث تقدم الاستيراد: " . $e->getMessage(), [
                        //     'job_id' => $this->jobId,
                        //     'progress' => $progress,
                        //     'message' => $message
                        // ]);
                    } else {
                        usleep(500000); // انتظار نصف ثانية قبل إعادة المحاولة
                    }
                }
            }
        } catch (\Exception $e) {
            // Log::error("خطأ عام في تحديث التقدم: " . $e->getMessage(), [
            //     'job_id' => $this->jobId
            // ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Job Failure
    |--------------------------------------------------------------------------
    */
    public function failed(\Throwable $exception): void
    {
        // Log::error("فشل Job الاستيراد نهائياً: " . $exception->getMessage(), [
        //     'job_id' => $this->jobId,
        //     'exception' => $exception->getMessage(),
        //     'trace' => $exception->getTraceAsString()
        // ]);

        $this->safeUpdateJobProgress(0, 'فشلت عملية الاستيراد نهائياً: '.$exception->getMessage(), null, true);
    }

    /*
    |--------------------------------------------------------------------------
    | Create Collection From Row
    |--------------------------------------------------------------------------
    */
    protected function createCollectionFromRow(array $validatedData): Collection
    {
        return Collection::create([
            'customer_id' => $validatedData['customer_id'],
            'collected_amount' => $validatedData['collected_amount'],
            'collection_date' => $validatedData['collection_date'],
            'payment_method_id' => $this->importConfig['payment_method_id'],
            'collection_type' => $this->importConfig['collection_type'],
            'payment_type' => $this->importConfig['payment_type'],
            'receipt_number' => $this->generateReceiptNumber(),
            'status' => 'pending',
            'added_by' => $validatedData['assigned_user_id'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Receipt Number
    |--------------------------------------------------------------------------
    */
    protected function generateReceiptNumber(): string
    {
        $prefix = 'RC';
        $date = now()->format('Ymd');
        $lastCollection = Collection::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastCollection ?
            (int) substr($lastCollection->receipt_number, -4) + 1 : 1;

        return $prefix.$date.str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
