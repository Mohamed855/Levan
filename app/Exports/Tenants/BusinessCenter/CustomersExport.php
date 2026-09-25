<?php

namespace App\Exports\Tenants\BusinessCenter;

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

class CustomersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    private const MANDATORY_FIELD = 'name'; // إلزامي دائمًا
    private const FIELD_LABELS    = [
        'name'             => 'اسم العميل',
        'customer_type'    => 'نوع العميل',
        'identity_number'  => 'رقم الهوية',
        'phone'            => 'رقم الهاتف',
        'account_number'   => 'رقم الحساب',
        'company_name'     => 'الشركة',
        'portfolio_name'   => 'المحفظة',
        'nationality'      => 'الجنسية',
        'customer_status'  => 'حالة العميل',
        'manager_name'     => 'مسؤول العلاقة',
        'commission_rate'  => 'نسبة العمولة',
        'contract_date'    => 'تاريخ العقد',
        'default_date'     => 'تاريخ التعثر',
        'debt_amount'      => 'إجمالي المديونية',
        'total_credit'     => 'إجمالي المدفوع',
        'remaining_debt'   => 'المتبقي',
        'created_by'       => 'أنشئ بواسطة',
        'created_at'       => 'تاريخ الإضافة',
    ];

    private const COLUMN_WIDTHS = [
        'name'             => 25,
        'customer_type'    => 12,
        'identity_number'  => 18,
        'phone'            => 18,
        'account_number'   => 18,
        'company_name'     => 20,
        'portfolio_name'   => 20,
        'nationality'      => 15,
        'customer_status'  => 15,
        'manager_name'     => 20,
        'commission_rate'  => 12,
        'contract_date'    => 15,
        'default_date'     => 15,
        'debt_amount'      => 18,
        'total_credit'     => 18,
        'remaining_debt'   => 18,
        'created_by'       => 20,
        'created_at'       => 18,
    ];

    protected $customers;
    protected array $selectedFields;
    protected $rowCount = 0;

    public function __construct($customers, array $selectedFields = [])
    {
        $this->customers = $customers;

        if (empty($selectedFields)) {
            $selectedFields = array_keys(self::FIELD_LABELS);
        }

        if (!in_array(self::MANDATORY_FIELD, $selectedFields, true)) {
            array_unshift($selectedFields, self::MANDATORY_FIELD);
        }

        // احترام الترتيب القياسي للحقول بدلاً من ترتيب وصولها من الطلب
        $this->selectedFields = array_values(array_intersect(array_keys(self::FIELD_LABELS), $selectedFields));
    }

    public function collection(): \Illuminate\Support\Enumerable
    {
        return $this->customers;
    }

    public function headings(): array
    {
        $headings = ['م'];

        foreach ($this->selectedFields as $field) {
            $headings[] = self::FIELD_LABELS[$field];
        }

        return $headings;
    }

    public function map($customer): array
    {
        static $counter = 1;

        $row = [$counter++];

        foreach ($this->selectedFields as $field) {
            $row[] = $this->resolveFieldValue($customer, $field);
        }

        $this->rowCount++;
        return $row;
    }

    private function resolveFieldValue($customer, string $field)
    {
        switch ($field) {
            case 'name':
                return $customer->name;
            case 'customer_type':
                return $customer->customer_type === 'individual' ? 'فردي' : 'شركة';
            case 'identity_number':
                return $customer->identity_number ?: 'غير محدد';
            case 'phone':
                return $customer->phones->firstWhere('is_primary', true)?->phone
                    ?? $customer->phones->first()?->phone
                    ?? $customer->phone
                    ?? 'غير محدد';
            case 'account_number':
                return $customer->account_number ?: 'غير محدد';
            case 'company_name':
                return $customer->company->name ?? 'غير محدد';
            case 'portfolio_name':
                return $customer->portfolio->name ?? 'غير محدد';
            case 'nationality':
                return $customer->nationalityCountry->name ?? 'غير محدد';
            case 'customer_status':
                return $customer->customerStatus->name ?? 'غير محدد';
            case 'manager_name':
                return $customer->relationshipManager->name ?? 'غير محدد';
            case 'commission_rate':
                return $customer->commission_rate ? $customer->commission_rate . '%' : 'غير محدد';
            case 'contract_date':
                return $customer->contract_date ? Carbon::parse($customer->contract_date)->format('Y-m-d') : 'غير محدد';
            case 'default_date':
                return $customer->default_date ? Carbon::parse($customer->default_date)->format('Y-m-d') : 'غير محدد';
            case 'debt_amount':
                return number_format((float) ($customer->total_debit ?? 0), 2) . ' ريال';
            case 'total_credit':
                return number_format((float) ($customer->total_credit ?? 0), 2) . ' ريال';
            case 'remaining_debt':
                return number_format((float) ($customer->total_debit ?? 0) - (float) ($customer->total_credit ?? 0), 2) . ' ريال';
            case 'created_by':
                return $customer->createdBy->name ?? 'النظام';
            case 'created_at':
                return $customer->created_at->format('Y-m-d H:i');
            default:
                return '';
        }
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->rowCount + 1;
        $lastColumnLetter = $this->getColumnLetter(count($this->headings()));

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

    public function columnWidths(): array
    {
        $widths = ['A' => 8];

        $columnIndex = 1;
        foreach ($this->selectedFields as $field) {
            $columnLetter = $this->getColumnLetter(++$columnIndex);
            $widths[$columnLetter] = self::COLUMN_WIDTHS[$field] ?? 15;
        }

        return $widths;
    }

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
                ]);

                $sheet->setAutoFilter("A1:{$lastColumnLetter}{$lastRow}");

                $sheet->setRightToLeft(true);
            },
        ];
    }

    public function title(): string
    {
        return 'بيانات العملاء';
    }

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
