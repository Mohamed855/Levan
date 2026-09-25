<?php

namespace App\Exports\Tenants\Reports\Customer;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class CustomersReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected $customers;
    protected $filters;
    protected $selectedFields;
    protected $rowCount = 0;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct($customers, $filters, $selectedFields = [])
    {
        $this->customers = $customers;
        $this->filters = $filters;
        $this->selectedFields = $selectedFields;

        $maxExtraPhones = $customers
            ->map(
                fn($c) => is_iterable($c->extra_phones ?? null)
                    ? count($c->extra_phones)
                    : 0
            )
            ->max();

        $this->maxExtraPhones = in_array('extra_phones', $this->selectedFields)
            ? max($maxExtraPhones, 1)
            : 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Collection
    |--------------------------------------------------------------------------
    */
    public function collection(): \Illuminate\Support\Enumerable
    {
        return $this->customers;
    }

    /*
    |--------------------------------------------------------------------------
    | Headings - عناوين الأعمدة
    |--------------------------------------------------------------------------
    */
    public function headings(): array
    {
        $headings = ['م'];

        $fieldLabels = [
            // الحقول الأساسية من جدول customers
            'name'                  => 'اسم العميل الكامل',
            'first_name'            => 'الاسم الأول',
            'father_name'           => 'اسم الأب',
            'grandfather_name'      => 'اسم الجد',
            'family_name'           => 'اسم العائلة',
            'customer_type'         => 'نوع العميل',
            'identity_number'       => 'رقم الهوية',
            'phone'                 => 'رقم الهاتف',
            'account_number'        => 'رقم الحساب',
            'commission_rate'       => 'نسبة العمولة',
            'contract_date'         => 'تاريخ العقد',
            'default_date'          => 'تاريخ التعثر',
            'created_at'            => 'تاريخ الإضافة',
            'updated_at'            => 'تاريخ آخر تحديث',

            // الحقول من العلاقات
            'company_name'          => 'اسم الشركة',
            'portfolio_name'        => 'المحفظة',
            'nationality'           => 'الجنسية',
            'manager_name'          => 'مسؤول العلاقة',
            'customer_status'       => 'حالة العميل',
            'last_contact_result'   => 'نتيجة آخر اتصال',
            'created_by'            => 'أنشأ بواسطة',

            // الحقول المالية المحسوبة
            'debt_amount'           => 'مبلغ المديونية',
            'total_credit'          => 'المدفوع',
            'remaining_debt'        => 'المتبقي',
            'days_elapsed'          => 'أيام الاسناد',
        ];

        foreach ($this->selectedFields as $field) {

            if ($field === 'primary_phone') {
                $headings[] = 'الرقم الافتراضي';
                continue;
            }

            if ($field === 'extra_phones') {
                for ($i = 1; $i <= $this->maxExtraPhones; $i++) {
                    $headings[] = "رقم إضافي {$i}";
                }
                continue;
            }

            $headings[] = $fieldLabels[$field] ?? $field;
        }


        return $headings;
    }

    /*
    |--------------------------------------------------------------------------
    | Map - تحويل البيانات للصفوف
    |--------------------------------------------------------------------------
    */
    public function map($customer): array
    {
        static $counter = 1;
        $row = [$counter++];

        foreach ($this->selectedFields as $field) {
            switch ($field) {
                // الحقول الأساسية من جدول customers
                case 'name':
                    $row[] = $customer->name;
                    break;
                case 'first_name':
                    $row[] = $customer->first_name ?: 'غير محدد';
                    break;
                case 'father_name':
                    $row[] = $customer->father_name ?: 'غير محدد';
                    break;
                case 'grandfather_name':
                    $row[] = $customer->grandfather_name ?: 'غير محدد';
                    break;
                case 'family_name':
                    $row[] = $customer->family_name ?: 'غير محدد';
                    break;
                case 'customer_type':
                    $row[] = $customer->customer_type === 'individual' ? 'فردي' : 'شركة';
                    break;
                case 'identity_number':
                    $row[] = $customer->identity_number ?: 'غير محدد';
                    break;
                case 'phone':
                    $row[] = $customer->phone ?: 'غير محدد';
                    break;
                case 'primary_phone':
                    $row[] = $customer->primary_phone ?? 'غير محدد';
                    break;

                case 'extra_phones':
                    $extraPhones = $customer->extra_phones ?? [];

                    for ($i = 0; $i < $this->maxExtraPhones; $i++) {
                        $row[] = $extraPhones[$i] ?? '';
                    }
                    break;
                case 'account_number':
                    $row[] = $customer->account_number ?: 'غير محدد';
                    break;
                case 'commission_rate':
                    $row[] = $customer->commission_rate ? $customer->commission_rate . '%' : 'غير محدد';
                    break;
                case 'contract_date':
                    $row[] = $customer->contract_date
                        ? Carbon::parse($customer->contract_date)->format('Y-m-d')
                        : 'غير محدد';
                    break;
                case 'default_date':
                    $row[] = $customer->default_date
                        ? Carbon::parse($customer->default_date)->format('Y-m-d')
                        : 'غير محدد';
                    break;
                case 'created_at':
                    $row[] = $customer->created_at->format('Y-m-d H:i');
                    break;
                case 'updated_at':
                    $row[] = $customer->updated_at->format('Y-m-d H:i');
                    break;

                // الحقول من العلاقات
                case 'company_name':
                    $row[] = $customer->company->name ?? 'غير محدد';
                    break;
                case 'portfolio_name':
                    $row[] = $customer->portfolio->name ?? 'غير محدد';
                    break;
                case 'nationality':
                    $row[] = $customer->nationalityCountry->name ?? 'غير محدد';
                    break;
                case 'manager_name':
                    $row[] = $customer->relationshipManager->name ?? 'غير محدد';
                    break;
                case 'customer_status':
                    $row[] = $customer->customerStatus->name ?? 'غير محدد';
                    break;
                case 'last_contact_result':
                    $row[] = $customer->lastContactResult->name ?? 'غير محدد';
                    break;
                case 'created_by':
                    $row[] = $customer->createdBy->name ?? 'النظام';
                    break;
                // الحقول المالية المحسوبة
                case 'debt_amount':
                    $row[] = number_format($customer->original_debt ?? 0, 2) . ' ريال';
                    break;
                case 'total_credit':
                    $row[] = number_format($customer->total_credit ?? 0, 2) . ' ريال';
                    break;
                case 'remaining_debt':
                    $row[] = number_format($customer->remaining_debt ?? 0, 2) . ' ريال';
                    break;
                case 'days_elapsed':
                    $row[] = $customer->days_elapsed ?? 0;
                    break;

                default:
                    $row[] = '';
                    break;
            }
        }

        $this->rowCount++;
        return $row;
    }

    /*
    |--------------------------------------------------------------------------
    | Styles - تنسيق الجدول
    |--------------------------------------------------------------------------
    */
    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->rowCount + 1;
        $headingsCount = count($this->headings());
        $lastColumnLetter = $this->getColumnLetter($headingsCount);

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['argb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '1F4E78'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],

            "A2:{$lastColumnLetter}{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Column Widths - عرض الأعمدة
    |--------------------------------------------------------------------------
    */
    public function columnWidths(): array
    {
        $widths = ['A' => 8];

        $fieldWidths = [
            // الحقول الأساسية من جدول customers
            'name' => 25,
            'first_name' => 15,
            'father_name' => 15,
            'grandfather_name' => 15,
            'family_name' => 15,
            'customer_type' => 12,
            'identity_number' => 18,
            'phone' => 18,
            'account_number' => 18,
            'commission_rate' => 12,
            'contract_date' => 15,
            'default_date' => 15,
            'created_at' => 18,
            'updated_at' => 18,

            // الحقول من العلاقات
            'company_name' => 20,
            'portfolio_name' => 20,
            'nationality' => 15,
            'manager_name' => 20,
            'customer_status' => 15,
            'last_contact_result' => 20,
            'created_by' => 20,

            // الحقول المالية المحسوبة
            'debt_amount' => 18,
            'total_credit' => 18,
            'remaining_debt' => 18,
            'days_elapsed' => 15,
        ];

        $columnIndex = 1;
        foreach ($this->selectedFields as $field) {
            $columnLetter = $this->getColumnLetter($columnIndex + 1);
            if ($field === 'primary_phone') {
                $widths[$columnLetter] = 18;
                continue;
            }

            if ($field === 'extra_phones') {
                for ($i = 1; $i <= $this->maxExtraPhones; $i++) {
                    $columnLetter = $this->getColumnLetter(++$columnIndex);
                    $widths[$columnLetter] = 18; // عرض موحد وواضح
                }
                continue;
            }

            $widths[$columnLetter] = $fieldWidths[$field] ?? 15;
            $columnIndex++;
        }

        return $widths;
    }

    /*
    |--------------------------------------------------------------------------
    | Events - أحداث إضافية
    |--------------------------------------------------------------------------
    */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('A2');

                $lastRow = $this->rowCount + 1;
                $lastColumnLetter = $this->getColumnLetter(count($this->headings()));

                $sheet->getStyle("A1:{$lastColumnLetter}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);


                $sheet->setAutoFilter("A1:{$lastColumnLetter}{$lastRow}");

                $sheet->setRightToLeft(true);
            },
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Title - عنوان الشيت
    |--------------------------------------------------------------------------
    */
    public function title(): string
    {
        return 'تقرير العملاء';
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    private function getColumnLetter($columnNumber): string
    {
        $letter = '';
        while ($columnNumber > 0) {
            $columnNumber--;
            $letter = chr(65 + ($columnNumber % 26)) . $letter;
            $columnNumber = intval($columnNumber / 26);
        }
        return $letter;
    }
}
