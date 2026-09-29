<?php

namespace App\Services\Tenants\BusinessCenter\Customer\Import;

use App\Models\Tenants\GeneralSetting\Country\SettingsCountry;
use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use App\Models\Tenants\UserMangment\User\User;

class CustomerFieldMappingService
{

    /*
    |--------------------------------------------------------------------------
    | البحث عن الحقل الأساسي المطابق
    |--------------------------------------------------------------------------
    */
    public function findMatchingCoreField(string $header): ?array
    {
        $normalizedHeader = $this->normalizeHeader($header);

        if ($this->isPhoneField($header)) {
            return null;   // لا تبحث هنا
        }

        $coreFields = $this->getCoreCustomerFields();

        foreach ($coreFields as $arabicKey => $fieldData) {

            // مطابقة المفتاح الرئيسي
            if ($normalizedHeader === $this->normalizeHeader($arabicKey)) {
                $result = [
                    'field'     => $fieldData['field'],
                    'category'  => 'core',
                    'type'      => 'static'
                ];

                // إذا كان الحقل يحتوي على  (source_field) ضفه في النتيجة
                if (isset($fieldData['source_field'])) {
                    $result['source_field'] = $fieldData['source_field'];
                }

                return $result;
            }

            // مطابقة أي من الـ variations
            foreach ($fieldData['variations'] as $variation) {
                if ($normalizedHeader === $this->normalizeHeader($variation)) {
                    $result = [
                        'field'     => $fieldData['field'],
                        'category'  => 'core',
                        'type'      => 'static'
                    ];

                    if (isset($fieldData['source_field'])) {
                        $result['source_field'] = $fieldData['source_field'];
                    }

                    return $result;
                }
            }
        }

        //  المرحلة 2: مطابقة جزئية (partial match) - فقط للنصوص الطويلة
        foreach ($coreFields as $arabicKey => $fieldData) {
            foreach ($fieldData['variations'] as $variation) {
                $normalizedVariation = $this->normalizeHeader($variation);

                // فقط إذا كان النص أطول من 4 أحرف
                if (mb_strlen($normalizedVariation, 'UTF-8') > 4) {
                    if (mb_strpos($normalizedHeader, $normalizedVariation) !== false) {
                        $result = [
                            'field'     => $fieldData['field'],
                            'category'  => 'core',
                            'type'      => 'static'
                        ];

                        if (isset($fieldData['source_field'])) {
                            $result['source_field'] = $fieldData['source_field'];
                        }

                        return $result;
                    }
                }
            }
        }

        return null;
    }
    /*
    |--------------------------------------------------------------------------
    | فحص إذا كان رأس العمود غير صالح (فارغ، رموز فقط، إلخ)
    |--------------------------------------------------------------------------
    | Added based on the refined analysis logic.
    */
    public function isInvalidHeader(string $header): bool
    {
        $invalidPatterns = ['—', '-', '', 'فارغ', 'empty', 'null', '###'];
        $cleanHeader = strtolower(trim($header)); // Use strtolower for case-insensitive check

        // Check if header is empty or consists only of invalid patterns
        if (empty($cleanHeader) || in_array($cleanHeader, $invalidPatterns)) {
            return true;
        }

        // Check if header consists only of whitespace or special characters often used as separators
        if (preg_match('/^[\s\-_#@$%^&*()]+$/', $cleanHeader)) {
            return true;
        }

        // Optional: Check if the header is purely numeric, which might indicate bad data or structure
        // if (is_numeric($cleanHeader)) {
        //     return true;
        // }

        // Optional: Check minimum length if required (e.g., headers must be at least 1 or 2 characters)
        // Adjust the minimum length as needed. Sometimes single letters are valid headers (e.g., 'م' for مسلسل).
        // if (mb_strlen($cleanHeader, 'UTF-8') < 1) { // Check for at least 1 character
        //    return true;
        // }


        return false; // Header seems valid
    }

