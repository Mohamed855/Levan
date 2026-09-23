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

class ProcessCustomerImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries               = 1;
    public int $timeout             = 300; // 5 دقائق
    // public $backoff                 = [30, 60, 120];
    public $deleteWhenMissingModels = true;

    protected ?Portfolio $portfolio = null;

    public function __construct(protected string $sessionId, protected array $chunk, protected array $headers, protected array $fieldMapping, protected int $portfolioId, protected int $chunkIndex, protected int $totalChunks, protected int $userId) {}

    public function handle()
    {
        $session = ImportSession::where('session_id', $this->sessionId)->first();

        if (!$session) {
            throw new \Exception('جلسة الاستيراد غير موجودة');
        }

        if (in_array($session->status, ['cancelled', 'failed'])) {
            return;
        }

        $this->portfolio = Portfolio::find($this->portfolioId);
        if (!$this->portfolio) {
            throw new \Exception('المحفظة غير موجودة');
        }


        DB::beginTransaction();
        try {
            $chunkErrors         = [];
            $successfulInChunk   = 0;
            $failedInChunk       = 0;

            foreach ($this->chunk as $rowIndex => $row) {
                if ($rowIndex % 50 === 0) {
                    $session->refresh();
                    if ($session->status === 'cancelled') {

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


            if ($this->chunkIndex + 1 === $this->totalChunks) {
                $session->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            $session->update([
                'status' => 'failed',
                'completed_at' => now()
            ]);

            $this->fail($e);
        }
    }

    // Process a Single Row
    private function processRow(array $row, int $rowNumber): array
    {
        $row = array_values($row);
        $fieldMappingService = app(CustomerFieldMappingService::class);

        $customerData = [
            'company_id'    => $this->portfolio->company_id,
            'portfolio_id'  => $this->portfolio->id,
            'created_by'    => $this->userId,
        ];

        $supportsSplitNames = (bool) ($this->portfolio->has_split_names ?? false);
        $splitKeys = ['first_name', 'father_name', 'grandfather_name', 'family_name'];

        $nameParts      = [];
        $phoneData      = [];
        $dynamicFields  = [];
        $errors         = [];
        $mainPhoneSet   = false;
        $initialDebt    = 0;
        $debtFormatError  = false;

        // معالجة كل عمود
        foreach ($this->fieldMapping as $index => $mapping) {
            $value = $row[$index] ?? '';
            $value = is_string($value) ? trim($value) : $value;

            if (($mapping['type'] ?? null) === 'pending_dynamic') {
                continue;
            }

            if ($value === '' && $mapping['type'] !== 'dynamic') {
                continue;
            }

            // معالجة الحقول الثابتة
            if ($mapping['type'] === 'static') {
                $field = $mapping['field'];

                if ($field === 'name' || in_array($field, $splitKeys, true)) {
                    if ($field !== 'name' && $supportsSplitNames) {
                        $customerData[$field] = $value;
                    }
                    $nameParts[] = $value;
                    continue;
                }

                switch ($field) {
                    case 'customer_type':
                        $customerData[$field] = $fieldMappingService->normalizeCustomerType($value);
                        break;

                    case 'identity_number':
                        $customerData[$field] = $fieldMappingService->cleanIdentityNumber($value);
                        break;

                    case 'phone':
                        if (!$mainPhoneSet) {
                            $customerData['phone'] = $fieldMappingService->cleanPhoneNumber($value);
                            $mainPhoneSet = true;
                        } else {
                            $nextPhoneIndex = count($phoneData) + 2;
                            $phoneData[$nextPhoneIndex] = [
                                'phone' => $fieldMappingService->cleanPhoneNumber($value),
                                'type' => 'mobile'
                            ];
                        }
                        break;

                    case 'initial_debt':
                        if (!$fieldMappingService->isValidDebtAmount((string) $value)) {
                            $errors[] = "المديونية يجب أن تكون أرقاماً فقط، القيمة المدخلة: '{$value}'";
                            $debtFormatError = true;
                            break;
                        }
                        $initialDebt = $fieldMappingService->cleanDebtAmount((string) $value);
                        $customerData['initial_debt'] = $initialDebt;
                        break;

                    case 'relationship_manager_id':
                        if (!empty($value)) {
                            $userNumber = $value;
                            $managerId  = $fieldMappingService->getRelationshipManagerId($userNumber);

                            if ($managerId !== null) {
                                $customerData['relationship_manager_id'] = $managerId;
                            } else {
                                $errors[] = "المستخدم بالرقم الوظيفي '{$userNumber}' غير موجود أو غير نشط";
                            }
                        }
                        break;


                    case 'nationality':
                        if (!empty($value)) {
                            if (!is_numeric($value)) {
                                $errors[] = "الجنسية يجب أن تكون رقماً، القيمة المدخلة: '{$value}'";
                            } else {
                                $nationalityId = (int) $value;
                                if ($fieldMappingService->isValidNationality($nationalityId)) {
                                    $customerData['nationality'] = $nationalityId;
                                } else {
                                    $errors[] = "الجنسية برقم '{$nationalityId}' غير موجودة أو غير نشطة";
                                }
                            }
                        }
                        break;
                    case 'customer_status_id':
                        if (!empty($value)) {
                            if (!is_numeric($value)) {
                                $errors[] = "حالة العميل يجب أن تكون رقماً، القيمة المدخلة: '{$value}'";
                            } else {
                                $statusId = (int) $value;
                                if ($fieldMappingService->isValidCustomerStatus($statusId)) {
                                    $customerData['customer_status_id'] = $statusId;
                                } else {
                                    $errors[] = "حالة العميل برقم '{$statusId}' غير موجودة أو غير نشطة";
                                }
                            }
                        }
                        break;

                    default:
                        $customerData[$field] = $value;
                        break;
                }
            }
            // معالجة الهواتف الإضافية
            elseif ($mapping['type'] === 'phone') {
                $this->processPhoneField($mapping['field'], $value, $phoneData);
            }
            // معالجة الحقول الديناميكية
            elseif ($mapping['type'] === 'dynamic') {
                if (is_array($mapping['field']) && isset($mapping['field']['id'])) {
                    $fieldObject = PortfolioField::find($mapping['field']['id']);
                } else {
                    $fieldObject = $mapping['field'];
                }

                if (!$fieldObject || !is_object($fieldObject) || !isset($fieldObject->id)) {
                    continue;
                }

                $processedValue = $value;
                $dynamicFields[$fieldObject->id] = $processedValue;
            }
        }

        // إنشاء الاسم الكامل
        if (!isset($customerData['name']) && !empty($nameParts)) {
            $customerData['name'] = implode(' ', array_filter($nameParts));
        }

        $customerData['customer_type'] = $customerData['customer_type'] ?? 'individual';

        //  التحقق من جميع الحقول الإجبارية
        if ($supportsSplitNames) {
            // التحقق من الأسماء المنفصلة
            if (empty($customerData['first_name'])) {
                $errors[] = 'الاسم الأول مطلوب';
            }
            if (empty($customerData['family_name'])) {
                $errors[] = 'اسم العائلة مطلوب';
            }
        } else {
            // التحقق من اسم العميل الواحد
            if (empty($customerData['name'])) {
                $errors[] = 'اسم العميل مطلوب';
            }
        }



        // التحقق من رقم الهوية/الجواز
        if (empty($customerData['identity_number'])) {
            $errors[] = 'رقم الهوية/رقم الجواز مطلوب';
        } elseif (!$fieldMappingService->isValidIdentityNumber($customerData['identity_number'])) {
            $length = mb_strlen($customerData['identity_number'], 'UTF-8');
            $errors[] = "رقم الهوية/رقم الجواز غير صحيح. يجب أن يكون بين 5 و 15 حرف انجليزي/رقماً فقط (الطول الحالي: $length)";
        }

        if (empty($customerData['customer_type'])) {
            $errors[] = 'نوع العميل مطلوب';
        }

        // if (!isset($customerData['initial_debt']) || $customerData['initial_debt'] === '' || $customerData['initial_debt'] === null) {
        //     $errors[] = 'المديونية مطلوبة';
        // }

        if (!$debtFormatError && (!isset($customerData['initial_debt']) || $customerData['initial_debt'] === '' || $customerData['initial_debt'] === null)) {
            $errors[] = 'المديونية مطلوبة';
        }

        // التحقق من عدم تكرار رقم الحساب
        if (!empty($customerData['account_number'])) {
            $exists = Customer::where('account_number', $customerData['account_number'])->exists();
            if ($exists) {
                $errors[] = "عميل برقم الحساب {$customerData['account_number']} موجود مسبقاً";
            }
        }

        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $customer = Customer::create($customerData);

            // حفظ الهواتف
            foreach ($phoneData as $pData) {
                if (!empty($pData['phone'])) {
                    CustomerPhone::create([
                        'customer_id' => $customer->id,
                        'phone' => $pData['phone'],
                        'type' => $pData['type'] ?? 'mobile',
                        'is_primary' => false,
                        'notes' => $pData['notes'] ?? null,
                    ]);
                }
            }

            // حفظ الحقول الديناميكية
            foreach ($dynamicFields as $fieldId => $val) {
                if ($val !== null) {
                    try {
                        CustomerFieldValue::create([
                            'customer_id' => $customer->id,
                            'portfolio_field_id' => $fieldId,
                            'value' => $val,
                        ]);
                    } catch (\Exception $e) {
                        Log::warning("خطأ في حفظ الحقل الديناميكي", [
                            'field_id' => $fieldId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // حفظ المديونية
            if ($initialDebt > 0) {
                CustomerTransaction::create([
                    'customer_id'           => $customer->id,
                    'type'                  => 'debit',
                    'amount'                => $initialDebt,
                    'transaction_date'      => now()->toDateString(),
                    'description'           => 'المديونية الأولية عند استيراد العميل من ملف Excel',
                    'created_by'            => $this->userId,
                    'source'                => 'import',
                    'reference_number'      => 'IMP-' . now()->format('YmdHis') . '-' . $customer->id,
                ]);
            }

            return [
                'success' => true,
                'customer' => $customer,
                'initial_debt' => $initialDebt,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['خطأ في إنشاء العميل: ' . $e->getMessage()]
            ];
        }
    }

    /*
    |============================================================================
    |============================================================================
    |                           Private Functions
    |============================================================================
    |============================================================================
    */
    // Process Phone Field
    private function processPhoneField(array $fieldInfo, string $value, array &$phoneData): void
    {
        $phoneIndex = $fieldInfo['index'];
        $fieldType  = $fieldInfo['type'];

        if (!isset($phoneData[$phoneIndex])) {
            $phoneData[$phoneIndex] = [];
        }

        if ($fieldType === 'phone') {
            $phoneData[$phoneIndex]['phone'] = app(CustomerFieldMappingService::class)->cleanPhoneNumber($value);
        } else {
            $phoneData[$phoneIndex][$fieldType] = $value;
        }
    }



    public function failed(\Throwable $exception)
    {
        //  تأكد من وجود sessionId
        if (empty($this->sessionId)) {
            Log::error('Session ID is empty in failed()');
            return;
        }

        try {
            //  ابحث عن الجلسة بـ session_id
            $session = ImportSession::where('session_id', $this->sessionId)->first();

            //  إذا الجلسة موجودة، حدّثها
            if ($session) {
                $session->update([
                    'status' => 'failed',
                    'errors' => json_encode([
                        ['row' => 0, 'errors' => ['خطأ: ' . $exception->getMessage()]]
                    ]),
                    'completed_at' => now()
                ]);

                Log::info('Session updated to failed', [
                    'session_id' => $this->sessionId,
                    'error' => $exception->getMessage()
                ]);
            } else {
                Log::error('Session not found in failed()', [
                    'session_id' => $this->sessionId
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in failed() method', [
                'error' => $e->getMessage(),
                'session_id' => $this->sessionId ?? 'unknown'
            ]);
        }
    }
}