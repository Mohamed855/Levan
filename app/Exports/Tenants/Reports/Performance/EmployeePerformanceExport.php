<?php

namespace App\Exports\Tenants\Reports\Performance;

use App\Support\ReportNumberFormatter;
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

class EmployeePerformanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected $employeesPerformance;
    protected $filters;
    protected $selectedFields;
    protected $rowCount = 0;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct($employeesPerformance, $filters, $selectedFields = [])
    {
        $this->employeesPerformance = collect($employeesPerformance);
        $this->filters = $filters;
        $this->selectedFields = empty($selectedFields) ? $this->getDefaultFields() : $selectedFields;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Default Fields
    |--------------------------------------------------------------------------
    */
    private function getDefaultFields(): array
    {
        return [
            'company_name',
            'employee_name',
            'customers_count',
            'total_portfolio',
            'avg_payment',
            'paid_customers',
            'overdue_tasks',
            'promises_count',
            'promises_amount',
            'promise_achievement_rate',
            'target_achievement_rate'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Collection
    |--------------------------------------------------------------------------
    */
    public function collection()
    {
        return $this->employeesPerformance;
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
            'company_name' => 'الشركة',
            'employee_name' => 'اسم الموظف',
            'customers_count' => 'عدد العملاء',
            'total_portfolio' => 'إجمالي المحفظة',
            'avg_payment' => 'متوسط السداد',
            'paid_customers' => 'عدد العملاء المسددين',
            'avg_calls' => 'متوسط المكالمات',
            'not_contacted' => 'لم يتم التواصل',
            'overdue_tasks' => 'المهام المتأخرة',
            'promises_count' => 'وعد بالسداد',
            'promises_amount' => 'مبلغ الوعد بالسداد',
            'promise_achievement_rate' => 'نسبة تحقيق الوعد',
            'target_achievement_rate' => 'نسبة تحقيق المستهدف',
        ];

        foreach ($this->selectedFields as $field) {
            $headings[] = $fieldLabels[$field] ?? $field;
        }

        return $headings;
    }

    /*
    |--------------------------------------------------------------------------
    | Map - تحويل البيانات للصفوف
    |--------------------------------------------------------------------------
    */
    public function map($employee): array
    {
        static $counter = 1;
        $row = [$counter++];

        foreach ($this->selectedFields as $field) {
            switch ($field) {
                case 'company_name':
                    $row[] = $employee['company_name'] ?? 'غير محدد';
                    break;
                case 'employee_name':
                    $row[] = $employee['employee_name'] ?? 'غير محدد';
                    break;
                case 'customers_count':
                    $row[] = $employee['customers_count'] ?? 0;
                    break;
                case 'total_portfolio':
                    $row[] = ReportNumberFormatter::format($employee['total_portfolio'] ?? 0);
                    break;
                case 'avg_payment':
                    $row[] = ReportNumberFormatter::format($employee['avg_payment'] ?? 0);
                    break;
                case 'paid_customers':
                    $row[] = $employee['paid_customers'] ?? 0;
                    break;
                case 'avg_calls':
                    $row[] = ReportNumberFormatter::format($employee['avg_calls'] ?? 0);
                    break;
                case 'not_contacted':
                    $row[] = $employee['not_contacted'] ?? 0;
                    break;
                case 'overdue_tasks':
                    $row[] = $employee['overdue_tasks'] ?? 0;
                    break;
                case 'promises_count':
                    $row[] = $employee['promises_count'] ?? 0;
                    break;
                case 'promises_amount':
                    $row[] = ReportNumberFormatter::format($employee['promises_amount'] ?? 0);
                    break;
                case 'promise_achievement_rate':
                    $row[] = ReportNumberFormatter::format($employee['promise_achievement_rate'] ?? 0) . '%';
                    break;
                case 'target_achievement_rate':
                    $row[] = ReportNumberFormatter::format($employee['target_achievement_rate'] ?? 0) . '%';
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
    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->rowCount + 1;
        $lastColumn = count($this->selectedFields) + 1;
        $lastColumnLetter = $this->getColumnLetter($lastColumn);

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
            'company_name' => 25,
            'employee_name' => 25,
            'customers_count' => 15,
            'total_portfolio' => 20,
            'avg_payment' => 18,
            'paid_customers' => 18,
            'avg_calls' => 15,
            'not_contacted' => 18,
            'overdue_tasks' => 18,
            'promises_count' => 15,
            'promises_amount' => 20,
            'promise_achievement_rate' => 20,
            'target_achievement_rate' => 22,
        ];

        $columnIndex = 1;
        foreach ($this->selectedFields as $field) {
            $columnLetter = $this->getColumnLetter($columnIndex + 1);
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
                $lastColumn = count($this->selectedFields) + 1;
                $lastColumnLetter = $this->getColumnLetter($lastColumn);

                $summaryRow = $lastRow + 2;
                $totalEmployees = $this->employeesPerformance->count();
                $totalCustomers = $this->employeesPerformance->sum('customers_count');
                $totalPortfolio = $this->employeesPerformance->sum('total_portfolio');

                $summaryText = "إجمالي الموظفين: " . ReportNumberFormatter::format($totalEmployees) .
                    " | إجمالي العملاء: " . ReportNumberFormatter::format($totalCustomers) .
                    " | إجمالي المحفظة: " . ReportNumberFormatter::format($totalPortfolio) . " ريال";

                $sheet->setCellValue("A{$summaryRow}", $summaryText);
                $sheet->mergeCells("A{$summaryRow}:{$lastColumnLetter}{$summaryRow}");

                $sheet->getStyle("A1:{$lastColumnLetter}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getStyle("A{$summaryRow}:{$lastColumnLetter}{$summaryRow}")->applyFromArray([
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
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['argb' => '1F4E78'],
                        ],
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
        return 'تقرير أداء الموظفين';
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
