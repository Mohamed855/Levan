<?php

namespace App\Exports\Tenants\BusinessCenter\Customer\Import\Error;

use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromCollection;

class CustomerImportErrorExport implements FromArray, WithHeadings, WithTitle, FromCollection
{
    protected array $errors;
    protected ?Portfolio $portfolio = null;

    /*
    |--------------------------------------------------------------------------
    | Modified Constructor
    |--------------------------------------------------------------------------
    | Accept errors or portfolio to determine the export type.
    */
    public function __construct($data, ?Portfolio $portfolio = null)
    {
        if (is_array($data)) {
            $this->errors = $data;
        } elseif ($data instanceof Portfolio) {
            $this->portfolio = $data;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Dynamic Title
    |--------------------------------------------------------------------------
    | Return title based on whether it's an error report or a template.
    */
    public function title(): string
    {
        return $this->portfolio ? 'نموذج استيراد العملاء' : 'أخطاء استيراد العملاء';
    }

    /*
    |--------------------------------------------------------------------------
    | Dynamic Headings
    |--------------------------------------------------------------------------
    | Return headings based on whether it's an error report or a template.
    */
    public function headings(): array
    {
        if ($this->portfolio) {

            $nameHeaders = $this->portfolio->has_split_names
                ? ['الاسم الأول *', 'اسم الأب', 'اسم الجد', 'اسم العائلة']
                : ['اسم العميل *'];

            $staticHeaders = [
                'نوع العميل * (individual/company)',
                'رقم الهوية *',
                'الجنسية (رقم البلد)',
                'الهاتف الرئيسي',
                'حالة العميل (رقم الحالة)',
                'الرقم الوظيفي',
                'مسؤول العلاقة (الرقم الوظيفي)',
                'رقم الحساب',
                'تاريخ العقد',
                'معدل العمولة',
                'تاريخ التعثر',
                'آخر نتيجة اتصال'
            ];

            $dynamicFields = $this->portfolio->portfolioFields->map(function ($field) {
                return $field->field_name . ($field->is_required ? ' *' : '');
            })->toArray();

            return array_merge($nameHeaders, $staticHeaders, $dynamicFields);
        }

        return ['رقم الصف', 'تفاصيل الخطأ'];
    }

    /*
    |--------------------------------------------------------------------------
    | Dynamic Array Data
    |--------------------------------------------------------------------------
    | Return data based on whether it's an error report or a template.
    */
    public function array(): array
    {
        if ($this->errors) {
            return array_map(function ($error) {
                return [
                    $error['row'],
                    implode(' | ', $error['errors'])
                ];
            }, $this->errors);
        }

        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | Collection Data
    |--------------------------------------------------------------------------
    | Return empty collection for template generation.
    */
    public function collection()
    {
        return collect();
    }
}
