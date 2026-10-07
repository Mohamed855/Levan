<?php

namespace App\Jobs\Tenants\BusinessCenter\Customer\BulkUpdate;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Phone\CustomerPhone;
use App\Models\Tenants\BusinessCenter\Customers\FieldValue\CustomerFieldValue;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use App\Models\Tenants\BusinessCenter\Customers\History\CustomerRelationshipHistory;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\BusinessCenter\Customer\BulkUpdate\CustomerBulkUpdateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;

class ProcessCustomerBulkUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;

    public int $timeout = 14400;
    public int $tries = 3;
    public int $maxExceptions = 3;
    private const BATCH_SIZE = 200;
    private const MEMORY_LIMIT = '4096M';
    private const READ_CHUNK_SIZE = 1000;
    private const PROGRESS_UPDATE_INTERVAL = 200;
    private const MAX_ALLOWED_ROWS = 50000;
    private const MEMORY_CLEANUP_INTERVAL = 500;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->onQueue('imports');
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Job Execution
    |--------------------------------------------------------------------------
    */
    public function handle(CustomerBulkUpdateService $service)
    {
        $jobId = $this->data['job_id'];
        $tempPath = $this->data['temp_file_path'];
        $fullPath = storage_path('app/' . $tempPath);

        ini_set('memory_limit', self::MEMORY_LIMIT);
        set_time_limit(0);

        // ✅ تحسينات إضافية للأداء
        ini_set('max_execution_time', 0);
        ini_set('mysql.connect_timeout', 300);

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
                unset($spreadsheet, $sheet, $reader);
                return;
            }

            $totalRows = $highestRow - 1;

            // ✅ التحقق من عدد الصفوف
            if ($totalRows > self::MAX_ALLOWED_ROWS) {
                $errorMessage = sprintf(
                    'عدد الصفوف في الملف (%s) يتجاوز الحد الأقصى المسموح به (%s صف). يرجى تقسيم الملف إلى ملفات أصغر.',
                    number_format($totalRows),
                    number_format(self::MAX_ALLOWED_ROWS)
                );

                $this->updateProgress($jobId, 'failed', 0, $totalRows, 0, 0, $errorMessage);

                Storage::delete($tempPath);
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $sheet, $reader);
                return;
            }

            $headers = $this->cleanHeaders(
                array_values($sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0])
            );

            $matchKeyResult = $this->validateMatchKey($headers);
            if (!$matchKeyResult['valid']) {
                $this->updateProgress($jobId, 'failed', 0, $totalRows, 0, 0, $matchKeyResult['error']);
                Storage::delete($tempPath);
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $sheet, $reader);
                return;
            }

            $matchKeyIndex = $matchKeyResult['index'];

            $this->updateProgress($jobId, 'processing', 0, $totalRows, 0, 0);

            $result = $this->processFileInChunks(
                $sheet,
                $headers,
                $matchKeyIndex,
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
                $service->rememberErrorReportOwner($errorReportPath, (int) $this->data['user_id']);
            }

            $this->updateProgress(
                $jobId,
                'completed',
                $result['processed'],
                $totalRows,
                $result['updated'],
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

    /*
    |--------------------------------------------------------------------------
    | Process File In Chunks - محسّن للأداء
    |--------------------------------------------------------------------------
    */
    private function processFileInChunks(
        $sheet,
        array $headers,
        int $matchKeyIndex,
        string $jobId,
        int $totalRows,
        int $highestRow
    ): array {
        $processed = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        $startRow = 2;
        $chunkNumber = 0;
        while ($startRow <= $highestRow) {
            $chunkNumber++;
            $endRow = min($startRow + self::READ_CHUNK_SIZE - 1, $highestRow);
            $chunkData = [];
            $emptyRowsInChunk = 0;

            for ($row = $startRow; $row <= $endRow; $row++) {
                try {
                    $rowData = $sheet->rangeToArray(
                        'A' . $row . ':' . $sheet->getHighestColumn() . $row,
                        null,
                        true,
                        false
                    )[0];

                    $hasData = false;
                    foreach ($rowData as $cell) {
                        if ($cell !== null && $cell !== '' && trim($cell) !== '') {
                            $hasData = true;
                            break;
                        }
                    }

                    if ($hasData) {
                        $chunkData[$row] = $rowData;
                    } else {
                        $emptyRowsInChunk++;
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
                $batchResult = $this->processBatchesSimple(
                    $chunkData,
                    $headers,
                    $matchKeyIndex,
                    $jobId,
                    $totalRows,
                    $processed,
                    $updated,
                    $failed
                );

                $errors = array_merge($errors, $batchResult['errors']);
            } else {
            }

            unset($chunkData);

            // ✅ تنظيف قوي للذاكرة كل 500 سجل
            if ($processed % self::MEMORY_CLEANUP_INTERVAL === 0) {
                gc_collect_cycles();
            }

            $startRow = $endRow + 1;

            usleep(50000); // راحة 50ms بين الدفعات
        }

        return [
            'processed' => $processed,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => $errors
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Process Batches Simple
    |--------------------------------------------------------------------------
    */
    private function processBatchesSimple(
        array $rows,
        array $headers,
        int $matchKeyIndex,
        string $jobId,
        int $totalRows,
        int &$globalProcessed,
        int &$globalUpdated,
        int &$globalFailed
    ): array {
        $localProcessed = 0;
        $localUpdated = 0;
        $localFailed = 0;
        $errors = [];

        $batches = array_chunk($rows, self::BATCH_SIZE, true);
        $totalBatches = count($batches);
        foreach ($batches as $batchIndex => $batch) {
            $batchNumber = $batchIndex + 1;

            DB::beginTransaction();

            try {
                $batchResult = $this->processBatch($batch, $headers, $matchKeyIndex);

                $localProcessed += $batchResult['processed'];
                $localUpdated += $batchResult['updated'];
                $localFailed += $batchResult['failed'];
                $errors = array_merge($errors, $batchResult['errors']);

                DB::commit();

                // ✅ تحديث العدادات العامة
                $globalProcessed += $batchResult['processed'];
                $globalUpdated += $batchResult['updated'];
                $globalFailed += $batchResult['failed'];
                $this->updateProgress($jobId, 'processing', $globalProcessed, $totalRows, $globalUpdated, $globalFailed);

                unset($batch, $batchResult);
            } catch (\Exception $e) {
                DB::rollBack();

                foreach ($batch as $rowIndex => $row) {
                    $localFailed++;
                    $localProcessed++;
                    $globalFailed++;
                    $globalProcessed++;

                    $errors[] = [
                        'row' => $rowIndex,
                        'match_value' => $row[$matchKeyIndex] ?? '',
                        'message' => 'خطأ في معالجة الدفعة: ' . $e->getMessage()
                    ];
                }
                $this->updateProgress($jobId, 'processing', $globalProcessed, $totalRows, $globalUpdated, $globalFailed);
            }

            usleep(100000);
        }

        return [
            'processed' => $localProcessed,
            'updated' => $localUpdated,
            'failed' => $localFailed,
            'errors' => $errors
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | باقي الدوال (بدون تغيير)
    |--------------------------------------------------------------------------
    */
    private function processBatch(array $batch, array $headers, int $matchKeyIndex): array
    {
        $processed = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($batch as $rowIndex => $row) {
            $rowData = array_values($row);

            if (empty(array_filter($rowData))) {
                $processed++;
                continue;
            }

            $matchValue = trim($rowData[$matchKeyIndex] ?? '');
            if (empty($matchValue)) {
                $failed++;
                $errors[] = [
                    'row' => $rowIndex,
                    'match_value' => $matchValue,
                    'message' => 'قيمة مفتاح المطابقة فارغة'
                ];
                $processed++;
                continue;
            }

            $customers = Customer::where($this->data['match_key'], $matchValue)
                ->where('portfolio_id', $this->data['portfolio_id'])
                ->get();

            if ($customers->isEmpty()) {
                $failed++;
                $errors[] = [
                    'row' => $rowIndex,
                    'match_value' => $matchValue,
                    'message' => 'لا يوجد عملاء بهذا ' . ($this->data['match_key'] === 'account_number' ? 'رقم الحساب' : 'رقم الهوية') . ' في المحفظة المحددة'
                ];
                $processed++;
                continue;
            }

            // تحديث جميع العملاء الذين لهم نفس القيمة
            $allSuccess = true;
            foreach ($customers as $customer) {
                $result = $this->processCustomerRow($customer, $rowData, $headers, $rowIndex, $matchValue);

                if (!empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                }

                if (!$result['success']) {
                    $allSuccess = false;
                }
            }

            if ($allSuccess) {
                $updated++;
            } else {
                $failed++;
            }

            if (!empty($result['errors'])) {
                $errors = array_merge($errors, $result['errors']);
            }

            if ($result['success']) {
                $updated++;
            } else {
                $failed++;
            }

            $processed++;
        }

        return [
            'processed' => $processed,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => $errors
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Process Customer Row
    |--------------------------------------------------------------------------
    */
    private function processCustomerRow(Customer $customer, array $rowData, array $headers, int $rowIndex, string $matchValue): array
    {
        $updateData = [];
        $dynamicData = [];
        $additionalPhones = [];
        $errors = [];
        $debtAmount = null;
        $hasAnySuccessfulUpdate = false;
        $totalFieldsToProcess = 0;
        $fieldsProcessedSuccessfully = 0;

        foreach ($headers as $columnIndex => $header) {
            $fieldMapping = $this->getFieldMapping($header);

            if (!$fieldMapping) continue;

            $newValue = trim($rowData[$columnIndex] ?? '');

            if ($newValue === '' && $newValue !== '0') continue;

            $totalFieldsToProcess++;

            // ✅ معالجة الأرقام الإضافية
            if (preg_match('/^additional_phone_(\d+)$/', $fieldMapping['field'], $matches)) {
                $phoneIndex = (int)$matches[1];

                $validation = $this->validatePhoneNumber($newValue);

                if (!$validation['valid']) {

                    $errors[] = [
                        'row' => $rowIndex,
                        'match_value' => $matchValue,
                        'message' => "فشل في تحديث الهاتف الإضافي {$phoneIndex}: " . $validation['message']
                    ];
                    continue;
                }

                $additionalPhones[$phoneIndex] = $validation['value'];

                $fieldsProcessedSuccessfully++;
                continue;
            }

            $validation = $this->validateFieldValue($fieldMapping['field'], $newValue, $customer);
            if (!$validation['valid']) {
                $errors[] = [
                    'row' => $rowIndex,
                    'match_value' => $matchValue,
                    'message' => "فشل في تحديث حقل ({$this->getFieldDisplayName($fieldMapping['field'])}): " . $validation['message']
                ];
                continue;
            }

            $currentValue = $this->getCurrentFieldValue($customer, $fieldMapping['field']);
            if ($validation['value'] == $currentValue) {
                $fieldsProcessedSuccessfully++;
                continue;
            }

            if ($fieldMapping['type'] === 'dynamic') {
                $fieldId = str_replace('dynamic_', '', $fieldMapping['field']);
                $dynamicData[$fieldId] = $validation['value'];
            } else {
                if ($fieldMapping['field'] === 'employee_number') {
                    $updateData['relationship_manager_id'] = $validation['value'];
                } elseif ($fieldMapping['field'] === 'debt_amount') {
                    $debtAmount = $validation['value'];
                } else {
                    $updateData[$fieldMapping['field']] = $validation['value'];
                }
            }
            $fieldsProcessedSuccessfully++;
        }

        if ($totalFieldsToProcess === 0) {
            return ['success' => true, 'errors' => $errors];
        }

        if (!empty($errors) && ($fieldsProcessedSuccessfully === 0)) {
            return ['success' => false, 'errors' => $errors];
        }

        if (empty($updateData) && empty($dynamicData) && is_null($debtAmount) && empty($additionalPhones) && empty($errors)) {
            $errors[] = [
                'row' => $rowIndex,
                'match_value' => $matchValue,
                'message' => 'لا توجد تغييرات للتحديث - جميع القيم مطابقة للقيم الحالية'
            ];
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $changedFields = [];

            if (!empty($updateData)) {
                $oldManagerId = $customer->relationship_manager_id;

                $customer->update($updateData);
                if (isset($updateData['relationship_manager_id']) && $oldManagerId != $updateData['relationship_manager_id']) {
                    $newManagerId = $updateData['relationship_manager_id'];
                    if (is_null($oldManagerId) && !is_null($newManagerId)) {
                        $changeType = 'assigned';
                    } elseif (!is_null($oldManagerId) && is_null($newManagerId)) {
                        $changeType = 'unassigned';
                    } else {
                        $changeType = 'transfer';
                    }

                    CustomerRelationshipHistory::create([
                        'customer_id' => $customer->id,
                        'previous_manager_id' => $oldManagerId,
                        'new_manager_id' => $newManagerId,
                        'changed_by' => $this->data['user_id'],
                        'change_type' => $changeType,
                        'changed_at' => now(),
                    ]);
                }

                $changedFields = array_merge($changedFields, array_keys($updateData));
                $hasAnySuccessfulUpdate = true;
            }

            if (!empty($dynamicData)) {
                foreach ($dynamicData as $fieldId => $value) {
                    try {
                        CustomerFieldValue::updateOrCreate(
                            ['customer_id' => $customer->id, 'portfolio_field_id' => $fieldId],
                            ['value' => $value]
                        );
                        $changedFields[] = "dynamic_field_$fieldId";
                        $hasAnySuccessfulUpdate = true;
                    } catch (\Exception $e) {
                        $errors[] = [
                            'row' => $rowIndex,
                            'match_value' => $matchValue,
                            'message' => 'فشل في تحديث الحقل المتغير: ' . $e->getMessage()
                        ];
                    }
                }
            }

            // ✅ معالجة الأرقام الإضافية
            if (!empty($additionalPhones)) {

                $phoneResult = $this->updateAdditionalPhones($customer, $additionalPhones, $rowIndex, $matchValue);

                if ($phoneResult['success']) {
                    $changedFields = array_merge($changedFields, $phoneResult['changed_fields']);
                    $hasAnySuccessfulUpdate = true;
                }
                if (!empty($phoneResult['errors'])) {
                    $errors = array_merge($errors, $phoneResult['errors']);
                }
            } else {
            }

            if (!is_null($debtAmount)) {
                try {
                    $this->updateCustomerDebt($customer, $debtAmount);
                    $changedFields[] = 'debt_amount';
                    $hasAnySuccessfulUpdate = true;
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $rowIndex,
                        'match_value' => $matchValue,
                        'message' => 'فشل في تحديث المديونية: ' . $e->getMessage()
                    ];
                }
            }

            if ($hasAnySuccessfulUpdate) {
                activity()
                    ->performedOn($customer)
                    ->causedBy(User::find($this->data['user_id']))
                    ->withProperties([
                        'action' => 'bulk_update',
                        'updated_fields' => $changedFields,
                        'debt_updated' => !is_null($debtAmount),
                        'phones_updated' => !empty($additionalPhones),
                        'partial_errors' => count($errors) > 0,
                        'total_fields_processed' => $totalFieldsToProcess,
                        'successful_fields' => $fieldsProcessedSuccessfully
                    ])
                    ->log('تم تحديث بيانات العميل عبر التحديث المجمع');
            }

            return ['success' => $hasAnySuccessfulUpdate, 'errors' => $errors];
        } catch (\Exception $e) {

            $errors[] = [
                'row' => $rowIndex,
                'match_value' => $matchValue,
                'message' => 'حدث خطأ عام أثناء التحديث: ' . $e->getMessage()
            ];

            return ['success' => false, 'errors' => $errors];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Update Additional Phones
    |--------------------------------------------------------------------------
    */
    private function updateAdditionalPhones(Customer $customer, array $additionalPhones, int $rowIndex, string $matchValue): array
    {
        $changedFields = [];
        $errors = [];
        $success = false;

        try {
            // جلب الأرقام الإضافية الموجودة حاليًا (غير الأساسي)
            $existingPhones = $customer->phones()
                ->where('is_primary', false)
                ->orderBy('id')
                ->get();

            ksort($additionalPhones);


            $phoneIndex = 0;
            foreach ($additionalPhones as $index => $phoneNumber) {

                try {
                    // إذا كان الرقم موجود في الأرقام الحالية
                    if (isset($existingPhones[$phoneIndex])) {
                        $existingPhone = $existingPhones[$phoneIndex];

                        if ($existingPhone->phone !== $phoneNumber) {
                            $existingPhone->update([
                                'phone' => $phoneNumber,
                                'type' => 'mobile'
                            ]);

                            $changedFields[] = "additional_phone_{$index}_updated";
                            $success = true;
                        } else {
                        }
                    } else {

                        $newPhone = CustomerPhone::create([
                            'customer_id' => $customer->id,
                            'phone' => $phoneNumber,
                            'type' => 'mobile',
                            'is_primary' => false,
                            'notes' => "تمت الإضافة عبر التحديث المجمع"
                        ]);

                        $changedFields[] = "additional_phone_{$index}_added";
                        $success = true;
                    }

                    $phoneIndex++;
                } catch (\Exception $e) {

                    $errors[] = [
                        'row' => $rowIndex,
                        'match_value' => $matchValue,
                        'message' => "فشل في تحديث الهاتف الإضافي {$index}: " . $e->getMessage()
                    ];
                }
            }

            return [
                'success' => $success,
                'changed_fields' => $changedFields,
                'errors' => $errors
            ];
        } catch (\Exception $e) {

            return [
                'success' => false,
                'changed_fields' => [],
                'errors' => [[
                    'row' => $rowIndex,
                    'match_value' => $matchValue,
                    'message' => 'خطأ عام في معالجة الأرقام الإضافية: ' . $e->getMessage()
                ]]
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Phone Number
    |--------------------------------------------------------------------------
    */
    private function validatePhoneNumber($value): array
    {
        $value = trim($value);

        if (empty($value)) {
            return ['valid' => false, 'message' => 'رقم الهاتف فارغ'];
        }

        $cleanPhone = preg_replace('/[^\d+]/', '', $value);

        if (strlen($cleanPhone) < 7 || strlen($cleanPhone) > 20) {
            return ['valid' => false, 'message' => 'تنسيق رقم الهاتف غير صحيح'];
        }

        return ['valid' => true, 'value' => $value];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Current Field Value
    |--------------------------------------------------------------------------
    */
    private function cleanHeaders(array $headers): array
    {
        return array_map(function ($header) {
            return trim($header ?? '');
        }, $headers);
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Match Key
    |--------------------------------------------------------------------------
    */
    private function validateMatchKey(array $headers): array
    {
        $fieldMappings = $this->data['field_mappings'] ?? [];
        $matchKeyField = null;

        if (isset($fieldMappings['match_key']['column'])) {
            $matchKeyField = trim($fieldMappings['match_key']['column']);
        }

        if (!$matchKeyField) {
            return [
                'valid' => false,
                'error' => 'لم يتم تحديد عمود مفتاح المطابقة في ربط الحقول'
            ];
        }

        $matchKeyIndex = array_search($matchKeyField, $headers);

        if ($matchKeyIndex === false) {
            foreach ($headers as $index => $header) {
                if (trim(strtolower($header)) === trim(strtolower($matchKeyField))) {
                    $matchKeyIndex = $index;
                    break;
                }
            }
        }

        if ($matchKeyIndex === false) {
            return [
                'valid' => false,
                'error' => "لم يتم العثور على عمود مفتاح المطابقة '{$matchKeyField}' في رؤوس الملف"
            ];
        }

        return [
            'valid' => true,
            'index' => $matchKeyIndex
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Field Display Name
    |--------------------------------------------------------------------------
    */
    private function getFieldDisplayName(string $fieldKey): string
    {
        $displayNames = [
            'debt_amount' => 'مبلغ المديونية',
            'employee_number' => 'الرقم الوظيفي',
            'customer_type' => 'نوع العميل',
            'phone' => 'رقم الهاتف',
            'name' => 'اسم العميل',
            'first_name' => 'الاسم الأول',
            'father_name' => 'اسم الأب',
            'grandfather_name' => 'اسم الجد',
            'family_name' => 'اسم العائلة',
            'identity_number' => 'رقم الهوية',
            'account_number' => 'رقم الحساب'
        ];

        return $displayNames[$fieldKey] ?? $fieldKey;
    }

    /*
    |--------------------------------------------------------------------------
    | Update Customer Debt
    |--------------------------------------------------------------------------
    */
    private function updateCustomerDebt(Customer $customer, float $newAmount): void
    {
        DB::transaction(function () use ($customer, $newAmount) {
            $existingDebits = CustomerTransaction::where('customer_id', $customer->id)
                ->where('type', 'debit')
                ->where(function ($query) {
                    $query->where('description', 'like', '%مديونية أولية%')
                        ->orWhere('description', 'like', '%المديونية الأولية%')
                        ->orWhere('description', 'like', '%التحديث المجمع%')
                        ->orWhere('description', 'like', '%bulk%');
                })
                ->get();

            if ($existingDebits->isNotEmpty()) {
                foreach ($existingDebits as $debit) {
                    activity()
                        ->performedOn($debit)
                        ->causedBy(User::find($this->data['user_id']))
                        ->withProperties([
                            'old_amount' => $debit->amount,
                            'customer_id' => $customer->id,
                            'action' => 'delete_old_debt',
                            'reason' => 'bulk_update_replacement'
                        ])
                        ->log('تم حذف المديونية الأولية السابقة قبل التحديث المجمع');

                    $debit->forceDelete();
                }
            }

            $newTransaction = CustomerTransaction::create([
                'customer_id' => $customer->id,
                'type' => 'debit',
                'amount' => $newAmount,
                'transaction_date' => now(),
                'description' => 'المديونية الأولية المحدثة - التحديث المجمع في ' . now()->format('Y-m-d H:i:s'),
                'created_by' => $this->data['user_id'],
            ]);

            activity()
                ->performedOn($newTransaction)
                ->causedBy(User::find($this->data['user_id']))
                ->withProperties([
                    'new_amount' => $newAmount,
                    'customer_id' => $customer->id,
                    'action' => 'create_new_debt',
                    'replaced_debits_count' => $existingDebits->count(),
                    'bulk_update_job_id' => $this->data['job_id'] ?? null
                ])
                ->log('تم إنشاء مديونية أولية جديدة عبر التحديث المجمع');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update Progress
    |--------------------------------------------------------------------------
    */
    private function getMatchKeyMapping(): string
    {
        return $this->data['match_key'] === 'account_number' ? 'رقم الحساب' : 'رقم الهوية';
    }

    /*
    |--------------------------------------------------------------------------
    | Get Field Mapping
    |--------------------------------------------------------------------------
    */
    private function getFieldMapping(string $header): ?array
    {
        $fieldMappings = $this->data['field_mappings'] ?? [];

        foreach ($fieldMappings as $field => $mapping) {
            if (isset($mapping['column'])) {
                $mappingColumn = trim($mapping['column']);
                $headerTrimmed = trim($header);

                if ($mappingColumn === $headerTrimmed) {

                    return [
                        'field' => $field,
                        'type' => strpos($field, 'dynamic_') === 0 ? 'dynamic' : 'base'
                    ];
                }
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Field Value
    |--------------------------------------------------------------------------
    */
    private function validateFieldValue(string $fieldKey, $value, Customer $customer): array
    {
        $value = trim($value);

        switch ($fieldKey) {
            case 'debt_amount':
                $originalValue = $value;

                if (empty($originalValue) && $originalValue !== '0' && $originalValue !== 0) {
                    return ['valid' => false, 'message' => 'مبلغ المديونية فارغ'];
                }

                $stringValue = (string) $originalValue;
                $cleanValue = str_replace(',', '', $stringValue);
                $cleanValue = preg_replace('/[^\d\.\-]/', '', $cleanValue);

                if (!is_numeric($cleanValue)) {
                    return ['valid' => false, 'message' => 'مبلغ المديونية غير صحيح: ' . $originalValue];
                }

                $numericValue = (float) $cleanValue;

                if ($numericValue < 0) {
                    return ['valid' => false, 'message' => 'مبلغ المديونية لا يمكن أن يكون سالباً: ' . $originalValue];
                }

                if ($numericValue > 999999999) {
                    return ['valid' => false, 'message' => 'مبلغ المديونية كبير جداً: ' . $originalValue];
                }

                return ['valid' => true, 'value' => $numericValue];

            case 'employee_number':
                $user = User::where('employee_number', strtoupper(trim($value)))->first();
                if (!$user) {
                    return ['valid' => false, 'message' => 'الرقم الوظيفي غير موجود: ' . $value];
                }
                return ['valid' => true, 'value' => $user->id];

            case 'customer_type':
                $typeMapping = $this->data['field_mappings'][$fieldKey]['type_mapping'] ?? [];
                $normalizedValue = trim(strtolower($value));

                if (!empty($typeMapping) && isset($typeMapping[$normalizedValue])) {
                    return ['valid' => true, 'value' => $typeMapping[$normalizedValue]];
                }

                $defaultMapping = [
                    'فردي' => 'individual',
                    'individual' => 'individual',
                    'فرد' => 'individual',
                    'شخص' => 'individual',
                    'شركة' => 'company',
                    'company' => 'company',
                    'مؤسسة' => 'company',
                    'شركة/مؤسسة' => 'company'
                ];

                if (isset($defaultMapping[$normalizedValue])) {
                    return ['valid' => true, 'value' => $defaultMapping[$normalizedValue]];
                }

                return ['valid' => false, 'message' => 'نوع العميل غير صحيح: ' . $value];

            case 'phone':
                $cleanPhone = preg_replace('/[^\d+]/', '', $value);
                if (strlen($cleanPhone) < 7 || strlen($cleanPhone) > 20) {
                    return ['valid' => false, 'message' => 'تنسيق رقم الهاتف غير صحيح'];
                }
                return ['valid' => true, 'value' => $value];

            case 'identity_number':
            case 'account_number':
                if (strlen($value) < 3) {
                    return ['valid' => false, 'message' => 'القيمة قصيرة جداً'];
                }
                return ['valid' => true, 'value' => $value];

            case 'name':
            case 'first_name':
            case 'father_name':
            case 'grandfather_name':
            case 'family_name':
                if (strlen($value) < 2) {
                    return ['valid' => false, 'message' => 'الاسم يجب أن يكون على الأقل حرفين'];
                }
                return ['valid' => true, 'value' => $value];

            default:
                return ['valid' => true, 'value' => $value];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Current Field Value
    |--------------------------------------------------------------------------
    */
    private function getCurrentFieldValue(Customer $customer, string $fieldKey): string
    {
        if (strpos($fieldKey, 'dynamic_') === 0) {
            $fieldId = str_replace('dynamic_', '', $fieldKey);
            $value = $customer->dynamicValues()->where('portfolio_field_id', $fieldId)->first();
            return $value ? (string) $value->value : '';
        }

        if ($fieldKey === 'employee_number') {
            return $customer->relationship_manager_id ? (string) $customer->relationship_manager_id : '';
        }

        $fieldValue = $customer->getAttribute($fieldKey);
        return $fieldValue !== null ? (string) $fieldValue : '';
    }

    /*
    |--------------------------------------------------------------------------
    | Update Progress
    |--------------------------------------------------------------------------
    */
    private function updateProgress(
        string $jobId,
        string $status,
        int $processed,
        int $total,
        int $updated,
        int $failed,
        ?string $error = null,
        ?string $errorReport = null
    ): void {
        $progress = [
            'requested_by' => $this->data['user_id'],
            'status' => $status,
            'processed' => $processed,
            'total' => $total,
            'updated' => $updated,
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

        Cache::put("bulk_update_progress_{$jobId}", $progress, 7200);
    }

    /*
    |--------------------------------------------------------------------------
    | Failed Job Handler
    |--------------------------------------------------------------------------
    */
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
