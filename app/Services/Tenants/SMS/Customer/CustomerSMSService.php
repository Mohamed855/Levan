<?php

namespace App\Services\Tenants\SMS\Customer;

use App\Jobs\Tenants\BusinessCenter\Customer\SendCustomerSMSJob;
use App\Models\Tenants\BusinessCenter\Customers\Phone\CustomerPhone;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\Communication\MessageLog\MessageIndividualLog;
use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use App\Services\Tenants\Communication\MessageLog\MessageLogService;
use App\Services\Tenants\SMS\Base\BaseSMSService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CustomerSMSService
{
    protected BaseSMSService $baseSMSService;
    protected MessageLogService $messageLogService;

    public function __construct(
        BaseSMSService $baseSMSService,
        MessageLogService $messageLogService
    ) {
        $this->baseSMSService = $baseSMSService;
        $this->messageLogService = $messageLogService;
    }

    /*
    |--------------------------------------------------------------------------
    | إرسال رسالة مخصصة للعملاء - محدثة لدعم أرقام متعددة
    |--------------------------------------------------------------------------
    */
    public function sendCustomMessage(string $message, array $customerIds): array
    {
        if (!$this->baseSMSService->isReady()) {
            return $this->createErrorResponse('خدمة الرسائل النصية غير متاحة');
        }

        // جلب العملاء مع أرقامهم
        $customersWithPhones = $this->getCustomersWithAllPhones($customerIds);
        if (empty($customersWithPhones)) {
            return $this->createErrorResponse('لا توجد أرقام هواتف صحيحة للعملاء المحددين');
        }

        // التحقق من وجود أي متغيرات
        if ($this->hasAnyVariables($message)) {
            // إرسال فردي لكل عميل مع معالجة المتغيرات
            return $this->sendIndividualMessagesToAllPhones($message, $customersWithPhones, null, 'custom');
        } else {
            // إرسال جماعي - رسالة ثابتة بدون متغيرات
            $processedMessage = $this->baseSMSService->convertHtmlToText($message);
            return $this->sendBulkMessageToAllPhones($processedMessage, $customersWithPhones, null, 'custom');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | إرسال رسالة باستخدام قالب للعملاء - محدثة لدعم أرقام متعددة
    |--------------------------------------------------------------------------
    */
    public function sendTemplateMessage(int $templateId, array $customerIds): array
    {
        if (!$this->baseSMSService->isReady()) {
            return $this->createErrorResponse('خدمة الرسائل النصية غير متاحة');
        }

        // التحقق من القالب
        $template = $this->baseSMSService->getTemplate($templateId);
        if (!$template) {
            return $this->createErrorResponse('القالب المحدد غير متاح');
        }

        // جلب العملاء مع أرقامهم
        $customersWithPhones = $this->getCustomersWithAllPhones($customerIds);
        if (empty($customersWithPhones)) {
            return $this->createErrorResponse('لا توجد أرقام هواتف صحيحة للعملاء المحددين');
        }

        // إرسال فردي لكل عميل (دائماً للقوالب)
        return $this->sendIndividualMessagesToAllPhones($template->content, $customersWithPhones, $templateId, 'template');
    }

    /*
    |--------------------------------------------------------------------------
    | جلب العملاء مع جميع أرقام الهواتف الصحيحة
    |--------------------------------------------------------------------------
    */
    private function getCustomersWithAllPhones(array $customerIds): array
    {
        $customers = Customer::whereIn('id', $customerIds)
            ->with([
                'phones' => function ($query) {
                    $query->select('customer_id', 'phone', 'is_primary')
                        ->whereNotNull('phone')
                        ->where('phone', '!=', '')
                        ->where('is_primary', true);
                },
                'dynamicValues' => function ($query) {
                    $query->select('customer_id', 'portfolio_field_id', 'value')
                        ->with(['portfolioField:id,field_name']);
                },
                'company:id,name',
                'portfolio:id,name',
                'nationalityCountry:id,name',
                'relationshipManager:id,name',
                'customerStatus:id,name'
            ])
            ->select(
                'id',
                'name',
                'first_name',
                'father_name',
                'grandfather_name',
                'family_name',
                'phone',
                'identity_number',
                'customer_type',
                'account_number',
                'commission_rate',
                'contract_date',
                'default_date',
                'nationality',
                'customer_status_id',
                'relationship_manager_id',
                'company_id',
                'portfolio_id'
            )
            ->get();

        $customersWithPhones = [];

        foreach ($customers as $customer) {
            $uniquePhones = [];
            $normalizedTracker = [];

            // ✅ أولاً: تحقق من وجود رقم افتراضي (is_primary = 1)
            $primaryExtra = $customer->phones->first();

            if ($primaryExtra && !empty($primaryExtra->phone) && $this->baseSMSService->isValidPhoneNumber($primaryExtra->phone)) {
                // ✅ إذا كان هناك رقم افتراضي، استخدمه فقط
                $formatted = $this->baseSMSService->formatPhoneNumber($primaryExtra->phone);
                $norm = preg_replace('/[^0-9]/', '', $formatted);

                if (str_starts_with($norm, '966')) {
                    $key = $norm;
                } elseif (str_starts_with($norm, '05')) {
                    $key = '966' . substr($norm, 1);
                } elseif (str_starts_with($norm, '5') && strlen($norm) === 9) {
                    $key = '966' . $norm;
                } else {
                    $key = $norm;
                }

                if (!in_array($key, $normalizedTracker, true)) {
                    $uniquePhones[] = $formatted;
                    $normalizedTracker[] = $key;
                }
            } elseif (!empty($customer->phone) && $this->baseSMSService->isValidPhoneNumber($customer->phone)) {
                // ✅ فقط إذا لم يكن هناك رقم افتراضي، استخدم الرقم الأساسي
                $formatted = $this->baseSMSService->formatPhoneNumber($customer->phone);
                $norm = preg_replace('/[^0-9]/', '', $formatted);

                if (str_starts_with($norm, '966')) {
                    $key = $norm;
                } elseif (str_starts_with($norm, '05')) {
                    $key = '966' . substr($norm, 1);
                } elseif (str_starts_with($norm, '5') && strlen($norm) === 9) {
                    $key = '966' . $norm;
                } else {
                    $key = $norm;
                }

                if (!in_array($key, $normalizedTracker, true)) {
                    $uniquePhones[] = $formatted;
                    $normalizedTracker[] = $key;
                }
            }

            if (!empty($uniquePhones)) {
                $customersWithPhones[] = [
                    'customer'     => $customer,
                    'phones'       => $uniquePhones,
                    'total_phones' => count($uniquePhones)
                ];
            }
        }

        return $customersWithPhones;
    }
    /*
    |--------------------------------------------------------------------------
    | Send Individual Messages To All Phones
    |--------------------------------------------------------------------------
    */
    private function sendIndividualMessagesToAllPhones(
        string $messageTemplate,
        array $customersWithPhones,
        ?int $templateId = null,
        string $messageType = 'custom'
    ): array {
        $successCount = 0;
        $failedCount = 0;
        $totalPhonesCount = 0;
        $successfulPhonesCount = 0;
        $errors = [];
        $allCustomerIds = [];

        // حساب إجمالي الأرقام والعملاء
        foreach ($customersWithPhones as $customerData) {
            $allCustomerIds[] = $customerData['customer']->id;
            $totalPhonesCount += $customerData['total_phones'];
        }

        // تحضير بيانات القالب
        $templateName = null;
        if ($templateId) {
            $template = $this->baseSMSService->getTemplate($templateId);
            $templateName = $template ? $template->name : null;
        }

        // تسجيل الرسالة الأساسية (Batch)
        $mainMessageLog = $this->messageLogService->logCustomerMessage(
            $messageTemplate,
            $allCustomerIds,
            'processing',
            $templateId,
            [
                'platform' => '4jawaly',
                'message_type' => $messageType,
                'template_name' => $templateName,
                'individual_send' => true,
                'total_customers' => count($customersWithPhones),
                'total_phone_numbers' => $totalPhonesCount,
                'multiple_phones_per_customer' => true,
                'metadata' => [
                    'customers_count' => count($customersWithPhones),
                    'phone_numbers_count' => $totalPhonesCount,
                    'total_cost' => $this->baseSMSService->calculateCost($totalPhonesCount),
                    'initiated_at' => now()->toISOString(),
                ]
            ]
        );

        foreach ($customersWithPhones as $customerData) {
            $customer = $customerData['customer'];
            $customerPhones = $customerData['phones'];

            try {
                // معالجة الرسالة للعميل الحالي مع جميع المتغيرات
                $processedMessage = $this->processMessageForCustomer($messageTemplate, $customer);

                // إنشاء سجل فردي للعميل
                $individualLog = MessageIndividualLog::create([
                    'batch_log_id' => $mainMessageLog->id,
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'company_id' => $customer->company_id,
                    // 'company_name' => $customer->company->name ?? null,
                    'portfolio_id' => $customer->portfolio_id,
                    // 'portfolio_name' => $customer->portfolio->name ?? null,
                    'phone_numbers' => $customerPhones,
                    'phone_numbers_count' => count($customerPhones),
                    'message_content' => $processedMessage,
                    'message_type' => 'sms',
                    'platform' => '4jawaly',
                    'template_id' => $templateId,
                    'template_name' => $templateName,
                    'status' => 'pending',
                    'cost_per_message' => $this->baseSMSService->getMessageCost(),
                    'total_cost' => $this->baseSMSService->calculateCost(count($customerPhones)),
                    'sent_by' => auth()->id(),
                ]);

                // إرسال الرسالة لجميع أرقام العميل
                $success = $this->baseSMSService->send4JawalyMessage($processedMessage, $customerPhones);

                if ($success) {
                    // تحديث السجل الفردي بالنجاح
                    $individualLog->updateSendStatus(true, [
                        'status_message' => 'تم الإرسال بنجاح لجميع الأرقام'
                    ]);

                    $successfulPhonesCount += count($customerPhones);
                    $successCount++;

                    $this->touchCustomerLastSms($customer, $processedMessage);
                } else {
                    // تحديث السجل الفردي بالفشل
                    $individualLog->updateSendStatus(false, [
                        'error_message' => 'فشل في الإرسال عبر مقدم الخدمة',
                        'status_message' => 'فشل الإرسال'
                    ]);

                    $failedCount += count($customerPhones);
                    $errors[] = "فشل إرسال الرسالة للعميل: {$customer->name}";
                }

                // توقف قصير بين العملاء
                usleep(100000); // 0.1 ثانية

            } catch (\Exception $e) {
                // إنشاء سجل فردي للخطأ إذا لم يتم إنشاؤه
                if (!isset($individualLog)) {
                    $individualLog = MessageIndividualLog::create([
                        'batch_log_id' => $mainMessageLog->id,
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'phone_numbers' => $customerPhones,
                        'phone_numbers_count' => count($customerPhones),
                        'message_content' => $messageTemplate,
                        'status' => 'failed',
                        'sent_by' => auth()->id(),
                    ]);
                }

                $individualLog->updateSendStatus(false, [
                    'error_message' => $e->getMessage(),
                    'status_message' => 'خطأ في النظام'
                ]);

                $failedCount += count($customerPhones);
                $errors[] = "خطأ للعميل {$customer->name}: " . $e->getMessage();

                // Log::error('Individual SMS sending failed', [
                //     'customer_id' => $customer->id,
                //     'customer_name' => $customer->name,
                //     'phones_count' => count($customerPhones),
                //     'error' => $e->getMessage(),
                //     'individual_log_id' => $individualLog->id
                // ]);
            }
        }

        // تحديث الرسالة الأساسية بالنتيجة النهائية
        if ($successfulPhonesCount > 0) {
            $this->messageLogService->updateMessageStatus(
                $mainMessageLog->id,
                $failedCount > 0 ? 'partial' : 'sent',
                [
                    'sent_count' => $successfulPhonesCount,
                    'failed_count' => $failedCount,
                    'customers_reached' => $successCount,
                    'total_cost' => $this->baseSMSService->calculateCost($successfulPhonesCount),
                    'completion_summary' => [
                        'total_customers' => count($customersWithPhones),
                        'customers_reached' => $successCount,
                        'total_phone_numbers' => $totalPhonesCount,
                        'successful_sends' => $successfulPhonesCount,
                        'failed_sends' => $failedCount,
                        'success_rate' => round(($successfulPhonesCount / $totalPhonesCount) * 100, 2) . '%'
                    ],
                    'completed_at' => now()->toISOString()
                ]
            );
        } else {
            $this->messageLogService->updateMessageStatus(
                $mainMessageLog->id,
                'failed',
                [
                    'sent_count' => 0,
                    'failed_count' => $totalPhonesCount,
                    'status_message' => 'فشل في إرسال جميع الرسائل'
                ]
            );
        }

        // إرجاع النتيجة
        if ($successfulPhonesCount > 0) {
            $message = "تم إرسال {$successfulPhonesCount} رسالة بنجاح لـ {$successCount} عميل";
            if ($failedCount > 0) {
                $message .= " وفشل إرسال {$failedCount} رسالة";
            }

            return $this->createSuccessResponse($message, $mainMessageLog->id, $successCount);
        } else {
            return $this->createErrorResponse(
                "فشل في إرسال جميع الرسائل",
                $mainMessageLog->id
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send Bulk Message To AllPhones
    |--------------------------------------------------------------------------
    */
    private function sendBulkMessageToAllPhones(
        string $message,
        array $customersWithPhones,
        ?int $templateId = null,
        string $messageType = 'custom'
    ): array {
        // تجميع جميع أرقام الهواتف
        $allPhones = [];
        $allCustomerIds = [];
        $totalPhonesCount = 0;

        foreach ($customersWithPhones as $customerData) {
            $allCustomerIds[] = $customerData['customer']->id;
            $allPhones = array_merge($allPhones, $customerData['phones']);
            $totalPhonesCount += $customerData['total_phones'];
        }

        // تحضير بيانات القالب
        $templateName = null;
        if ($templateId) {
            $template = $this->baseSMSService->getTemplate($templateId);
            $templateName = $template ? $template->name : null;
        }

        // تسجيل الرسالة قبل الإرسال (Batch)
        $messageLog = $this->messageLogService->logCustomerMessage(
            $message,
            $allCustomerIds,
            'processing',
            $templateId,
            [
                'platform' => '4jawaly',
                'message_type' => $messageType,
                'template_name' => $templateName,
                'bulk_send' => true,
                'multiple_phones_per_customer' => true,
                'metadata' => [
                    'customers_count' => count($customersWithPhones),
                    'phone_numbers_count' => $totalPhonesCount,
                    'total_cost' => $this->baseSMSService->calculateCost($totalPhonesCount),
                    'initiated_at' => now()->toISOString(),
                ]
            ]
        );

        // إنشاء السجلات الفردية لكل عميل
        $individualLogs = [];
        foreach ($customersWithPhones as $customerData) {
            $customer = $customerData['customer'];
            $customerPhones = $customerData['phones'];

            $individualLog = MessageIndividualLog::create([
                'batch_log_id' => $messageLog->id,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'company_id' => $customer->company_id,
                // 'company_name' => $customer->company->name ?? null,
                'portfolio_id' => $customer->portfolio_id,
                // 'portfolio_name' => $customer->portfolio->name ?? null,
                'phone_numbers' => $customerPhones,
                'phone_numbers_count' => count($customerPhones),
                'message_content' => $message,
                'message_type' => 'sms',
                'platform' => '4jawaly',
                'template_id' => $templateId,
                'template_name' => $templateName,
                'status' => 'pending',
                'cost_per_message' => $this->baseSMSService->getMessageCost(),
                'total_cost' => $this->baseSMSService->calculateCost(count($customerPhones)),
                'sent_by' => auth()->id(),
            ]);

            $individualLogs[] = [
                'log' => $individualLog,
                'customer' => $customer,
                'phones' => $customerPhones
            ];
        }

        try {
            // إرسال الرسائل
            $success = $this->baseSMSService->send4JawalyMessage($message, $allPhones);

            if ($success) {
                // تحديث جميع السجلات الفردية بالنجاح
                foreach ($individualLogs as $logData) {
                    $logData['log']->updateSendStatus(true, [
                        'status_message' => 'تم الإرسال بنجاح ضمن الإرسال الجماعي'
                    ]);

                    $this->touchCustomerLastSms($logData['customer'], $message);
                }

                // تحديث حالة النجاح للـ Batch
                $this->messageLogService->updateMessageStatus(
                    $messageLog->id,
                    'sent',
                    [
                        'sent_count' => $totalPhonesCount,
                        'failed_count' => 0,
                        'customers_reached' => count($customersWithPhones),
                        'total_cost' => $this->baseSMSService->calculateCost($totalPhonesCount),
                        'response_data' => [
                            'success' => true,
                            'sent_to' => $allPhones,
                            'platform_response' => '4Jawaly SMS sent successfully'
                        ]
                    ]
                );

                // Log::info('Bulk Customer SMS sent successfully', [
                //     'message_log_id' => $messageLog->id,
                //     'customer_count' => count($customersWithPhones),
                //     'phone_count' => $totalPhonesCount,
                //     'individual_logs_count' => count($individualLogs)
                // ]);

                return $this->createSuccessResponse(
                    "تم إرسال الرسائل بنجاح إلى {$totalPhonesCount} رقم لـ " . count($customersWithPhones) . " عميل",
                    $messageLog->id,
                    count($customersWithPhones)
                );
            } else {
                // تحديث جميع السجلات الفردية بالفشل
                foreach ($individualLogs as $logData) {
                    $logData['log']->updateSendStatus(false, [
                        'error_message' => 'فشل في الإرسال الجماعي عبر مقدم الخدمة',
                        'status_message' => 'فشل الإرسال الجماعي'
                    ]);
                }

                // تحديث حالة الفشل للـ Batch
                $this->updateMessageLogFailure($messageLog->id, $allPhones, 'فشل في إرسال الرسائل عبر مقدم الخدمة');

                return $this->createErrorResponse(
                    'فشل في إرسال الرسائل. تحقق من إعدادات الرسائل النصية',
                    $messageLog->id
                );
            }
        } catch (\Exception $e) {
            // تحديث جميع السجلات الفردية بالخطأ
            foreach ($individualLogs as $logData) {
                $logData['log']->updateSendStatus(false, [
                    'error_message' => 'خطأ في النظام: ' . $e->getMessage(),
                    'status_message' => 'خطأ في النظام'
                ]);
            }

            // تسجيل الأخطاء
            // Log::error('Bulk Customer SMS sending failed with exception', [
            //     'message_log_id' => $messageLog->id,
            //     'error' => $e->getMessage(),
            //     'individual_logs_affected' => count($individualLogs)
            // ]);

            // تحديث حالة الفشل للـ Batch
            $this->updateMessageLogFailure($messageLog->id, $allPhones, 'خطأ في النظام: ' . $e->getMessage());

            return $this->createErrorResponse(
                'حدث خطأ أثناء إرسال الرسائل: ' . $e->getMessage(),
                $messageLog->id
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | جلب العملاء مع أرقام الهواتف الصحيحة - محدثة للتوافق مع الكود القديم
    |--------------------------------------------------------------------------
    */
    protected function getValidCustomers(array $customerIds): Collection
    {
        $customersWithPhones = $this->getCustomersWithAllPhones($customerIds);

        // تحويل إلى Collection للتوافق مع الكود الموجود
        $customers = collect();
        foreach ($customersWithPhones as $customerData) {
            $customer = $customerData['customer'];
            $customer->all_phones = $customerData['phones']; // إضافة الأرقام كخاصية
            $customers->push($customer);
        }

        return $customers;
    }

    /*
    |--------------------------------------------------------------------------
    | تحضير بيانات المستلمين للتسجيل - محدثة لدعم أرقام متعددة
    |--------------------------------------------------------------------------
    */
    protected function prepareRecipientsData(Collection $customers): array
    {
        $recipients = [];

        foreach ($customers as $customer) {
            if (isset($customer->all_phones)) {
                // استخدام جميع الأرقام
                foreach ($customer->all_phones as $phone) {
                    $recipients[] = [
                        'id' => $customer->id,
                        'type' => 'customer',
                        'name' => $customer->name,
                        'phone' => $phone,
                        'formatted_phone' => $phone // الرقم مُنسق مسبقاً
                    ];
                }
            } else {
                // الطريقة القديمة للتوافق
                $recipients[] = [
                    'id' => $customer->id,
                    'type' => 'customer',
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'formatted_phone' => $this->baseSMSService->formatPhoneNumber($customer->phone)
                ];
            }
        }

        return $recipients;
    }

    /*
    |--------------------------------------------------------------------------
    | معالجة المتغيرات العامة
    |--------------------------------------------------------------------------
    */
    private function processSystemVariables(string $message): string
    {
        $systemVariables = [
            '{date}' => now()->format('Y-m-d'),
            '{time}' => now()->format('H:i'),
            '{datetime}' => now()->format('Y-m-d H:i'),
            '{year}' => now()->format('Y'),
            '{month}' => now()->format('m'),
            '{day}' => now()->format('d'),
        ];

        return str_replace(array_keys($systemVariables), array_values($systemVariables), $message);
    }

    /*
    |--------------------------------------------------------------------------
    | معالجة أي رسالة بجميع المتغيرات (العملاء + العامة)
    |--------------------------------------------------------------------------
    */
    private function processMessageForCustomer(string $message, Customer $customer): string
    {
        // أولاً: معالجة متغيرات العميل
        $customerVariableKeys = array_keys(MessageTemplate::getCustomerVariables());

        $customerVariables = [];
        foreach ($customerVariableKeys as $variable) {
            $customerVariables[$variable] = $this->getCustomerVariableValue($variable, $customer);
        }

        // استبدال متغيرات العميل
        $processedMessage = str_replace(array_keys($customerVariables), array_values($customerVariables), $message);

        // ثانياً: معالجة المتغيرات العامة
        $processedMessage = $this->processSystemVariables($processedMessage);

        // ثالثاً: تحويل HTML إلى نص
        return $this->baseSMSService->convertHtmlToText($processedMessage);
    }

    /*
    |--------------------------------------------------------------------------
    | معالجة قالب الرسالة بمتغيرات العميل - للتوافق مع الكود القديم
    |--------------------------------------------------------------------------
    */
    protected function processCustomerTemplate(string $template, Customer $customer): string
    {
        return $this->processMessageForCustomer($template, $customer);
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على قيمة متغير معين للعميل
    |--------------------------------------------------------------------------
    */
    private function getCustomerVariableValue(string $variable, Customer $customer): string
    {
        return match ($variable) {
            // -- New method to handle Arabic names ----
            // '{customer_name}' => $customer->name ?? '',
            // -- New method to handle Arabic names ----
            '{customer_name}' => $this->extractFirstName($customer),
            '{first_name}' => $customer->first_name ?? '',
            '{father_name}' => $customer->father_name ?? '',
            '{grandfather_name}' => $customer->grandfather_name ?? '',
            '{family_name}' => $customer->family_name ?? '',
            '{customer_type_label}' => $customer->customer_type_label ?? '',
            '{identity_number}' => $this->maskIdentityNumber($customer->identity_number ?? ''),
            '{phone}' => $customer->phone ?? '',
            '{service_number}' => $this->getServiceNumber($customer),
            '{account_number}' => $customer->account_number ?? '',

            // استخدام الـ accessor بدلاً من الحقل المباشر
            '{formatted_debt_amount}' => $customer->formatted_remaining_debt ?? '0.00 ريال',

            '{formatted_commission_rate}' => $customer->formatted_commission_rate ?? '',
            '{formatted_contract_date}' => $customer->formatted_contract_date ?? '',
            '{formatted_default_date}' => $customer->formatted_default_date ?? '',
            '{nationality}' => $customer->nationalityCountry->name ?? '',
            '{status_label}' => $customer->status_label ?? '',
            '{relationship_manager}' => $customer->relationshipManager->name ?? '',
            '{company_name}' => $customer->company->name ?? '',
            '{portfolio_name}' => $customer->portfolio->name ?? '',
            default => ''
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Extract First Name From Customer Name 
    |--------------------------------------------------------------------------
    | -- New method to handle Arabic names ----
    */
    private function extractFirstName(Customer $customer): string
    {
        if (!empty($customer->first_name)) {
            return trim($customer->first_name);
        }

        $full = trim(preg_replace('/\s+/u', ' ', (string)($customer->name ?? '')));
        if ($full === '') {
            return '';
        }

        $parts = preg_split('/\s+/u', $full);
        if (!$parts || count($parts) === 0) {
            return '';
        }

        if ($parts[0] === 'عبد' && isset($parts[1])) {
            return 'عبد ' . $parts[1];
        }

        return $parts[0];
    }

    /*
    |--------------------------------------------------------------------------
    | إخفاء رقم الهوية وإظهار آخر 4 أرقام فقط
    |--------------------------------------------------------------------------
    */
    private function maskIdentityNumber(string $identityNumber): string
    {
        if (empty($identityNumber)) {
            return '';
        }

        // إزالة أي مسافات أو رموز
        $cleanNumber = preg_replace('/[^0-9]/', '', $identityNumber);

        if (strlen($cleanNumber) <= 4) {
            return $cleanNumber;
        }

        // إخفاء جميع الأرقام ما عدا آخر 4
        $maskedLength = strlen($cleanNumber) - 4;
        $lastFour = substr($cleanNumber, -4);

        // استخدام LTR Override لضمان الاتجاه الصحيح من اليسار لليمين
        return "\u{202D}" . str_repeat('*', $maskedLength) . $lastFour . "\u{202C}";
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على رقم الخدمة من بيانات العميل الديناميكية
    |--------------------------------------------------------------------------
    | تبحث هذه الدالة في الحقول الديناميكية للعميل وتعيد قيمة حقل "رقم الخدمة" إذا وُجد.
    */
    private function getServiceNumber(Customer $customer): string
    {
        // البحث في الحقول المحملة مسبقاً (بدون استعلام إضافي)
        foreach ($customer->dynamicValues as $dynamicValue) {
            if (!$dynamicValue->portfolioField) {
                continue;
            }

            $fieldName = strtolower(trim($dynamicValue->portfolioField->field_name));

            // البحث بأسماء مختلفة للحقل
            if (in_array($fieldName, [
                'service_number',
                'رقم الخدمة',
                'service_no',
                'خدمة',
                'رقم خدمة'
            ])) {
                return $dynamicValue->value ?? '';
            }
        }

        return '';
    }

    /*
    |--------------------------------------------------------------------------
    | معاينة رسالة القالب
    |--------------------------------------------------------------------------
    */
    public function previewTemplateMessage(int $templateId, ?int $customerId = null): array
    {
        $template = $this->baseSMSService->getTemplate($templateId);
        if (!$template) {
            return [
                'success' => false,
                'message' => 'القالب غير موجود أو غير نشط'
            ];
        }

        // استخدام عميل محدد أو بيانات نموذجية
        if ($customerId) {
            $customer = Customer::find($customerId);
        }

        if (!isset($customer)) {
            $customer = new Customer([
                'name' => 'أحمد محمد',
                'first_name' => 'أحمد',
                'phone' => '966501234567',
                'identity_number' => '1234567890',
                'email' => 'ahmed@example.com'
            ]);
        }

        $processedMessage = $this->processMessageForCustomer($template->content, $customer);

        return [
            'success' => true,
            'preview' => $processedMessage,
            'length' => mb_strlen($processedMessage),
            'cost' => $this->baseSMSService->getMessageCost(),
            'variables_used' => $this->extractUsedVariables($template->content)
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | استخراج المتغيرات المستخدمة في القالب
    |--------------------------------------------------------------------------
    */
    protected function extractUsedVariables(string $content): array
    {
        preg_match_all('/\{([^}]+)\}/', $content, $matches);
        return array_unique($matches[1] ?? []);
    }

    /*
    |--------------------------------------------------------------------------
    | تحديث سجل الرسالة عند النجاح
    |--------------------------------------------------------------------------
    */
    protected function updateMessageLogSuccess(int $messageLogId, array $phoneNumbers): void
    {
        $this->messageLogService->updateMessageStatus(
            $messageLogId,
            'sent',
            [
                'sent_count' => count($phoneNumbers),
                'failed_count' => 0,
                'total_cost' => $this->baseSMSService->calculateCost(count($phoneNumbers)),
                'response_data' => [
                    'success' => true,
                    'sent_to' => $phoneNumbers,
                    'platform_response' => '4Jawaly SMS sent successfully'
                ]
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | تحديث سجل الرسالة عند الفشل
    |--------------------------------------------------------------------------
    */
    protected function updateMessageLogFailure(int $messageLogId, array $phoneNumbers, string $errorMessage): void
    {
        $this->messageLogService->updateMessageStatus(
            $messageLogId,
            'failed',
            [
                'sent_count' => 0,
                'failed_count' => count($phoneNumbers),
                'status_message' => $errorMessage,
                'response_data' => [
                    'success' => false,
                    'error' => $errorMessage
                ]
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على إحصائيات رسائل العملاء
    |--------------------------------------------------------------------------
    */
    public function getCustomerSMSStatistics(): array
    {
        return $this->messageLogService->getMessageStats([
            'target_model' => 'customers',
            'message_type' => 'sms'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على آخر الرسائل المرسلة للعملاء
    |--------------------------------------------------------------------------
    */
    public function getRecentCustomerMessages(int $limit = 10): Collection
    {
        return $this->messageLogService->getRecentMessages([
            'target_model' => 'customers',
            'message_type' => 'sms',
            'limit' => $limit
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | التحقق من وجود أي متغيرات في النص
    |--------------------------------------------------------------------------
    */
    private function hasAnyVariables(string $message): bool
    {
        // متغيرات العملاء
        $customerVariables = array_keys(MessageTemplate::getCustomerVariables());
        foreach ($customerVariables as $variable) {
            if (strpos($message, $variable) !== false) {
                return true;
            }
        }

        // المتغيرات العامة
        $systemVariables = array_keys(MessageTemplate::getGeneralVariables());
        foreach ($systemVariables as $variable) {
            if (strpos($message, $variable) !== false) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | التحقق من وجود متغيرات العملاء في النص
    |--------------------------------------------------------------------------
    */
    private function hasCustomerVariables(string $message): bool
    {
        $customerVariables = array_keys(MessageTemplate::getCustomerVariables());

        foreach ($customerVariables as $variable) {
            if (strpos($message, $variable) !== false) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | التحقق من وجود متغيرات النظام في النص
    |--------------------------------------------------------------------------
    */
    private function hasSystemVariables(string $message): bool
    {
        $systemVariables = array_keys(MessageTemplate::getGeneralVariables());

        foreach ($systemVariables as $variable) {
            if (strpos($message, $variable) !== false) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | إنشاء استجابة نجاح
    |--------------------------------------------------------------------------
    */
    protected function createSuccessResponse(string $message, ?int $messageLogId = null, ?int $count = null): array
    {
        return [
            'success' => true,
            'message' => $message,
            'message_log_id' => $messageLogId,
            'customers_count' => $count
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | إنشاء استجابة خطأ
    |--------------------------------------------------------------------------
    */
    protected function createErrorResponse(string $message, ?int $messageLogId = null): array
    {
        return [
            'success' => false,
            'message' => $message,
            'message_log_id' => $messageLogId,
            'customers_count' => 0
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | التحقق من صحة معرفات العملاء - محدثة لدعم أرقام متعددة
    |--------------------------------------------------------------------------
    */
    public function validateCustomerIds(array $customerIds): array
    {
        if (empty($customerIds)) {
            return $this->createErrorResponse('يرجى تحديد عميل واحد على الأقل');
        }

        $customersWithPhones = $this->getCustomersWithAllPhones($customerIds);

        if (empty($customersWithPhones)) {
            return $this->createErrorResponse('لا توجد أرقام هواتف صحيحة للعملاء المحددين');
        }

        // تحويل إلى format مناسب للعرض
        $customers = collect();
        foreach ($customersWithPhones as $customerData) {
            $customer = $customerData['customer'];
            $customer->phones_count = $customerData['total_phones'];
            $customer->all_phones_list = $customerData['phones'];
            $customers->push($customer);
        }

        return [
            'success' => true,
            'valid_customers' => $customers,
            'total_customers' => $customers->count(),
            'total_phone_numbers' => array_sum(array_column($customersWithPhones, 'total_phones'))
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | تحضير بيانات العملاء لعرضها في النموذج - محدثة لدعم أرقام متعددة
    |--------------------------------------------------------------------------
    */
    public function prepareCustomersForSMSForm(array $customerIds): array
    {
        if (count($customerIds) > 500) {
            return $this->getFastFormData($customerIds);
        }

        $quickValidation = $this->quickValidateCustomerIds($customerIds);
        if (!$quickValidation['success']) {
            return $quickValidation;
        }

        $templates = MessageTemplate::active()
            ->where('category', 'customers')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return [
            'success' => true,
            'data' => [
                'templates' => $templates,
                'variables' => $this->getAvailableVariables(),
                'total_customers' => $quickValidation['total_customers'],
                'total_phone_numbers' => $quickValidation['total_phone_numbers'],
                'estimated_cost' => $this->baseSMSService->calculateCost($quickValidation['total_phone_numbers'])
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Fast Form Data
    |--------------------------------------------------------------------------
    */
    private function getFastFormData(array $customerIds): array
    {
        $templates = MessageTemplate::active()
            ->where('category', 'customers')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $estimatedPhones = count($customerIds) * 1.2;

        return [
            'success' => true,
            'data' => [
                'templates' => $templates,
                'variables' => $this->getAvailableVariables(),
                'total_customers' => count($customerIds),
                'total_phone_numbers' => (int) $estimatedPhones,
                'estimated_cost' => $this->baseSMSService->calculateCost($estimatedPhones)
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Quick Validate Customer Ids
    |--------------------------------------------------------------------------
    */
    private function quickValidateCustomerIds(array $customerIds): array
    {
        if (empty($customerIds)) {
            return ['success' => false, 'message' => 'يرجى تحديد عميل واحد على الأقل'];
        }

        // عد سريع للعملاء والأرقام بدون جلب البيانات الكاملة
        $customerCount = Customer::whereIn('id', $customerIds)->count();

        if ($customerCount === 0) {
            return ['success' => false, 'message' => 'لا توجد عملاء صحيحون'];
        }

        // حساب تقريبي للأرقام (العميل + أرقامه الإضافية)
        $totalPhones = Customer::whereIn('id', $customerIds)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->count();

        $additionalPhones = CustomerPhone::whereIn('customer_id', $customerIds)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->count();

        return [
            'success' => true,
            'total_customers' => $customerCount,
            'total_phone_numbers' => $totalPhones + $additionalPhones
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على المتغيرات المتاحة للعملاء - من المصدر الوحيد
    |--------------------------------------------------------------------------
    */
    protected function getAvailableVariables(): array
    {
        return MessageTemplate::getGroupedVariables('customers');
    }

    /*
    |--------------------------------------------------------------------------
    | إرسال رسالة مخصصة للعملاء مع الطوابير - محسن لدعم أرقام متعددة
    |--------------------------------------------------------------------------
    */
    public function sendCustomMessageWithQueue(string $message, array $customerIds): array
    {
        if (!$this->baseSMSService->isReady()) {
            return $this->createErrorResponse('خدمة الرسائل النصية غير متاحة');
        }

        if (empty($customerIds)) {
            return $this->createErrorResponse('يرجى تحديد عميل واحد على الأقل');
        }

        // التحقق من صحة العملاء أولاً
        $customersWithPhones = $this->getCustomersWithAllPhones($customerIds);
        if (empty($customersWithPhones)) {
            return $this->createErrorResponse('لا توجد أرقام هواتف صحيحة للعملاء المحددين');
        }

        // حساب إجمالي الأرقام
        $totalPhones = array_sum(array_column($customersWithPhones, 'total_phones'));

        // تقسيم العملاء إلى مجموعات (تعديل حجم المجموعة حسب الحاجة)
        $chunkSize = $this->calculateOptimalChunkSize(count($customerIds));
        $chunks = array_chunk($customerIds, $chunkSize);
        $batchId = time();

        // إرسال كل مجموعة كـ job منفصل
        foreach ($chunks as $index => $chunk) {
            SendCustomerSMSJob::dispatch(
                $message,
                $chunk,
                null,
                'custom',
                $batchId + $index
            )->delay(now()->addSeconds($index * 2)); // تأخير قصير بين المجموعات
        }

        return [
            'success' => true,
            'customers_count' => count($customerIds),
            'total_phone_numbers' => $totalPhones
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | إرسال قالب للعملاء مع الطوابير - النسخة المُصححة
    |--------------------------------------------------------------------------
    | في ملف CustomerSMSService.php، استبدل دالة sendTemplateMessageWithQueue بهذه النسخة:
    */
    public function sendTemplateMessageWithQueue(int $templateId, array $customerIds): array
    {
        if (!$this->baseSMSService->isReady()) {
            return $this->createErrorResponse('خدمة الرسائل النصية غير متاحة');
        }

        $template = $this->baseSMSService->getTemplate($templateId);
        if (!$template) {
            return $this->createErrorResponse('القالب المحدد غير متاح');
        }

        // إزالة التكرار والقيم الفارغة
        $uniqueCustomerIds = array_values(array_unique(array_filter(array_map('intval', $customerIds))));

        if (empty($uniqueCustomerIds)) {
            return $this->createErrorResponse('يرجى تحديد عميل واحد على الأقل');
        }

        // التحقق من صحة العملاء
        $customersWithPhones = $this->getCustomersWithAllPhones($uniqueCustomerIds);
        if (empty($customersWithPhones)) {
            return $this->createErrorResponse('لا توجد أرقام هواتف صحيحة للعملاء المحددين');
        }

        // حساب التفاصيل
        $allCustomerIds = array_map(fn($c) => $c['customer']->id, $customersWithPhones);
        $totalPhones = array_sum(array_column($customersWithPhones, 'total_phones'));

        $chunkSize = $this->calculateOptimalChunkSize(count($uniqueCustomerIds));
        $chunks = array_chunk($uniqueCustomerIds, $chunkSize);

        // إنشاء batch ID فريد مضمون
        $batchId = time() . '_' . uniqid() . '_' . count($chunks);

        // إنشاء main message log
        $mainBatchLog = $this->messageLogService->logCustomerMessage(
            'Batch Template Message - Queue Processing',
            $allCustomerIds,
            'processing',
            $templateId,
            [
                'platform' => '4jawaly',
                'message_type' => 'template',
                'template_name' => $template->name,
                'batch_send' => true,
                'batch_id' => $batchId,
                'total_customers' => count($allCustomerIds),
                'total_phone_numbers' => $totalPhones,
                'total_jobs' => count($chunks),
                'queue_processing' => true,
                'estimated_cost' => $this->baseSMSService->calculateCost($totalPhones),
                'created_at' => now()->toISOString()
            ]
        );

        // إرسال Jobs مع تأخير متدرج
        foreach ($chunks as $index => $chunk) {
            SendCustomerSMSJob::dispatch(
                $template->content,
                $chunk,
                $templateId,
                'template',
                $batchId . '_' . $index, // batch ID فريد لكل job
                $mainBatchLog->id
            )->delay(now()->addSeconds($index * 3)); // تأخير 3 ثواني بين كل job
        }

        return [
            'success' => true,
            'customers_count' => count($uniqueCustomerIds),
            'total_phone_numbers' => $totalPhones,
            'batch_id' => $batchId,
            'main_log_id' => $mainBatchLog->id,
            'jobs_count' => count($chunks),
            'estimated_cost' => $this->baseSMSService->calculateCost($totalPhones),
            'message' => 'تم جدولة الرسائل للإرسال بنجاح - لن تصل أي رسالة مكررة'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | حساب الحجم الأمثل للمجموعة
    |--------------------------------------------------------------------------
    */
    private function calculateOptimalChunkSize(int $totalCustomers): int
    {
        if ($totalCustomers <= 10) {
            return $totalCustomers; // إرسال دفعة واحدة للأعداد الصغيرة
        } elseif ($totalCustomers <= 100) {
            return 20; // 20 عميل في كل مجموعة
        } elseif ($totalCustomers <= 500) {
            return 50; // 50 عميل في كل مجموعة
        } else {
            return 100; // 100 عميل في كل مجموعة للأعداد الكبيرة
        }
    }

    /*
    |--------------------------------------------------------------------------
    | حساب الوقت المتوقع للإرسال
    |--------------------------------------------------------------------------
    */
    private function calculateEstimatedTime(int $totalBatches): string
    {
        $totalSeconds = $totalBatches * 2; // ثانيتان بين كل مجموعة

        if ($totalSeconds < 60) {
            return $totalSeconds . ' ثانية';
        } elseif ($totalSeconds < 3600) {
            return round($totalSeconds / 60, 1) . ' دقيقة';
        } else {
            return round($totalSeconds / 3600, 1) . ' ساعة';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | إرسال الرسائل مع التسجيل الكامل - محدثة للتوافق
    |--------------------------------------------------------------------------
    */
    protected function sendAndLog(
        string $message,
        Collection $customers,
        ?int $templateId = null,
        string $messageType = 'custom'
    ): array {
        // تحويل Collection إلى array للاستفادة من الدوال الجديدة
        $customerIds = $customers->pluck('id')->toArray();
        $customersWithPhones = $this->getCustomersWithAllPhones($customerIds);

        if (empty($customersWithPhones)) {
            return $this->createErrorResponse('لا توجد أرقام هواتف صحيحة للعملاء المحددين');
        }

        // استخدام الدالة الجديدة للإرسال الجماعي
        return $this->sendBulkMessageToAllPhones($message, $customersWithPhones, $templateId, $messageType);
    }

    /*
    |--------------------------------------------------------------------------
    | إرسال رسائل فردية لكل عميل - للتوافق مع الكود القديم
    |--------------------------------------------------------------------------
    */
    private function sendIndividualMessages(
        string $messageTemplate,
        Collection $customers,
        ?int $templateId = null,
        string $messageType = 'custom'
    ): array {
        // تحويل Collection إلى array للاستفادة من الدوال الجديدة
        $customerIds = $customers->pluck('id')->toArray();
        $customersWithPhones = $this->getCustomersWithAllPhones($customerIds);

        if (empty($customersWithPhones)) {
            return $this->createErrorResponse('لا توجد أرقام هواتف صحيحة للعملاء المحددين');
        }

        // استخدام الدالة الجديدة للإرسال الفردي
        return $this->sendIndividualMessagesToAllPhones($messageTemplate, $customersWithPhones, $templateId, $messageType);
    }

    /*
    |--------------------------------------------------------------------------
    | Touch Customer LastSms
    |--------------------------------------------------------------------------
    */
    private function touchCustomerLastSms(Customer $customer, string $finalMessage): void
    {
        $customer->forceFill([
            'last_sms_text' => mb_substr($finalMessage, 0, 2000),
            'last_sms_at'   => now(),
        ])->saveQuietly();
    }
}
