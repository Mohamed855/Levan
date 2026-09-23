<?php

namespace App\Jobs\Tenants\BusinessCenter\Customer;

use App\Models\Tenants\BusinessCenter\Customers\ImportSession\ImportSession;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Phone\CustomerPhone;
use App\Models\Tenants\BusinessCenter\Customers\FieldValue\CustomerFieldValue;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\BusinessCenter\Portfolio\PortfolioField;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\BusinessCenter\Customer\Import\CustomerFieldMappingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ___________ProcessCustomerImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $sessionId;
    protected array $chunk;
    protected array $headers;
    protected array $fieldMapping;
    protected int $chunkIndex;
    protected int $totalChunks;
    protected int $userId;
    protected ?Portfolio $portfolio = null;

    /*
    |--------------------------------------------------------------------------
    | Job Configuration
    |--------------------------------------------------------------------------
    */
    public int $tries = 3;
    public int $timeout = 300;
    public $backoff = [30, 60, 120];
    public $deleteWhenMissingModels = true;
    protected int $portfolioId;

    public function __construct(
        string $sessionId,
        array $chunk,
        array $headers,
        array $fieldMapping,
        int $portfolioId, // 🟢
        int $chunkIndex,
        int $totalChunks,
        int $userId
    ) {
        $this->sessionId = $sessionId;
        $this->chunk = $chunk;
        $this->headers = $headers;
        $this->fieldMapping = $fieldMapping;
        $this->portfolioId = $portfolioId;
        $this->chunkIndex = $chunkIndex;
        $this->totalChunks = $totalChunks;
        $this->userId = $userId;
    }

    /*
    |--------------------------------------------------------------------------
    | Handle the Job
    |--------------------------------------------------------------------------
    */
    public function handle()
    {
        $session = ImportSession::where('session_id', $this->sessionId)->first();
        $this->portfolio = Portfolio::find($this->portfolioId);
        if (!$this->portfolio) {
            // Log::error("❌ Portfolio not found", [
            //     'portfolio_id' => $this->portfolioId,
            //     'session_id' => $this->sessionId
            // ]);
            return;
        }

        $portfolio = Portfolio::find($this->portfolioId);


        if (!$session || in_array($session->status, ['cancelled', 'failed'])) {
            // Log::info("🚫 Import job skipped", [
            //     'session_id' => $this->sessionId,
            //     'status' => $session->status ?? 'not found'
            // ]);
            return;
        }

        // Log::info("🚀 Starting chunk processing", [
        //     'session_id' => $this->sessionId,
        //     'chunk_index' => $this->chunkIndex,
        //     'total_chunks' => $this->totalChunks,
        //     'rows_in_chunk' => count($this->chunk)
        // ]);

        DB::beginTransaction();
        try {
            $chunkErrors = [];
            $successfulInChunk = 0;
            $failedInChunk = 0;

            foreach ($this->chunk as $rowIndex => $row) {
                if ($rowIndex % 50 === 0) {
                    $session->refresh();
                    if ($session->status === 'cancelled') {
                        // Log::info("❌ Import cancelled during processing", [
                        //     'session_id' => $this->sessionId,
                        //     'at_row' => $rowIndex
                        // ]);
                        DB::rollBack();
                        return;
                    }
                }

                $globalRowNumber = ($this->chunkIndex * config('import.chunk_size', 200)) + $rowIndex + 2;

                try {
                    $result = $this->processRow($row, $globalRowNumber);

                    if ($result['success']) {
                        $successfulInChunk++;
                    } else {
                        $failedInChunk++;
                        $chunkErrors[] = [
                            'row' => $globalRowNumber,
                            'errors' => $result['errors']
                        ];
                    }
                } catch (\Exception $e) {
                    $failedInChunk++;
                    $chunkErrors[] = [
                        'row' => $globalRowNumber,
                        'errors' => ['خطأ عام: ' . $e->getMessage()]
                    ];

                    // Log::error("❌ Row processing failed", [
                    //     'session_id' => $this->sessionId,
                    //     'row_number' => $globalRowNumber,
                    //     'error' => $e->getMessage(),
                    //     'trace' => $e->getTraceAsString()
                    // ]);
                }
            }

            $session->increment('processed_rows', count($this->chunk));
            $session->increment('successful_rows', $successfulInChunk);
            $session->increment('failed_rows', $failedInChunk);

            if (!empty($chunkErrors)) {
                $currentErrors = json_decode($session->errors, true) ?? [];
                $updatedErrors = array_merge($currentErrors, $chunkErrors);
                $session->errors = json_encode($updatedErrors);
            }

            $session->save();

            DB::commit();

            // Log::info("✅ Chunk processed successfully", [
            //     'session_id' => $this->sessionId,
            //     'chunk_index' => $this->chunkIndex,
            //     'successful' => $successfulInChunk,
            //     'failed' => $failedInChunk
            // ]);

            if ($this->chunkIndex + 1 === $this->totalChunks) {
                $session->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);

                // Log::info("🎉 Import session completed", [
                //     'session_id' => $this->sessionId,
                //     'total_successful' => $session->successful_rows,
                //     'total_failed' => $session->failed_rows
                // ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            // Log::error("❌ Chunk processing failed", [
            //     'session_id' => $this->sessionId,
            //     'chunk_index' => $this->chunkIndex,
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);

            $session->update([
                'status' => 'failed',
                'completed_at' => now()
            ]);

            $this->fail($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Process a Single Row
    |--------------------------------------------------------------------------
    */
    private function processRow(array $row, int $rowNumber): array
    {
        // Log::info("🔄 ========== Starting row processing ==========", [
        //     'session_id' => $this->sessionId,
        //     'row_number' => $rowNumber,
        //     'row_data_count' => count($row)
        // ]);

        $row = array_values($row);


        $fieldMappingService = app(CustomerFieldMappingService::class);

        $customerData = [
            'company_id' => $this->portfolio->company_id,
            'portfolio_id' => $this->portfolio->id,
            'created_by' => $this->userId,
        ];

        $supportsSplitNames = (bool) ($this->portfolio->has_split_names ?? false);
        $splitKeys = ['first_name', 'father_name', 'grandfather_name', 'family_name'];

        $nameParts = [];
        $phoneData = [];
        $dynamicFields = [];
        $errors = [];
        $mainPhoneSet = false;
        $initialDebt = 0;

        // Log::info("📋 Field mapping info", [
        //     'row_number' => $rowNumber,
        //     'total_mappings' => count($this->fieldMapping),
        //     'mappings_preview' => array_slice($this->fieldMapping, 0, 3, true)
        // ]);

        // ✅ معالجة كل عمود
        foreach ($this->fieldMapping as $index => $mapping) {
            $value = $row[$index] ?? '';
            $value = is_string($value) ? trim($value) : $value;


            $fieldNameForSecurity = $this->getMappingFieldName($mapping);
            $mappingTypeForSecurity = $mapping['type'] ?? null;

            $securityResult = $this->validateImportedValueSecurity(
                $fieldNameForSecurity,
                $value,
                $mappingTypeForSecurity,
                $rowNumber,
                $index
            );

            if (!$securityResult['valid']) {
                $errors = array_merge($errors, $securityResult['errors']);
                continue;
            }

            $value = $securityResult['value'];

            // Log::debug("🔍 Processing column", [
            //     'row_number' => $rowNumber,
            //     'column_index' => $index,
            //     'mapping_type' => $mapping['type'],
            //     'mapping_field' => is_object($mapping['field'] ?? null)
            //         ? get_class($mapping['field'])
            //         : ($mapping['field'] ?? 'unknown'),
            //     'value_empty' => empty($value),
            //     'value_preview' => is_string($value) ? mb_substr($value, 0, 30) : gettype($value)
            // ]);

            if (($mapping['type'] ?? null) === 'pending_dynamic') {
                // Log::warning('Pending dynamic field mapping reached job unexpectedly - skipping', [
                //     'row' => $rowNumber,
                //     'column_index' => $index,
                //     'display_name' => $mapping['display_name'] ?? null,
                // ]);
                continue;
            }


            // ✅ تخطي القيم الفارغة فقط للحقول غير الديناميكية
            if ($value === '' && $mapping['type'] !== 'dynamic') {
                // Log::debug("⏭️ Skipping empty non-dynamic field", [
                //     'column_index' => $index,
                //     'mapping_type' => $mapping['type']
                // ]);
                continue;
            }

            // ✅ معالجة الحقول الثابتة
            if ($mapping['type'] === 'static') {
                $field = $mapping['field'];

                // Log::debug("📌 Processing static field", [
                //     'row_number' => $rowNumber,
                //     'field_name' => $field,
                //     'value' => $value
                // ]);

                if ($field === 'name' || in_array($field, $splitKeys, true)) {
                    if ($field !== 'name' && $supportsSplitNames) {
                        $customerData[$field] = $value;
                    }
                    $nameParts[] = $value;
                    // Log::debug("👤 Name field added", ['field' => $field, 'value' => $value]);
                    continue;
                }

                switch ($field) {
                    case 'customer_type':
                        $customerData[$field] = $fieldMappingService->normalizeCustomerType($value);
                        // Log::debug("🏢 Customer type", ['original' => $value, 'normalized' => $customerData[$field]]);
                        break;

                    case 'identity_number':
                        $customerData[$field] = $fieldMappingService->cleanIdentityNumber($value);
                        // Log::debug("🆔 Identity number", ['original' => $value, 'cleaned' => $customerData[$field]]);
                        break;

                    case 'phone':
                        if (!$mainPhoneSet) {
                            $customerData['phone'] = $fieldMappingService->cleanPhoneNumber($value);
                            $mainPhoneSet = true;
                            // Log::debug("📱 Main phone set", ['phone' => $customerData['phone']]);
                        } else {
                            $nextPhoneIndex = count($phoneData) + 2;
                            $phoneData[$nextPhoneIndex] = [
                                'phone' => $fieldMappingService->cleanPhoneNumber($value),
                                'type' => 'mobile'
                            ];
                            // Log::debug("📱 Additional phone", ['index' => $nextPhoneIndex, 'phone' => $phoneData[$nextPhoneIndex]['phone']]);
                        }
                        break;

                    case 'initial_debt':
                        $cleanValue = preg_replace('/[^\d.-]/', '', $value);
                        $initialDebt = $cleanValue ? (float) $cleanValue : 0;
                        // Log::debug("💰 Initial debt", ['original' => $value, 'cleaned' => $cleanValue, 'debt' => $initialDebt]);
                        break;

                    case 'relationship_manager_id':
                        if (isset($mapping['source_field']) && $mapping['source_field'] === 'employee_number') {
                            $cleanEmployeeNumber = strtoupper(trim($value));
                            $relManagerId = $this->getUserIdByEmployeeNumber($cleanEmployeeNumber);

                            if ($relManagerId) {
                                $customerData['relationship_manager_id'] = $relManagerId;
                                // Log::info("👤 Manager set via employee number", [
                                //     'employee_number' => $cleanEmployeeNumber,
                                //     'manager_id' => $relManagerId
                                // ]);
                            } else {
                                $errors[] = "المستخدم برقم وظيفي '{$cleanEmployeeNumber}' غير موجود";
                                // Log::warning("⚠️ Employee not found", ['employee_number' => $cleanEmployeeNumber]);
                            }
                        } else {
                            if (is_numeric($value)) {
                                $customerData['relationship_manager_id'] = (int) $value;
                                // Log::debug("👤 Manager set via ID", ['manager_id' => $customerData['relationship_manager_id']]);
                            }
                        }
                        break;

                    case 'nationality':
                    case 'customer_status_id':
                        if (is_numeric($value)) {
                            $customerData[$field] = (int) $value;
                            // Log::debug("🔢 Numeric field", ['field' => $field, 'value' => $customerData[$field]]);
                        }
                        break;

                    default:
                        $customerData[$field] = $value;
                        // Log::debug("📝 Field set", ['field' => $field, 'value' => $value]);
                        break;
                }
            }
            // ✅ معالجة الهواتف الإضافية
            elseif ($mapping['type'] === 'phone') {
                $this->processPhoneField($mapping['field'], $value, $phoneData);
                // Log::debug("📱 Phone field processed", ['info' => $mapping['field'], 'value' => $value]);
            }
            // ✅ معالجة الحقول الديناميكية - الجزء الأهم
            elseif ($mapping['type'] === 'dynamic') {
                // ✅ إعادة تحميل الكائن الحقيقي إذا أتى كمصفوفة
                if (is_array($mapping['field']) && isset($mapping['field']['id'])) {
                    $fieldObject = PortfolioField::find($mapping['field']['id']);
                } else {
                    $fieldObject = $mapping['field'];
                }

                // Log::info("🔎 Dynamic field detected", [
                //     'row_number' => $rowNumber,
                //     'column_index' => $index,
                //     'field_object_type' => is_object($fieldObject) ? get_class($fieldObject) : gettype($fieldObject),
                //     'field_id' => is_object($fieldObject) && isset($fieldObject->id) ? $fieldObject->id : 'N/A',
                //     'field_name' => is_object($fieldObject) && isset($fieldObject->field_name) ? $fieldObject->field_name : 'N/A',
                //     'field_type' => is_object($fieldObject) && isset($fieldObject->field_type) ? $fieldObject->field_type : 'N/A',
                //     'value' => $value
                // ]);

                if (!$fieldObject || !is_object($fieldObject) || !isset($fieldObject->id)) {
                    // Log::error("❌ INVALID dynamic field object", [
                    //     'row_number' => $rowNumber,
                    //     'column_index' => $index,
                    //     'mapping' => $mapping,
                    //     'field_object' => $fieldObject
                    // ]);
                    continue;
                }

                $processedValue = $value;

                // ✅ معالجة التواريخ
                if ($fieldObject->field_type === 'date' && !empty($value)) {
                    $processedValue = $this->processDateValue($value, $fieldObject->field_name, $rowNumber);
                    // Log::info("📅 Date field processed", [
                    //     'field_name' => $fieldObject->field_name,
                    //     'original' => $value,
                    //     'processed' => $processedValue
                    // ]);
                }

                // ✅ حفظ القيمة - حتى لو فارغة
                $dynamicFields[$fieldObject->id] = $processedValue;

                // Log::info("✅ Dynamic field ADDED to array", [
                //     'field_id' => $fieldObject->id,
                //     'field_name' => $fieldObject->field_name,
                //     'field_type' => $fieldObject->field_type,
                //     'value' => $processedValue,
                //     'value_empty' => empty($processedValue),
                //     'total_dynamic_fields_so_far' => count($dynamicFields)
                // ]);
            } else {
                // Log::warning("⚠️ Unknown mapping type", [
                //     'row_number' => $rowNumber,
                //     'column_index' => $index,
                //     'mapping_type' => $mapping['type'] ?? 'undefined'
                // ]);
            }
        }



        // Log::info("📊 After field mapping loop", [
        //     'row_number' => $rowNumber,
        //     'total_dynamic_fields' => count($dynamicFields),
        //     'dynamic_fields_ids' => array_keys($dynamicFields),
        //     'dynamic_fields_preview' => array_slice($dynamicFields, 0, 5, true)
        // ]);

        // ✅ إنشاء الاسم الكامل
        if (!isset($customerData['name']) && !empty($nameParts)) {
            $customerData['name'] = implode(' ', array_filter($nameParts));
            // Log::info("👤 Full name created", ['name' => $customerData['name']]);
        }

        $customerData['customer_type'] = $customerData['customer_type'] ?? 'individual';

        // ✅ التحقق
        if (empty($customerData['name'])) {
            $errors[] = 'اسم العميل مطلوب';
        }
        if (empty($customerData['identity_number'])) {
            $errors[] = 'رقم الهوية مطلوب';
        }

        if (!empty($customerData['account_number'])) {
            $exists = Customer::where('account_number', $customerData['account_number'])->exists();
            if ($exists) {
                $errors[] = "عميل برقم الحساب {$customerData['account_number']} موجود مسبقاً";
                // Log::warning("⚠️ Duplicate account", ['account' => $customerData['account_number']]);
            }
        }

        if ($errors) {
            // Log::warning("⚠️ Validation failed", ['row' => $rowNumber, 'errors' => $errors]);
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $customer = Customer::create($customerData);
            // Log::info("✅ Customer created", [
            //     'customer_id' => $customer->id,
            //     'account' => $customer->account_number,
            //     'name' => $customer->name
            // ]);

            // ✅ حفظ الهواتف
            foreach ($phoneData as $pData) {
                if (!empty($pData['phone'])) {
                    $phone = CustomerPhone::create([
                        'customer_id' => $customer->id,
                        'phone' => $pData['phone'],
                        'type' => $pData['type'] ?? 'mobile',
                        'is_primary' => false,
                        'notes' => $pData['notes'] ?? null,
                    ]);
                    // Log::debug("📱 Phone saved", ['phone_id' => $phone->id, 'phone' => $pData['phone']]);
                }
            }
            // 👇 تأكيد الدخول إلى مرحلة حفظ الحقول الديناميكية
            if (empty($dynamicFields)) {
                // Log::error("🚫 SKIPPED dynamic field save section - dynamicFields array is EMPTY", [
                //     'customer_id' => $customer->id,
                //     'row_number' => $rowNumber,
                //     'headers_count' => count($this->headers ?? []),
                //     'field_mapping_count' => count($this->fieldMapping ?? []),
                //     'note' => 'لم يتم إضافة أي قيم ديناميكية لهذا الصف'
                // ]);
            } else {
                // Log::info("✅ ENTERED dynamic field save section", [
                //     'customer_id' => $customer->id,
                //     'row_number' => $rowNumber,
                //     'fields_to_save' => count($dynamicFields),
                //     'field_ids' => array_keys($dynamicFields)
                // ]);
            }


            // ✅ حفظ الحقول الديناميكية - مع log شامل
            // Log::info("💾 ========== Saving dynamic fields ==========", [
            //     'customer_id' => $customer->id,
            //     'fields_count' => count($dynamicFields),
            //     'field_ids' => array_keys($dynamicFields)
            // ]);

            $savedCount = 0;
            $skippedCount = 0;

            foreach ($dynamicFields as $fieldId => $val) {
                // Log::info("🔄 Processing dynamic field for save", [
                //     'customer_id' => $customer->id,
                //     'field_id' => $fieldId,
                //     'value' => $val,
                //     'value_type' => gettype($val),
                //     'value_empty' => empty($val),
                //     'value_is_null' => is_null($val),
                //     'value_is_empty_string' => $val === ''
                // ]);

                // ✅ حفظ حتى القيم الفارغة (لكن ليس NULL)
                if ($val !== null) {
                    try {
                        $fieldValue = CustomerFieldValue::create([
                            'customer_id' => $customer->id,
                            'portfolio_field_id' => $fieldId,
                            'value' => $val,
                        ]);

                        $savedCount++;


                        // Log::info("✅ Dynamic field SAVED successfully", [
                        //     'customer_id' => $customer->id,
                        //     'field_id' => $fieldId,
                        //     'field_value_id' => $fieldValue->id,
                        //     'value' => $val
                        // ]);
                    } catch (\Exception $e) {
                        // Log::error("❌ Failed to save dynamic field", [
                        //     'customer_id' => $customer->id,
                        //     'field_id' => $fieldId,
                        //     'value' => $val,
                        //     'error' => $e->getMessage(),
                        //     'trace' => $e->getTraceAsString()
                        // ]);
                    }
                } else {
                    $skippedCount++;
                    // Log::debug("⏭️ Skipped NULL dynamic field", [
                    //     'customer_id' => $customer->id,
                    //     'field_id' => $fieldId
                    // ]);
                }
            }

            // Log::info("📊 Dynamic fields save summary", [
            //     'customer_id' => $customer->id,
            //     'total_fields' => count($dynamicFields),
            //     'saved' => $savedCount,
            //     'skipped' => $skippedCount
            // ]);

            // ✅ حفظ المديونية
            if ($initialDebt > 0) {
                $transaction = CustomerTransaction::create([
                    'customer_id' => $customer->id,
                    'type' => 'debit',
                    'amount' => $initialDebt,
                    'transaction_date' => now()->toDateString(),
                    'description' => 'المديونية الأولية عند استيراد العميل من ملف Excel',
                    'created_by' => $this->userId,
                    'source' => 'import',
                    'reference_number' => 'IMP-' . now()->format('YmdHis') . '-' . $customer->id,
                ]);
                // Log::info("💰 Debt transaction created", [
                //     'customer_id' => $customer->id,
                //     'transaction_id' => $transaction->id,
                //     'amount' => $initialDebt
                // ]);
            }

            // Log::info("🎉 ========== Row processed successfully ==========", [
            //     'row_number' => $rowNumber,
            //     'customer_id' => $customer->id,
            //     'dynamic_fields_saved' => $savedCount,
            //     'phones_saved' => count($phoneData),
            //     'debt_created' => $initialDebt > 0
            // ]);

            return [
                'success' => true,
                'customer' => $customer,
                'initial_debt' => $initialDebt,
            ];
        } catch (\Exception $e) {
            // Log::error("❌ Customer creation failed", [
            //     'row_number' => $rowNumber,
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString(),
            //     'customer_data' => $customerData
            // ]);

            return [
                'success' => false,
                'errors' => ['خطأ في إنشاء العميل: ' . $e->getMessage()]
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Process Date Value
    |--------------------------------------------------------------------------
    */
    private function processDateValue(string $value, string $fieldName, int $rowNumber): string
    {
        $originalValue = $value;

        if (is_numeric($value)) {
            try {
                $numericValue = (float) $value;

                if (strlen($value) === 8 && $numericValue > 19000101 && $numericValue < 30001231) {
                    $date = \DateTime::createFromFormat('Ymd', $value);
                    if ($date && $date !== false) {
                        // Log::debug("📅 Date parsed (YYYYMMDD)", [
                        //     'field' => $fieldName,
                        //     'original' => $originalValue,
                        //     'parsed' => $date->format('Y-m-d')
                        // ]);
                        return $date->format('Y-m-d');
                    }
                }

                if ($numericValue > 1 && $numericValue < 100000) {
                    $unix_timestamp = ($numericValue - 25569) * 86400;
                    $date = new \DateTime("@$unix_timestamp");
                    if ($date) {
                        // Log::debug("📅 Date parsed (Excel Serial)", [
                        //     'field' => $fieldName,
                        //     'original' => $originalValue,
                        //     'serial' => $numericValue,
                        //     'parsed' => $date->format('Y-m-d')
                        // ]);
                        return $date->format('Y-m-d');
                    }
                }
            } catch (\Exception $e) {
                // Log::warning("⚠️ Failed to parse numeric date", [
                //     'field' => $fieldName,
                //     'value' => $originalValue,
                //     'error' => $e->getMessage()
                // ]);
            }
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'm/d/Y', 'Y.m.d', 'd.m.Y'];
        foreach ($formats as $format) {
            try {
                $date = \DateTime::createFromFormat($format, trim($value));
                if ($date && $date->format($format) === trim($value)) {
                    // Log::debug("📅 Date parsed (Text)", [
                    //     'field' => $fieldName,
                    //     'original' => $originalValue,
                    //     'format' => $format,
                    //     'parsed' => $date->format('Y-m-d')
                    // ]);
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Log::warning("⚠️ Date format not recognized", [
        //     'field' => $fieldName,
        //     'value' => $originalValue,
        //     'row' => $rowNumber
        // ]);

        return $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Process Phone Field
    |--------------------------------------------------------------------------
    */
    private function processPhoneField(array $fieldInfo, string $value, array &$phoneData): void
    {
        $phoneIndex = $fieldInfo['index'];
        $fieldType = $fieldInfo['type'];

        if (!isset($phoneData[$phoneIndex])) {
            $phoneData[$phoneIndex] = [];
        }

        if ($fieldType === 'phone') {
            $phoneData[$phoneIndex]['phone'] = app(CustomerFieldMappingService::class)->cleanPhoneNumber($value);
        } else {
            $phoneData[$phoneIndex][$fieldType] = $value;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get User ID by Employee Number
    |--------------------------------------------------------------------------
    */
    private function getUserIdByEmployeeNumber(string $employeeNumber): ?int
    {
        if (empty(trim($employeeNumber))) {
            return null;
        }

        $cleanEmployeeNumber = strtoupper(trim($employeeNumber));

        try {
            $user = User::where('employee_number', $cleanEmployeeNumber)
                ->where('status', 'active')
                ->first();

            if (!$user) {
                $user = User::where('employee_number', 'like', "%{$cleanEmployeeNumber}%")
                    ->where('status', 'active')
                    ->first();
            }

            return $user ? $user->id : null;
        } catch (\Exception $e) {
            // Log::error('❌ Error finding user by employee number', [
            //     'employee_number' => $cleanEmployeeNumber,
            //     'error' => $e->getMessage()
            // ]);
            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Handle Job Failure
    |--------------------------------------------------------------------------
    */
    public function failed(\Throwable $exception)
    {
        // Log::error("❌ Import job failed completely", [
        //     'session_id' => $this->sessionId,
        //     'chunk_index' => $this->chunkIndex,
        //     'error' => $exception->getMessage(),
        //     'trace' => $exception->getTraceAsString()
        // ]);

        $session = ImportSession::where('session_id', $this->sessionId)->first();
        if ($session && $session->status !== 'cancelled') {
            $session->update([
                'status' => 'failed',
                'completed_at' => now()
            ]);
        }
    }





    /*
    |============================================================================
    |============================================================================
    |                               Functions
    |============================================================================
    |============================================================================
    */
    private function getMappingFieldName(array $mapping): string
    {
        $field = $mapping['field'] ?? 'unknown';

        if (is_string($field)) {
            return $field;
        }

        if (is_array($field)) {
            return $field['field_name']
                ?? $field['name']
                ?? $field['type']
                ?? 'dynamic_field';
        }

        if (is_object($field)) {
            return $field->field_name
                ?? $field->name
                ?? 'dynamic_field';
        }

        return 'unknown';
    }

    private function validateImportedValueSecurity(
        string $fieldName,
        $value,
        ?string $mappingType,
        int $rowNumber,
        $columnIndex
    ): array {
        if ($value === null) {
            return [
                'valid' => true,
                'value' => null,
            ];
        }

        if ($value instanceof \DateTimeInterface) {
            return [
                'valid' => true,
                'value' => $value,
            ];
        }

        if (is_bool($value) || is_numeric($value)) {
            return [
                'valid' => true,
                'value' => $value,
            ];
        }

        $value = trim((string) $value);

        if ($value === '') {
            return [
                'valid' => true,
                'value' => '',
            ];
        }

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
        $value = trim($value);

        $errors = [];

        if ($value !== strip_tags($value)) {
            $errors[] = "الصف {$rowNumber}، العمود " . ($columnIndex + 1) . " يحتوي على وسوم HTML غير مسموحة";
        }

        if ($this->containsDangerousImportedContent($value)) {
            $errors[] = "الصف {$rowNumber}، العمود " . ($columnIndex + 1) . " يحتوي على محتوى غير آمن";
        }

        if ($this->startsWithUnsafeFormulaCharacter($value, $fieldName, $mappingType)) {
            $errors[] = "الصف {$rowNumber}، العمود " . ($columnIndex + 1) . " يحتوي على قيمة تبدأ برمز غير مسموح";
        }

        if ($this->isNameField($fieldName) && !$this->isValidImportedName($value)) {
            $errors[] = "الصف {$rowNumber}، العمود " . ($columnIndex + 1) . " يحتوي على اسم غير صالح";
        }

        if (!empty($errors)) {
            return [
                'valid' => false,
                'errors' => $errors,
            ];
        }

        return [
            'valid' => true,
            'value' => $value,
        ];
    }

    private function containsDangerousImportedContent(string $value): bool
    {
        $patterns = [
            '/javascript\s*:/iu',
            '/vbscript\s*:/iu',
            '/data\s*:/iu',
            '/on\w+\s*=/iu',
            '/<\s*script/iu',
            '/<\/\s*script/iu',
            '/<\s*iframe/iu',
            '/<\s*object/iu',
            '/<\s*embed/iu',
            '/<\s*svg/iu',
            '/<\s*img/iu',
            '/<\s*link/iu',
            '/<\s*meta/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    private function startsWithUnsafeFormulaCharacter(string $value, string $fieldName, ?string $mappingType): bool
    {
        $trimmedValue = ltrim($value);

        if (preg_match('/^[=@]/u', $trimmedValue)) {
            return true;
        }

        if (preg_match('/^\+/u', $trimmedValue)) {
            return !$this->isPhoneLikeField($fieldName, $mappingType);
        }

        if (preg_match('/^-/u', $trimmedValue)) {
            return !preg_match('/^-\d+(\.\d+)?$/u', $trimmedValue);
        }

        return false;
    }

    private function isPhoneLikeField(string $fieldName, ?string $mappingType): bool
    {
        $normalized = strtolower(trim($fieldName));

        return $mappingType === 'phone'
            || str_contains($normalized, 'phone')
            || str_contains($normalized, 'mobile')
            || str_contains($normalized, 'جوال')
            || str_contains($normalized, 'هاتف')
            || str_contains($normalized, 'رقم');
    }

    private function isNameField(string $fieldName): bool
    {
        return in_array($fieldName, [
            'name',
            'first_name',
            'father_name',
            'grandfather_name',
            'family_name',
        ], true);
    }

    private function isValidImportedName(string $value): bool
    {
        return preg_match('/^[\p{Arabic}\p{L}0-9\s\.\-_&،\/]+$/u', $value) === 1;
    }
}
