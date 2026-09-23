<?php

namespace App\Exports\Tenants\Reports\Company;

use App\Support\ReportNumberFormatter;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CompanyReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected $companies;
    protected $filters;
    protected $selectedFields;
    protected $rowCount = 0;

    public function __construct($companies, $filters, $selectedFields = [])
    {
        $this->companies = collect($companies);
        $this->filters = $filters;
        $this->selectedFields = empty($selectedFields) ? $this->getDefaultFields() : $selectedFields;
    }

    private function getDefaultFields(): array
    {
        return [
            'company_name',
            'status',
            'total_portfolios',
            'customers_count',
            'total_debt',
            'total_credit',
            'collection_rate',
            'target_percentage',
            'created_at',
        ];
    }

    public function collection()
    {
        return $this->companies;
    }

    public function headings(): array
    {
        $headings = ['م'];

        $fieldLabels = [
            'company_name' => 'اسم الشركة',
            'status' => 'الحالة',
            'total_portfolios' => 'إجمالي المحافظ',
            'active_portfolios' => 'المحافظ النشطة',
            'inactive_portfolios' => 'المحافظ غير النشطة',
            'customers_count' => 'عدد العملاء',
            'total_debt' => 'إجمالي الديون',
            'total_credit' => 'إجمالي التحصيل',
            'collection_rate' => 'نسبة التحصيل',
            'target_percentage' => 'الهدف %',
            'created_at' => 'تاريخ الإنشاء',
        ];

        foreach ($this->selectedFields as $field) {
            $headings[] = $fieldLabels[$field] ?? $field;
        }

        return $headings;
    }

    public function map($company): array
    {
        static $counter = 1;
        $row = [$counter++];

        foreach ($this->selectedFields as $field) {
            switch ($field) {
                case 'company_name':
                    $row[] = $company['company_name'] ?? 'غير محدد';
                    break;
                case 'status':
                    $row[] = ($company['status'] ?? '') === 'active' ? 'نشط' : 'غير نشط';
                    break;
                case 'total_portfolios':
                    $row[] = $company['total_portfolios'] ?? 0;
                    break;
                case 'active_portfolios':
                    $row[] = $company['active_portfolios'] ?? 0;
                    break;
                case 'inactive_portfolios':
                    $row[] = $company['inactive_portfolios'] ?? 0;
                    break;
                case 'customers_count':
                    $row[] = $company['customers_count'] ?? 0;
                    break;
                case 'total_debt':
                    $row[] = ReportNumberFormatter::format($company['total_debt'] ?? 0);
                    break;
                case 'total_credit':
                    $row[] = ReportNumberFormatter::format($company['total_credit'] ?? 0);
                    break;
                case 'collection_rate':
                    $row[] = ReportNumberFormatter::format($company['collection_rate'] ?? 0) . '%';
                    break;
                case 'target_percentage':
                    $targetPercentage = $company['target_percentage'] ?? null;
                    $row[] = $targetPercentage === null
                        ? 'بدون هدف'
                        : ReportNumberFormatter::format($targetPercentage) . '%';
                    break;
                case 'created_at':
                    $row[] = $company['created_at'] ?? '';
                    break;
                default:
                    $row[] = '';
                    break;
            }
        }

        $this->rowCount++;
        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = max($this->rowCount + 1, 2);
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

    public function columnWidths(): array
    {
        $widths = ['A' => 8];

        $fieldWidths = [
            'company_name' => 30,
            'status' => 14,
            'total_portfolios' => 18,
            'active_portfolios' => 18,
            'inactive_portfolios' => 22,
            'customers_count' => 15,
            'total_debt' => 20,
            'total_credit' => 20,
            'collection_rate' => 18,
            'target_percentage' => 14,
            'created_at' => 18,
        ];

        $columnIndex = 1;
        foreach ($this->selectedFields as $field) {
            $columnLetter = $this->getColumnLetter($columnIndex + 1);
            $widths[$columnLetter] = $fieldWidths[$field] ?? 15;
            $columnIndex++;
        }

        return $widths;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');

                $lastRow = max($this->rowCount + 1, 2);
                $lastColumn = count($this->selectedFields) + 1;
                $lastColumnLetter = $this->getColumnLetter($lastColumn);

                $summaryRow = $lastRow + 2;
                $totalCompanies = $this->companies->count();
                $totalCustomers = $this->companies->sum('customers_count');
                $totalDebt = $this->companies->sum('total_debt');
                $totalCredit = $this->companies->sum('total_credit');

                $summaryText = "إجمالي الشركات: " . ReportNumberFormatter::format($totalCompanies) .
                    " | إجمالي العملاء: " . ReportNumberFormatter::format($totalCustomers) .
                    " | إجمالي الديون: " . ReportNumberFormatter::format($totalDebt) . " ريال" .
                    " | إجمالي التحصيل: " . ReportNumberFormatter::format($totalCredit) . " ريال";

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

    public function title(): string
    {
        return 'تقرير الشركات';
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
