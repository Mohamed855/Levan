<?php

namespace App\Jobs\Tenants\BusinessCenter\Customer\BulkUpdate;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\BusinessCenter\Customer\BulkUpdate\CustomerBulkUpdateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;

class ProcessCustomerBulkExclusion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;

    public int $timeout = 14400;
    public int $tries = 3;
    private const BATCH_SIZE = 200;
    private const MEMORY_LIMIT = '4096M';
    private const READ_CHUNK_SIZE = 1000;
    private const MAX_ALLOWED_ROWS = 50000;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->onQueue('imports');
    }

    public function handle(CustomerBulkUpdateService $service)
    {
        $jobId = $this->data['job_id'];
        $tempPath = $this->data['temp_file_path'];
        $fullPath = storage_path('app/' . $tempPath);

        ini_set('memory_limit', self::MEMORY_LIMIT);
        set_time_limit(0);

        if (!file_exists($fullPath)) {
            $this->updateProgress($jobId, 'failed', 0, 0, 0, 0, 'الملف المؤقت غير موجود');
            return;
        }

        try {
            $this->updateProgress($jobId, 'processing', 0, 0, 0, 0);

            $reader = new XlsxReader();
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);

            $spreadsheet = $reader->load($fullPath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            if ($highestRow < 2) {
                $this->updateProgress($jobId, 'completed', 0, 0, 0, 0);
                Storage::delete($tempPath);
                $spreadsheet->disconnectWorksheets();
                return;
            }

            $totalRows = $highestRow - 1;

            if ($totalRows > self::MAX_ALLOWED_ROWS) {
                $errorMessage = sprintf(
                    'عدد الصفوف في الملف (%s) يتجاوز الحد الأقصى المسموح به (%s صف).',
                    number_format($totalRows),
                    number_format(self::MAX_ALLOWED_ROWS)
                );
                $this->updateProgress($jobId, 'failed', 0, $totalRows, 0, 0, $errorMessage);
                Storage::delete($tempPath);
                $spreadsheet->disconnectWorksheets();
                return;
            }

            $this->updateProgress($jobId, 'processing', 0, $totalRows, 0, 0);

            $result = $this->processExclusionInChunks(
                $sheet,
                $jobId,
                $totalRows,
                $highestRow
            );

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $sheet, $reader);
            gc_collect_cycles();

            $errorReportPath = null;
            if ($result['failed'] > 0 && !empty($result['errors'])) {
                $errorReportPath = $service->generateErrorReport($result['errors']);
            }

            $this->updateProgress(
                $jobId,
                'completed',
                $result['processed'],
                $totalRows,
                $result['excluded'],
                $result['failed'],
                null,
                $errorReportPath
            );

            Storage::delete($tempPath);
        } catch (\Exception $e) {
            $this->updateProgress(
                $jobId,
                'failed',
                0,
                0,
                0,
                0,
                'حدث خطأ غير متوقع: ' . $e->getMessage()
            );
            Storage::delete($tempPath);
            throw $e;
        }
    }

    private function processExclusionInChunks(
        $sheet,
        string $jobId,
        int $totalRows,
        int $highestRow
    ): array {
        $processed = 0;
        $excluded = 0;
        $failed = 0;
        $errors = [];

        $startRow = 2;
        while ($startRow <= $highestRow) {
            $endRow = min($startRow + self::READ_CHUNK_SIZE - 1, $highestRow);
            $chunkData = [];

            for ($row = $startRow; $row <= $endRow; $row++) {
                try {
                    $rowData = $sheet->rangeToArray('A' . $row . ':A' . $row, null, true, false)[0];
                    
                    if (!empty($rowData[0]) && trim($rowData[0]) !== '') {
                        $chunkData[$row] = trim($rowData[0]);
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $processed++;
                    $errors[] = [
                        'row' => $row,
                        'match_value' => '',
                        'message' => 'خطأ في قراءة الصف: ' . $e->getMessage()
                    ];
                }
            }

            if (!empty($chunkData)) {
                $batchResult = $this->processExclusionBatch(
                    $chunkData,
                    $jobId,
                    $totalRows,
                    $processed,
                    $excluded,
                    $failed
                );

                $errors = array_merge($errors, $batchResult['errors']);
            }

            unset($chunkData);

            if ($processed % 500 === 0) {
                gc_collect_cycles();
            }

            $startRow = $endRow + 1;
            usleep(50000);
        }

        return [
            'processed' => $processed,
            'excluded' => $excluded,
            'failed' => $failed,
            'errors' => $errors
        ];
    }

    private function processExclusionBatch(
        array $values,
        string $jobId,
        int $totalRows,
        int &$globalProcessed,
        int &$globalExcluded,
        int &$globalFailed
    ): array {
        $localProcessed = 0;
        $localExcluded = 0;
        $localFailed = 0;
        $errors = [];

        $batches = array_chunk($values, self::BATCH_SIZE, true);

        foreach ($batches as $batch) {
            DB::beginTransaction();

            try {
                foreach ($batch as $rowIndex => $value) {
                    $customers = Customer::where($this->data['exclusion_key'], $value)
                        ->where('portfolio_id', $this->data['portfolio_id'])
                        ->get();

                    if ($customers->isEmpty()) {
                        $localFailed++;
                        $errors[] = [
                            'row' => $rowIndex,
                            'match_value' => $value,
                            'message' => 'لا يوجد عميل بهذا ' . ($this->data['exclusion_key'] === 'account_number' ? 'رقم الحساب' : 'رقم الهوية')
                        ];
                    } else {
                        foreach ($customers as $customer) {
                            // تسجيل في الـ Activity Log
                            activity()
                                ->performedOn($customer)
                                ->causedBy(User::find($this->data['user_id']))
                                ->withProperties([
                                    'action' => 'bulk_exclusion',
                                    'exclusion_key' => $this->data['exclusion_key'],
                                    'value' => $value,
                                    'customer_name' => $customer->name,
                                    'account_number' => $customer->account_number,
                                ])
                                ->log('تم استبعاد العميل عبر الاستبعاد المجمع');

                            $customer->delete();
                        }
                        $localExcluded++;
                    }

                    $localProcessed++;
                }

                DB::commit();

                $globalProcessed += $localProcessed;
                $globalExcluded += $localExcluded;
                $globalFailed += $localFailed;

                $this->updateProgress($jobId, 'processing', $globalProcessed, $totalRows, $globalExcluded, $globalFailed);

            } catch (\Exception $e) {
                DB::rollBack();

                foreach ($batch as $rowIndex => $value) {
                    $localFailed++;
                    $localProcessed++;
                    $globalFailed++;
                    $globalProcessed++;

                    $errors[] = [
                        'row' => $rowIndex,
                        'match_value' => $value,
                        'message' => 'خطأ في معالجة الدفعة: ' . $e->getMessage()
                    ];
                }
            }

            usleep(100000);
        }

        return [
            'processed' => $localProcessed,
            'excluded' => $localExcluded,
            'failed' => $localFailed,
            'errors' => $errors
        ];
    }

    private function updateProgress(
        string $jobId,
        string $status,
        int $processed,
        int $total,
        int $excluded,
        int $failed,
        ?string $error = null,
        ?string $errorReport = null
    ): void {
        $progress = [
            'status' => $status,
            'processed' => $processed,
            'total' => $total,
            'excluded' => $excluded,
            'failed' => $failed,
            'percentage' => $total > 0 ? round(($processed / $total) * 100, 2) : 0,
            'updated_at' => now(),
        ];

        if ($error) {
            $progress['error'] = $error;
        }

        if ($errorReport) {
            $progress['error_report_path'] = $errorReport;
        }

        Cache::put("bulk_exclusion_progress_{$jobId}", $progress, 7200);
    }

    public function failed(\Throwable $exception)
    {
        $jobId = $this->data['job_id'] ?? null;
        if ($jobId) {
            $this->updateProgress(
                $jobId,
                'failed',
                0,
                0,
                0,
                0,
                'فشل في تنفيذ المهمة: ' . $exception->getMessage()
            );
        }

        if (isset($this->data['temp_file_path'])) {
            Storage::delete($this->data['temp_file_path']);
        }
    }
}