    /*
    |--------------------------------------------------------------------------
    | تطبيع النص للمقارنة
    |--------------------------------------------------------------------------
    */
    public function normalizeHeader(string $str): string
    {
        $clean = trim($str); // حذف المسافات من بداية ونهاية النص.
        $clean = preg_replace('/\s+/', ' ', $clean);  // تستبدل أي مسافات كثيرة داخل النص بمسافة واحدة فقط.
        $clean = str_replace(['*', '(مطلوب)', '(إجباري)'], '', $clean); // تحذف علامات الإلزام من العنوان.
        $clean = str_replace(['أ', 'إ', 'آ'], 'ا', $clean); // توحد الحروف العربية المختلفة إلى حرف واحد.
        $clean = str_replace('ة', 'ه', $clean); // توحد حرف التاء المربوطة إلى الهاء.
        return mb_strtolower(trim($clean), 'UTF-8'); // تحذف المسافات مرة ثانية بعد التنظيف، وتحول النص إلى حروف صغيرة مع دعم العربية و UTF-8.
    }

    /*
    |--------------------------------------------------------------------------
    | فحص حقل الهاتف الإضافي
    |--------------------------------------------------------------------------
    */
    public function isPhoneField(string $header): bool
    {
        $normalizedHeader = $this->normalizeHeader($header);

        // فحص الأنماط المختلفة للهواتف الإضافية
        $phonePatterns = [
            '/هاتف\s*اضافي\s*\d+/u',
            '/جوال\s*اضافي\s*\d+/u',
            '/هاتف\s*رئيسي\s*\d+/u',
            '/الهاتف\s*الرئيسي\s*\d+/u',
            '/هاتف\s*\d+/u',
            '/جوال\s*\d+/u'
        ];

        foreach ($phonePatterns as $pattern) {
            if (preg_match($pattern, $normalizedHeader)) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | تحليل حقل الهاتف الإضافي
    |--------------------------------------------------------------------------
    */
    public function parsePhoneField(string $header): ?array
    {
        $normalizedHeader = $this->normalizeHeader($header);

        if (preg_match('/(هاتف|جوال|رقم هاتف)\s*(اضافي|إضافي)?\s*(\d+)/u', $normalizedHeader, $matches)) {
            $index = (int)$matches[3];

            if (strpos($normalizedHeader, 'نوع') !== false) {
                $type = 'type';
            } elseif (strpos($normalizedHeader, 'ملاحظ') !== false) {
                $type = 'notes';
            } else {
                $type = 'phone';
            }

            return [
                'index' => $index,
                'type'  => $type
            ];
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | فحص صحة اسم الحقل الجديد
    |--------------------------------------------------------------------------
    */
    public function shouldCreateNewField(string $header): bool
    {
        $excludedPatterns = [
            // 'تسلسل',
            // 'serial',
            // 'index',
            // 'فارغ',
            // 'empty',
            // 'null',
            // '###',
            // '—',
            // '-',
            // 'row labels',
            // 'column labels',
            // 'grand total',
            // 'values',
            // 'إجمالي',
            // 'المجموع',
            // 'مجموع',
            // 'عدد',
            // 'متوسط',
            // 'الحد الأدنى',
            // 'الحد الأقصى',
            // 'تسميات الصفوف',
            // 'تسميات الأعمدة',
            // 'الإجماليات',
            // 'المجاميع'
        ];

        $headerLower = strtolower(trim($header));

        if (strlen(trim($header)) < 2) {
            return false;
        }

        foreach ($excludedPatterns as $pattern) {
            if (stripos($headerLower, $pattern) !== false) {
                return false;
            }
        }

        if (is_numeric(trim($header))) {
            return false;
        }

        if (preg_match('/^[\s\-_#@$%^&*()]+/', trim($header))) {
            return false;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | تحديد نوع الحقل تلقائياً
    |--------------------------------------------------------------------------
    */
    public function detectFieldType(string $fieldName): string
    {
        $fieldNameLower = strtolower($fieldName);

        if (
            strpos($fieldNameLower, 'تاريخ') !== false ||
            strpos($fieldNameLower, 'date') !== false ||
            strpos($fieldNameLower, 'يوم') !== false ||
            strpos($fieldNameLower, 'شهر') !== false ||
            strpos($fieldNameLower, 'سنة') !== false
        ) {
            return 'date';
        }

        if (
            strpos($fieldNameLower, 'مبلغ') !== false ||
            strpos($fieldNameLower, 'سعر') !== false ||
            strpos($fieldNameLower, 'قيمة') !== false ||
            strpos($fieldNameLower, 'amount') !== false ||
            strpos($fieldNameLower, 'price') !== false ||
            strpos($fieldNameLower, 'رقم') !== false ||
            strpos($fieldNameLower, 'عدد') !== false ||
            strpos($fieldNameLower, 'كمية') !== false ||
            strpos($fieldNameLower, 'number') !== false ||
            strpos($fieldNameLower, 'خدمة') !== false
        ) {
            return 'numeric';
        }

        return 'text';
    }

    /*
    |--------------------------------------------------------------------------
    | تطبيع نوع العميل
    |--------------------------------------------------------------------------
    */
    public function normalizeCustomerType(string $value): string
    {
        $normalized = strtolower(trim($value));

        $typeMapping = [
            'فردي'          => 'individual',
            'فرد'           => 'individual',
            'شخص'           => 'individual',
            'individual'    => 'individual',

            'مؤسسة'         => 'company',
            'شركة'          => 'company',
            'company'       => 'company'
        ];

        foreach ($typeMapping as $key => $type) {
            if (stripos($normalized, $key) !== false) {
                return $type;
            }
        }

        return 'individual';
    }

    /*
    |--------------------------------------------------------------------------
    | تنظيف رقم الهوية
    |--------------------------------------------------------------------------
    */
    // public function cleanIdentityNumber(string $value): string
    // {
    //     return preg_replace('/[^0-9]/', '', trim($value));
    // }

    public function cleanIdentityNumber(string $value): string
    {
        $trimmed = trim($value);

        $cleaned = preg_replace('/[^0-9a-zA-Z]/', '', $trimmed);

        return $cleaned ?? '';
    }

    public function isValidIdentityNumber(string $value): bool
    {
        $cleaned = $this->cleanIdentityNumber($value);
        $length = mb_strlen($cleaned, 'UTF-8');

        // فحص الطول (5-15)
        if ($length < 5 || $length > 15) {
            return false;
        }

        // فحص عدم الفراغ
        if (empty($cleaned)) {
            return false;
        }

        return true;
    }

    //
    // تتحقق أن القيمة أرقام فقط (تسمح بفاصلة الآلاف مثل 1,000)
    public function isValidDebtAmount(string $value): bool
    {
        $trimmed = trim($value);
        if ($trimmed === '') return false;
        $normalized = str_replace(',', '', $trimmed);
        return is_numeric($normalized);
    }

    // تنظف القيمة وتحولها لرقم عشري
    public function cleanDebtAmount(string $value): float
    {
        $trimmed = trim($value);
        $normalized = str_replace(',', '', $trimmed);
        $cleaned = preg_replace('/[^0-9.\-]/', '', $normalized);
        return $cleaned !== '' && $cleaned !== '-' && $cleaned !== '.' ? (float) $cleaned : 0;
    }

    /*
    |--------------------------------------------------------------------------
    | تنظيف رقم الهاتف
    |--------------------------------------------------------------------------
    */
    public function cleanPhoneNumber(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', trim($phone));
    }

    /*
    |--------------------------------------------------------------------------
    | التحقق من صحة رقم الهاتف
    |--------------------------------------------------------------------------
    */
    public function isValidPhoneNumber(string $phone): bool
    {
        $patterns = [
            '/^966[0-9]{9}$/',      // 966xxxxxxxxx
            '/^\+966[0-9]{9}$/',    // +966xxxxxxxxx
            '/^05[0-9]{8}$/',       // 05xxxxxxxx
            '/^5[0-9]{8}$/',        // 5xxxxxxxx
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $phone)) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | معالجة قيم التاريخ من Excel
    |--------------------------------------------------------------------------
    */
    public function processDateValue(string $value): string
    {
        if (is_numeric($value)) {
            try {
                $date = null;
                if (strlen($value) === 8) {
                    $date = \DateTime::createFromFormat('Ymd', $value);
                } elseif (strlen($value) === 5) {
                    $unix_timestamp = ($value - 25569) * 86400;
                    $date = new \DateTime("@$unix_timestamp");
                }

                if ($date && $date !== false) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // فشل تحويل التاريخ الرقمي
            }
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, trim($value));
            if ($date && $date->format($format) === trim($value)) {
                return $date->format('Y-m-d');
            }
        }

        return $value;
    }



    /*
    |============================================================================
    |============================================================================
    |                         Private Helper Methods
    |============================================================================
    |============================================================================
    */
    private function getCoreCustomerFields(): array
    {
        return [
            // حقول الاسم
            'اسم العميل' => [
                'field' => 'name',
                'variations' => [
                    'اسم العميل',
                    'customer name',
                    'name',
                    'customer_name'
                ]
            ],
            'الاسم الأول' => [
                'field' => 'first_name',
                'variations' => [
                    'الاسم الأول',
                    'الاسم الاول',
                    'first name',
                    'first_name'
                ]
            ],
            'اسم الأب' => [
                'field' => 'father_name',
                'variations' => [
                    'اسم الأب',
                    'اسم الاب',
                    'father name',
                    'father_name'
                ]
            ],
            'اسم الجد' => [
                'field' => 'grandfather_name',
                'variations' => [
                    'اسم الجد',
                    'grandfather name',
                    'grandfather_name'
                ]
            ],
            'اسم العائلة' => [
                'field' => 'family_name',
                'variations' => [
                    'اسم العائلة',
                    'family name',
                    'family_name'
                ]
            ],

            // نوع العميل
            'نوع العميل' => [
                'field' => 'customer_type',
                'variations' => [
                    'نوع العميل',
                    'نوع الهوية',
                    'customer type',
                    'customer_type',
                    'فردي',
                    'شركة',
                    'individual',
                    'company'
                ]
            ],

            // رقم الهوية
            'رقم الهوية' => [
                'field' => 'identity_number',
                'variations' => [
                    'رقم الهوية',
                    'رقم معرف العميل',
                    'معرف العميل',
                    'الاقامة',
                    'الهوية',
                    'identity number',
                    'identity_number'
                ]
            ],

            // الجنسية
            'الجنسية' => [
                'field' => 'nationality',
                'variations' => [
                    'الجنسية',
                    'nationality'
                ]
            ],

            'الهاتف الرئيسي' => [
                'field' => 'phone',
                'variations' => [
                    'الهاتف الرئيسي',
                    'الهاتف الاساسي',
                    'الهاتف الأساسي',
                    'الهاتف',
                    'هاتف',
                    'phone',
                    'mobile',
                    'هاتف اساسي',
                    'الهاتف الاساسي'
                ]
            ],

            'حالة العميل' => [
                'field' => 'customer_status_id',
                'variations' => [
                    'حالة العميل',
                    'customer status',
                    'customer_status_id'
                ]
            ],

            // مسؤول العلاقة
            // 'مسؤول العلاقة' => [
            //     'field' => 'relationship_manager_id',
            //     'variations' => [
            //         'مسؤول العلاقة',
            //         'مدير العلاقة',
            //         'relationship manager',
            //         'relationship_manager_id'
            //     ]
            // ],

            // رقم الحساب
            'رقم الحساب' => [
                'field' => 'account_number',
                'variations' => [
                    'رقم الحساب',
                    'الحساب',
                    'account number',
                    'account_number',
                    'حساب'
                ]
            ],

            'المديونية' => [
                'field' => 'initial_debt',
                'variations' => [
                    'المديونية',
                    'مبلغ الدين',
                    'الرصيد المتاخر',
                    'إجمالى المديونية',
                    'اجمالي المديونية',
                    'debt amount',
                    'المديونية الاولية',
                    'المديونية الأولية',
                    'initial_debt'
                ]
            ],

            // تاريخ العقد
            'تاريخ العقد' => [
                'field' => 'contract_date',
                'variations' => [
                    'تاريخ العقد',
                    'contract date',
                    'contract_date'
                ]
            ],

            // معدل العمولة
            'معدل العمولة' => [
                'field' => 'commission_rate',
                'variations' => [
                    'معدل العمولة',
                    'commission rate',
                    'commission_rate'
                ]
            ],

            // تاريخ التعثر
            'تاريخ التعثر' => [
                'field' => 'default_date',
                'variations' => [
                    'تاريخ التعثر',
                    'default date',
                    'default_date'
                ]
            ],

            'الرقم الوظيفي' => [
                'field' => 'relationship_manager_id',
                'source_field' => 'employee_number',
                'variations' => [
                    'الرقم الوظيفي',
                    'رقم الموظف',
                    'رقم وظيفي',
                    'employee number',
                    'emp number',
                    'emp_number'
                ]
            ],

            'آخر نتيجة اتصال' => [
                'field' => 'last_contact_result_id',
                'variations' => [
                    'آخر نتيجة اتصال',
                    'نتيجة الاتصال',
                    'last contact result',
                    'contact result'
                ]
            ]
        ];
    }

















    /*
    |--------------------------------------------------------------------------
    | تحديد فئة الحقل (ثابت أم ديناميكي)
    |--------------------------------------------------------------------------
    */
    // public function determineFieldCategory(string $header): string
    // {
    //     if ($this->findMatchingCoreField($header)) {
    //         return 'static';
    //     }

    //     if ($this->isPhoneField($header)) {
    //         return null;
    //     }

    //     if ($this->isEmployeeNumberField($header)) {
    //         return 'employee_number';
    //     }

    //     if ($this->isDebtAmountField($header)) {
    //         return 'debt_amount';
    //     }

    //     return 'dynamic';
    // }


    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    // public function isEmployeeNumberField(string $header): bool
    // {
    //     $normalizedHeader = $this->normalizeHeader($header);

    //     $employeePatterns = [
    //         'رقم وظيفي',
    //         'الرقم الوظيفي',
    //         'رقم الموظف',
    //         'employee number',
    //         'emp number',
    //         'emp_number',
    //         'employee_number'
    //     ];

    //     foreach ($employeePatterns as $pattern) {
    //         if ($normalizedHeader === $this->normalizeHeader($pattern)) {
    //             return true;
    //         }
    //         if (mb_strpos($normalizedHeader, $this->normalizeHeader($pattern)) !== false) {
    //             return true;
    //         }
    //     }

    //     return false;
    // }


    /*
    |============================================================================
    |============================================================================
    |                               Functions
    |============================================================================
    |============================================================================
    */
    // التحقق من صحة الجنسية (رقم البلد)
    public function isValidNationality(int $nationalityId): bool
    {
        return SettingsCountry::where('id', $nationalityId)->where('status', 'active')->exists();
    }

    // حالة العميل
    public function isValidCustomerStatus(int $statusId): bool
    {
        return SettingsCustomerStatus::where('id', $statusId)->where('status', 'active')->exists();
    }

    // مسؤول العلاقة
    public function getRelationshipManagerId($userNumber): ?int
    {
        $user = User::where('employee_number', $userNumber)->where('status', 'active')->first();
        return $user ? $user->id : null;
    }
}