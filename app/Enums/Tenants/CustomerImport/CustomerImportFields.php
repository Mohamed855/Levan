<?php

namespace App\Enums\Tenants\CustomerImport;

enum CustomerImportFields: string
{
    // حقول الاسم المنقسمة
    case FIRST_NAME         = 'الاسم الأول';
    case FATHER_NAME        = 'اسم الأب';
    case GRANDFATHER_NAME   = 'اسم الجد';
    case FAMILY_NAME        = 'اسم العائلة';

        // حقول الاسم الواحد
    case CUSTOMER_NAME      = 'اسم العميل';

        // الحقول الثابتة الإجبارية
    case CUSTOMER_TYPE      = 'نوع العميل (individual/company)';
    case IDENTITY_NUMBER    = 'رقم الهوية/رقم الجواز';

        // الحقول الثابتة الاختيارية
    case NATIONALITY        = 'الجنسية (رقم البلد)';
    case PHONE              = 'الهاتف الرئيسي';
    case INITIAL_DEBT       = 'المديونية';
    case CUSTOMER_STATUS    = 'حالة العميل (رقم الحالة)';
    case EMPLOYEE_NUMBER    = 'الرقم الوظيفي';
    case RELATIONSHIP_MANAGER = 'مسؤول العلاقة (الرقم الوظيفي)';
    case ACCOUNT_NUMBER     = 'رقم الحساب';
    case CONTRACT_DATE      = 'تاريخ العقد';
    case COMMISSION_RATE    = 'معدل العمولة';
    case DEFAULT_DATE       = 'تاريخ التعثر';
    case LAST_CONTACT_RESULT = 'آخر نتيجة اتصال';

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /*
    |============================================================================
    |                               Functions
    |============================================================================
    */
    //  الحقول الإجبارية (تختلف حسب نوع المحفظة)
    public static function requiredHeaders(bool $hasSplitNames = false): array
    {
        if ($hasSplitNames) {
            //  إذا كانت تقبل أسماء منفصلة: الاسم الأول + اسم العائلة فقط (إجبارية)
            return [
                self::FIRST_NAME->value,        // الاسم الأول *
                self::FAMILY_NAME->value,       // اسم العائلة *

                self::IDENTITY_NUMBER->value,   // رقم الهوية *
                self::CUSTOMER_TYPE->value,     // نوع العميل *
                self::INITIAL_DEBT->value,     // المديونية *

            ];
        } else {
            //  إذا كانت لا تقبل أسماء منفصلة: اسم العميل فقط (إجبارية)
            return [
                self::CUSTOMER_NAME->value,     // اسم العميل *

                self::IDENTITY_NUMBER->value,   // رقم الهوية *
                self::CUSTOMER_TYPE->value,     // نوع العميل *
                self::INITIAL_DEBT->value,     // المديونية *
            ];
        }
    }

    //  الحقول الاختيارية (تختلف أيضاً حسب نوع المحفظة)
    public static function optionalHeaders(bool $hasSplitNames = false): array
    {
        if ($hasSplitNames) {
            //  إذا كانت تقبل أسماء منفصلة: اسم الأب + اسم الجد اختيارية
            return [
                self::FATHER_NAME->value,           // اسم الأب (اختياري)
                self::GRANDFATHER_NAME->value,      // اسم الجد (اختياري)

                self::NATIONALITY->value,
                self::PHONE->value,
                self::CUSTOMER_STATUS->value,
                self::EMPLOYEE_NUMBER->value,
                self::RELATIONSHIP_MANAGER->value,
                self::ACCOUNT_NUMBER->value,
                self::CONTRACT_DATE->value,
                self::COMMISSION_RATE->value,
                self::DEFAULT_DATE->value,
                self::LAST_CONTACT_RESULT->value,
            ];
        } else {
            //  إذا كانت لا تقبل أسماء منفصلة: جميع تفاصيل الاسم اختيارية
            return [
                self::NATIONALITY->value,
                self::PHONE->value,
                self::CUSTOMER_STATUS->value,
                self::EMPLOYEE_NUMBER->value,
                self::RELATIONSHIP_MANAGER->value,
                self::ACCOUNT_NUMBER->value,
                self::CONTRACT_DATE->value,
                self::COMMISSION_RATE->value,
                self::DEFAULT_DATE->value,
                self::LAST_CONTACT_RESULT->value,
            ];
        }
    }

    //  جميع الحقول الثابتة
    public static function staticHeaders(bool $hasSplitNames = false): array
    {
        return array_merge(self::requiredHeaders($hasSplitNames), self::optionalHeaders($hasSplitNames));
    }

    //  أسماء الحقول الإنجليزية للتحقق في Backend
    // public static function requiredFieldKeys(bool $hasSplitNames = false): array
    // {
    //     if ($hasSplitNames) {
    //         return ['first_name', 'family_name', 'identity_number', 'customer_type'];
    //     } else {
    //         return ['name', 'identity_number', 'customer_type'];
    //     }
    // }
}