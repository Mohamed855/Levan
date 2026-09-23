<?php

namespace App\Exports\Tenants\Reports\Performance;

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

/*
|--------------------------------------------------------------------------
| ✅ Performance Report Export - UPDATED
|--------------------------------------------------------------------------
*/

class PerformanceReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected $data;
    protected $filters;
    protected $selectedFields;
    protected $reportType;
    protected $rowCount = 0;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct($data, $filters, $selectedFields = [], $reportType = 'companies')
    {
        $this->data = collect($data);
        $this->filters = $filters;
        $this->selectedFields = empty($selectedFields) ? $this->getDefaultFields() : $selectedFields;
        $this->reportType = $reportType;
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ Get Default Fields - بدون total_collected
    |--------------------------------------------------------------------------
    */
    private function getDefaultFields(): array
    {
        return [
            'company_name',
            'customers_count',
            'total_portfolio',
            'total_payment',
            'achievement_rate'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Collection
    |--------------------------------------------------------------------------
    */
    public function collection()
    {
        return $this->data;
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ Headings - التسميات المحدثة
    |--------------------------------------------------------------------------
    */
    public function headings(): array
    {
        $headings = ['م'];

        $fieldLabels = [
            'company_name' => 'الشركة',
            'customers_count' => 'عدد العملاء',
            'total_portfolio' => 'إجمالي المديونية', // ✅ تم التحديث
            'customer_target' => 'المستهدف',
            'total_payment' => 'إجمالي السداد',
            'total_settlements' => 'إجمالي التسويات',
            // ✅ تم حذف total_collected
            'achievement_rate' => 'نسبة الإنجاز',
        ];

        foreach ($this->selectedFields as $field) {
            $headings[] = $fieldLabels[$field] ?? $field;
        }

        return $headings;
    }

    /*
    |--------------------------------------------------------------------------
    | ✅ Map - بدون total_collected
    |--------------------------------------------------------------------------
    */
    public function map($row): array
    {
        static $counter = 1;
        $mappedRow = [$counter++];

        foreach ($this->selectedFields as $field) {
            switch ($field) {
                case 'company_name':
                    $mappedRow[] = $row['company_name'] ?? 'غير محدد';
                    break;
                    
                case 'customers_count':
                    $mappedRow[] = $row['customers_count'] ?? 0;
                    break;
                    
                case 'total_portfolio':
                    $mappedRow[] = number_format($row['total_portfolio'] ?? 0, 2);
                    break;
                    
                case 'customer_target':
                    $mappedRow[] = number_format($row['customer_target'] ?? 0, 2);
                    break;
                    
                case 'total_payment':
                    $mappedRow[] = number_format($row['total_payment'] ?? 0, 2);
                    break;
                    
                case 'total_settlements':
                    $mappedRow[] = number_format($row['total_settlements'] ?? 0, 2);
                    break;
                    
                // ✅ تم حذف total_collected
                    
                case 'achievement_rate':
                    $mappedRow[] = number_format($row['achievement_rate'] ?? 0, 2) . '%';
                    break;
                    
                default:
                    $mappedRow[] = '';
                    break;
            }
        }

        $this->rowCount++;
        return $mappedRow;
    }

    /*
    |--------------------------------------------------------------------------
    | Styles
    |--------------------------------------------------------------------------
    */
    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->rowCount + 1;
        $lastColumn = count($this->selectedFields) + 1;
        $lastColumnLetter = $this->getColumnLetter($lastColumn);

        return [
            // ✅ تنسيق الرأس
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

            // ✅ تنسيق البيانات
            "A2:{$lastColumnLetter}{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Column Widths
    |--------------------------------------------------------------------------
    */
    public function columnWidths(): array
    {
        $widths = ['A' => 8];

        $fieldWidths = [
            'company_name' => 30,
            'customers_count' => 15,
            'total_portfolio' => 20,
            'customer_target' => 18,
            'total_payment' => 20,
            'total_settlements' => 20,
            // ✅ تم حذف total_collected
            'achievement_rate' => 15,
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
    | ✅ Events - الملخص المحدث
    |--------------------------------------------------------------------------
    */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ✅ تجميد الصف الأول
                $sheet->freezePane('A2');

                $lastRow = $this->rowCount + 1;
                $lastColumn = count($this->selectedFields) + 1;
                $lastColumnLetter = $this->getColumnLetter($lastColumn);

                // ✅ صف الملخص
                $summaryRow = $lastRow + 2;
                
                $totalCompanies = $this->data->count();
                $totalCustomers = $this->data->sum('customers_count');
                $totalPortfolio = $this->data->sum('total_portfolio');
                $totalPayment = $this->data->sum('total_payment');
                $totalSettlements = $this->data->sum('total_settlements');
                $avgAchievement = $this->data->avg('achievement_rate');

                $summaryText = "📊 الملخص الإجمالي: " .
                    "الشركات: " . number_format($totalCompanies) . " | " .
                    "العملاء: " . number_format($totalCustomers) . " | " .
                    "المديونية: " . number_format($totalPortfolio, 0) . " ريال | " .
                    "السداد: " . number_format($totalPayment, 0) . " ريال | " .
                    "التسويات: " . number_format($totalSettlements, 0) . " ريال | " .
                    "متوسط الإنجاز: " . number_format($avgAchievement, 1) . "%";

                $sheet->setCellValue("A{$summaryRow}", $summaryText);
                $sheet->mergeCells("A{$summaryRow}:{$lastColumnLetter}{$summaryRow}");

                // ✅ الحدود
                $sheet->getStyle("A1:{$lastColumnLetter}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);

                // ✅ تنسيق صف الملخص
                $sheet->getStyle("A{$summaryRow}:{$lastColumnLetter}{$summaryRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                        'color' => ['argb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => '4CC1BB'],
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

                // ✅ فلترة تلقائية
                $sheet->setAutoFilter("A1:{$lastColumnLetter}{$lastRow}");
                
                // ✅ اتجاه RTL
                $sheet->setRightToLeft(true);

                // ✅ ارتفاع الصفوف
                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getRowDimension($summaryRow)->setRowHeight(25);
            },
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    */
    public function title(): string
    {
        return 'تقرير أداء الشركات';
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: Get Column Letter
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