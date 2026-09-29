<?php

namespace App\Services\Tenants\BusinessCenter\Customer\Follow;

use App\Models\Tenants\GeneralSetting\ContactResult\ContactResult;
use App\Models\Tenants\GeneralSetting\DocumentRequest\SettingsDocumentRequestReason;
use App\Models\Tenants\GeneralSetting\PaymentMethod\SettingsPaymentMethod;
use Illuminate\Support\Collection;

class ContactResultService
{
    /*
    |--------------------------------------------------------------------------
    | Get All Contact Results
    |--------------------------------------------------------------------------
    */
    public function getAllContactResults(): Collection
    {
        return ContactResult::where('status', true)
            ->orderBy('id')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Result Fields
    |--------------------------------------------------------------------------
    */
    public function getResultFields(int $contactResultId): array
    {
        // جلب أسباب طلب المستندات مسبقاً
        $documentRequestReasons = $this->getDocumentRequestReasons();

        // جلب طرق الدفع النشطة
        $paymentMethods = SettingsPaymentMethod::where('status', 'active')
            ->pluck('name', 'id')
            ->toArray();

        $fields = [
            1 => [ // تم السداد بالكامل
                [
                    'name' => 'payment_amount',
                    'label' => 'المبلغ المسدد',
                    'type' => 'number',
                    'required' => true,
                    'validation' => 'required|numeric|min:0',
                    'placeholder' => 'أدخل المبلغ المسدد',
                    'auto_fill_debt' => true,
                    'readonly' => true
                ],
                [
                    'name' => 'payment_date',
                    'label' => 'تاريخ السداد',
                    'type' => 'date',
                    'required' => true,
                    'validation' => 'required|date|before_or_equal:today',
                    'placeholder' => 'اختر تاريخ السداد'
                ],

                // حقل collection_type مخفي مع قيمة ثابتة للسداد الكامل
                [
                    'name' => 'collection_type',
                    'label' => 'نوع التحصيل',
                    'type' => 'hidden',
                    'required' => false,
                    'validation' => 'nullable',
                    'default_value' => 'cash'
                ],
                [
                    'name' => 'payment_type',
                    'label' => 'نوع السداد',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        'settlement' => 'سداد بتسوية',
                        'without_settlement' => 'سداد بدون تسوية'
                    ],
                    'validation' => 'required|in:settlement,without_settlement'
                ],
                [
                    'name' => 'payment_method_id',
                    'label' => 'طريقة التحصيل',
                    'type' => 'select',
                    'required' => true,
                    'options' => $paymentMethods,
                    'validation' => 'required|exists:settings_payment_methods,id'
                ],
                [
                    'name' => 'payment_receipt',
                    'label' => 'إرفاق إيصال السداد',
                    'type' => 'file',
                    'required' => false,
                    'validation' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240'
                ]
            ],
            2 => [ // سداد جزئي
                [
                    'name' => 'partial_payment_amount',
                    'label' => 'المبلغ المسدد',
                    'type' => 'number',
                    'required' => true,
                    'validation' => 'required|numeric|min:0',
                    'placeholder' => 'أدخل المبلغ المسدد'
                ],
                [
                    'name' => 'partial_payment_date',
                    'label' => 'تاريخ السداد',
                    'type' => 'date',
                    'required' => true,
                    'validation' => 'required|date',
                    'placeholder' => 'اختر تاريخ السداد'
                ],
                [
                    'name' => 'collection_type',
                    'label' => 'نوع التحصيل',
                    'type' => 'hidden',
                    'required' => false,
                    'validation' => 'nullable',
                    'default_value' => 'scheduled'
                ],
                [
                    'name' => 'partial_payment_type',
                    'label' => 'نوع السداد',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        'settlement' => 'سداد بتسوية',
                        'without_settlement' => 'سداد بدون تسوية'
                    ],
                    'validation' => 'required|in:settlement,without_settlement'
                ],
                [
                    'name' => 'payment_method_id',
                    'label' => 'طريقة التحصيل',
                    'type' => 'select',
                    'required' => true,
                    'options' => $paymentMethods,
                    'validation' => 'required|exists:settings_payment_methods,id'
                ],
                [
                    'name' => 'next_partial_payment_date',
                    'label' => 'تاريخ السداد القادم',
                    'type' => 'date',
                    'required' => true,
                    'validation' => 'required|date|after:today',
                    'placeholder' => 'اختر تاريخ السداد القادم'
                ],
                [
                    'name' => 'partial_payment_receipt',
                    'label' => 'إرفاق إيصال السداد',
                    'type' => 'file',
                    'required' => false,
                    'validation' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240'
                ]
            ],
            3 => [ // وعد بالسداد
                [
                    'name' => 'promise_amount',
                    'label' => 'المبلغ الموعود به',
                    'type' => 'number',
                    'required' => true,
                    'validation' => 'required|numeric|min:0',
                    'placeholder' => 'أدخل المبلغ الموعود به'
                ],
                [
                    'name' => 'promise_date',
                    'label' => 'تاريخ السداد الموعود',
                    'type' => 'date',
                    'required' => true,
                    'validation' => 'required|date|after_or_equal:today',
                    'placeholder' => 'اختر تاريخ السداد الموعود'
                ]
            ],
            4 => [
                [
                    'name' => 'callback_date',
                    'label' => 'تاريخ الاتصال القادم',
                    'type' => 'datetime-local',
                    'required' => true,
                    'validation' => 'required|date|after:now',
                    'placeholder' => 'اختر تاريخ ووقت الاتصال القادم'
                ],
                [
                    'name' => 'callback_reason',
                    'label' => 'سبب معاودة الاتصال',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        'client_busy' => 'افاد العميل بأنه مشغول حالياً',
                        'branch_review' => 'مراجعة فرع الشركة للتأكد من المديونية',
                        'branch_verification' => 'مراجعة فرع الشركة للتحقق من الخصم',
                        'other' => 'أخرى'
                    ],
                    'validation' => 'required|in:client_busy,branch_review,branch_verification,other'
                ],
                [
                    'name' => 'callback_reason_other',
                    'label' => 'سبب آخر',
                    'type' => 'text',
                    'required' => false, // غير مطلوب افتراضياً
                    'validation' => 'nullable|required_if:callback_reason,other|string|max:255',
                    'placeholder' => 'أدخل السبب',
                    'show_if' => [
                        'field' => 'callback_reason',
                        'value' => 'other'
                    ]
                ]
            ],
            5 => [ // لا يوجد رد
            ],
            6 => [ // الرقم خارج الخدمة
            ],
            7 => [ // الرقم غير صحيح
            ],
            8 => [ // الهاتف مغلق
            ],
            9 => [ // طلب مستندات
                [
                    'name' => 'documents_date',
                    'label' => 'تاريخ طلب المستندات',
                    'type' => 'date',
                    'required' => true,
                    'validation' => 'required|date',
                    'placeholder' => 'اختر تاريخ طلب المستندات'
                ],
                [
                    'name' => 'documents_reason',
                    'label' => 'سبب طلب المستندات',
                    'type' => 'select',
                    'required' => true,
                    'options' => $documentRequestReasons,
                    'validation' => 'required|in:' . implode(',', array_keys($documentRequestReasons))
                ]
            ],
            10 => [ // معترض
                [
                    'name' => 'objection_reason',
                    'label' => 'سبب الاعتراض',
                    'type' => 'text',
                    'required' => true,
                    'validation' => 'required|string|max:255',
                    'placeholder' => 'أدخل سبب الاعتراض'
                ]
            ],
            11 => [
                [
                    'name' => 'recovery_reason',
                    'label' => 'سبب الاسترداد',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        'valid_print' => 'برنت سارية',
                        'has_license' => 'يملك رخصة',
                        'star_report' => 'تقرير نجم بعد التعديل',
                        'traffic_decision' => 'قرار لجنة مرورية',
                        'court_deed' => 'صك محكمة',
                        'other' => 'أخرى'
                    ],
                    'validation' => 'required|in:valid_print,has_license,star_report,traffic_decision,court_deed,other'
                ],
                [
                    'name' => 'recovery_reason_other',
                    'label' => 'سبب آخر',
                    'type' => 'text',
                    'required' => false, // غير مطلوب افتراضياً
                    'validation' => 'nullable|required_if:recovery_reason,other|string|max:255',
                    'placeholder' => 'أدخل السبب',
                    'show_if' => [
                        'field' => 'recovery_reason',
                        'value' => 'other'
                    ]
                ],
                [
                    'name' => 'recovery_document',
                    'label' => 'إرفاق مستند الاسترداد',
                    'type' => 'file',
                    'required' => false,
                    'validation' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                ]
            ],

            12 => [
                [
                    'name' => 'temporary_contact_reason',
                    'label' => 'سبب عدم التواصل المؤقت',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        'debt_amount_objection' => 'اعتراض على مبلغ المديونية',
                        'fraud_operation' => 'عملية احتيال',
                        'previous_complaint' => 'شكوى سابقة',
                        'other' => 'أخرى'
                    ],
                    'validation' => 'required|in:debt_amount_objection,fraud_operation,previous_complaint,other'
                ],
                [
                    'name' => 'temporary_contact_reason_other',
                    'label' => 'سبب آخر',
                    'type' => 'text',
                    'required' => false, // غير مطلوب افتراضياً
                    'validation' => 'nullable|required_if:temporary_contact_reason,other|string|max:500',
                    'placeholder' => 'أدخل التفاصيل',
                    'show_if' => [
                        'field' => 'temporary_contact_reason',
                        'value' => 'other'
                    ]
                ]
            ],
        ];

        return $fields[$contactResultId] ?? [];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Document Request Reasons
    |--------------------------------------------------------------------------
    */
    private function getDocumentRequestReasons(): array
    {
        return SettingsDocumentRequestReason::active()
            ->pluck('name', 'id')
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Validation Rules
    |--------------------------------------------------------------------------
    */
    public function getValidationRules(int $contactResultId): array
    {
        $rules = [];
        $fields = $this->getResultFields($contactResultId);
        foreach ($fields as $field) {
            $rules[$field['name']] = $field['validation'];
        }
        return $rules;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Validation Messages
    |--------------------------------------------------------------------------
    */
    public function getValidationMessages(int $contactResultId): array
    {
        $messages = [];
        $fields = $this->getResultFields($contactResultId);
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $messages["{$fieldName}.required"] = "حقل {$field['label']} مطلوب";
            $messages["{$fieldName}.numeric"] = "حقل {$field['label']} يجب أن يكون رقماً";
            $messages["{$fieldName}.date"] = "حقل {$field['label']} يجب أن يكون تاريخاً صحيحاً";
            $messages["{$fieldName}.after"] = "حقل {$field['label']} يجب أن يكون تاريخاً مستقبلياً";
            $messages["{$fieldName}.after_or_equal"] = "حقل {$field['label']} يجب أن يكون تاريخاً يساوي اليوم أو بعده";
            $messages["{$fieldName}.in"] = "حقل {$field['label']} يحتوي على قيمة غير صالحة";
            $messages["{$fieldName}.file"] = "حقل {$field['label']} يجب أن يكون ملفاً";
            $messages["{$fieldName}.mimes"] = "حقل {$field['label']} يجب أن يكون من نوع: pdf, jpg, jpeg, png";
            $messages["{$fieldName}.max"] = "حقل {$field['label']} يجب ألا يتجاوز 10 ميجابايت";
            $messages['payment_date.before_or_equal'] = 'تاريخ السداد لا يمكن أن يكون في يوم مستقبلي';
            $messages['partial_payment_date.before_or_equal'] = 'تاريخ السداد لا يمكن أن يكون في يوم مستقبلي';
        }
        return $messages;
    }
}
