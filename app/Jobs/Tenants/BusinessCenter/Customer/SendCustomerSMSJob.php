<?php

namespace App\Jobs\Tenants\BusinessCenter\Customer;

use App\Models\Tenants\Communication\MessageLog\MessageLog;
use App\Services\Tenants\Communication\MessageLog\MessageLogService;
use App\Services\Tenants\SMS\Customer\CustomerSMSService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendCustomerSMSJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public $tries = 1;

    public $maxExceptions = 1;

    public $backoff = 0;

    protected string $message;

    protected array $customerIds;

    protected ?int $templateId;

    protected string $messageType;

    protected string $uniqueJobId;

    protected ?int $mainLogId;

    protected string $batchId;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(
        string $message,
        array $customerIds,
        ?int $templateId = null,
        string $messageType = 'custom',
        string $batchId = '',
        ?int $mainLogId = null
    ) {
        $this->message = $message;
        $this->customerIds = array_values(array_unique(array_map('intval', $customerIds)));
        $this->templateId = $templateId;
        $this->messageType = $messageType;
        $this->mainLogId = $mainLogId;
        $this->batchId = $batchId;

        // إنشاء معرف فريد مضمون
        $this->uniqueJobId = $batchId.'_'.md5(serialize([
            'message' => substr(md5($message), 0, 8),
            'customers' => $this->customerIds,
            'template' => $templateId,
            'type' => $messageType,
            'timestamp' => microtime(true),
        ]));
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    */
    public function handle(
        CustomerSMSService $smsService,
        MessageLogService $messageLogService
    ) {
        $processedKey = "sms_job_processed_{$this->uniqueJobId}";
        $batchProgressKey = "sms_batch_progress_{$this->batchId}";

        if (Cache::has($processedKey)) {
            return;
        }

        DB::beginTransaction();

        try {
            $this->markJobAsStarted($batchProgressKey);
            if ($this->messageType === 'template') {
                $result = $smsService->sendTemplateMessage($this->templateId, $this->customerIds);
            } else {
                $result = $smsService->sendCustomMessage($this->message, $this->customerIds);
            }

            $this->markJobAsCompleted($batchProgressKey, $result['success']);

            $this->updateMainBatchLog($messageLogService, $result, $batchProgressKey);

            Cache::put($processedKey, [
                'processed_at' => now()->toISOString(),
                'result' => $result['success'],
                'customers_count' => count($this->customerIds),
                'batch_id' => $this->batchId,
                'individual_logs_created' => true,
            ], now()->addDays(7));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->markJobAsFailed($batchProgressKey, $e->getMessage());
            $this->updateMainBatchLogFailure($messageLogService, $e->getMessage());
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    private function markJobAsStarted(string $batchProgressKey): void
    {
        $progress = Cache::get($batchProgressKey, [
            'total_jobs' => 0,
            'started_jobs' => 0,
            'completed_jobs' => 0,
            'failed_jobs' => 0,
            'jobs_status' => [],
        ]);

        $progress['started_jobs']++;
        $progress['jobs_status'][$this->uniqueJobId] = [
            'status' => 'started',
            'started_at' => now()->toISOString(),
            'customers_count' => count($this->customerIds),
        ];

        Cache::put($batchProgressKey, $progress, now()->addHours(24));
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Job As Completed
    |--------------------------------------------------------------------------
    */
    private function markJobAsCompleted(string $batchProgressKey, bool $success): void
    {
        $progress = Cache::get($batchProgressKey, []);

        if ($success) {
            $progress['completed_jobs'] = ($progress['completed_jobs'] ?? 0) + 1;
        } else {
            $progress['failed_jobs'] = ($progress['failed_jobs'] ?? 0) + 1;
        }

        $progress['jobs_status'][$this->uniqueJobId] = [
            'status' => $success ? 'completed' : 'failed',
            'started_at' => $progress['jobs_status'][$this->uniqueJobId]['started_at'] ?? now()->toISOString(),
            'completed_at' => now()->toISOString(),
            'customers_count' => count($this->customerIds),
            'success' => $success,
        ];

        Cache::put($batchProgressKey, $progress, now()->addHours(24));
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Job As Failed
    |--------------------------------------------------------------------------
    */
    private function markJobAsFailed(string $batchProgressKey, string $error): void
    {
        $progress = Cache::get($batchProgressKey, []);

        $progress['failed_jobs'] = ($progress['failed_jobs'] ?? 0) + 1;
        $progress['jobs_status'][$this->uniqueJobId] = [
            'status' => 'failed',
            'started_at' => $progress['jobs_status'][$this->uniqueJobId]['started_at'] ?? now()->toISOString(),
            'failed_at' => now()->toISOString(),
            'customers_count' => count($this->customerIds),
            'error' => $error,
        ];

        Cache::put($batchProgressKey, $progress, now()->addHours(24));
    }

    /*
    |--------------------------------------------------------------------------
    | Update Main Batch Log
    |--------------------------------------------------------------------------
    */
    private function updateMainBatchLog($messageLogService, array $result, string $batchProgressKey): void
    {
        if (! $this->mainLogId) {
            return;
        }

        try {
            $progress = Cache::get($batchProgressKey, []);
            $totalJobs = $this->getTotalJobsCount();
            $completedJobs = ($progress['completed_jobs'] ?? 0);
            $failedJobs = ($progress['failed_jobs'] ?? 0);
            $processedJobs = $completedJobs + $failedJobs;

            $isLastJob = ($processedJobs >= $totalJobs);

            if ($isLastJob) {
                $finalStatus = ($failedJobs > 0) ? 'failed' : 'sent';

                $messageLogService->updateMessageStatus(
                    $this->mainLogId,
                    $finalStatus,
                    [
                        'batch_completed' => true,
                        'batch_id' => $this->batchId,
                        'total_jobs' => $totalJobs,
                        'completed_jobs' => $completedJobs,
                        'failed_jobs' => $failedJobs,
                        'success_rate' => $totalJobs > 0 ? round(($completedJobs / $totalJobs) * 100, 2) : 0,
                        'batch_summary' => [
                            'total_jobs' => $totalJobs,
                            'successful_jobs' => $completedJobs,
                            'failed_jobs' => $failedJobs,
                            'completion_rate' => round(($processedJobs / $totalJobs) * 100, 2).'%',
                        ],
                        'completed_at' => now()->toISOString(),
                        'jobs_details' => $progress['jobs_status'] ?? [],
                    ]
                );

                Cache::forget($batchProgressKey);
            } else {
                $messageLogService->updateMessageStatus(
                    $this->mainLogId,
                    'processing',
                    [
                        'batch_id' => $this->batchId,
                        'jobs_completed' => $processedJobs,
                        'jobs_remaining' => $totalJobs - $processedJobs,
                        'successful_jobs' => $completedJobs,
                        'failed_jobs' => $failedJobs,
                        'progress_percentage' => round(($processedJobs / $totalJobs) * 100, 2),
                        'last_update' => now()->toISOString(),
                    ]
                );
            }
        } catch (\Exception $e) {
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Main Batch Log Failure
    |--------------------------------------------------------------------------
    */
    private function updateMainBatchLogFailure($messageLogService, string $error): void
    {
        if (! $this->mainLogId) {
            return;
        }

        try {
            $messageLogService->updateMessageStatus(
                $this->mainLogId,
                'failed',
                [
                    'job_failed' => true,
                    'batch_id' => $this->batchId,
                    'failed_job_id' => $this->uniqueJobId,
                    'error_message' => $error,
                    'failed_customers' => $this->customerIds,
                    'failed_at' => now()->toISOString(),
                ]
            );
        } catch (\Exception $e) {
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Total Jobs Count
    |--------------------------------------------------------------------------
    */
    private function getTotalJobsCount(): int
    {
        try {
            $mainLog = MessageLog::find($this->mainLogId);

            return $mainLog ? ($mainLog->metadata['total_jobs'] ?? 1) : 1;
        } catch (\Exception $e) {
            return 1;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Failed Method
    |--------------------------------------------------------------------------
    */
    public function failed(\Throwable $exception)
    {
        $failureKey = "sms_job_failed_{$this->uniqueJobId}";
        $batchProgressKey = "sms_batch_progress_{$this->batchId}";

        Cache::put($failureKey, [
            'failed_at' => now()->toISOString(),
            'error' => $exception->getMessage(),
            'customers_affected' => count($this->customerIds),
            'batch_id' => $this->batchId,
            'unique_job_id' => $this->uniqueJobId,
        ], now()->addDays(3));

        $this->markJobAsFailed($batchProgressKey, $exception->getMessage());
    }
}
