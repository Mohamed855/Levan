<?php

namespace App\Exports\Tenants\Reports\Financial;

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

class FinancialReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected $collections;
    protected $filters;
    protected $selectedFields;
    protected $rowCount = 0;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct($collections, $filters, $selectedFields = [])
    {
        $this->collections = $collections;
        $this->filters = $filters;
        $this->selectedFields = $selectedFields;
    }

    /*
    |--------------------------------------------------------------------------
    | Collection
    |--------------------------------------------------------------------------
    */
    public function collection(): \Illuminate\Support\Enumerable
    {
        return $this->collections;
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
            // 'id' => 'رقم التحصيل',
            'customer_name' => 'اسم العميل',
            'customer_account' => 'رقم حساب العميل',
            'customer_identity_number' => 'رقم هوية العميل',
            'company_name' => 'الشركة',
            'portfolio_name' => 'المحفظة',
            'collected_amount' => 'المبلغ المحصل',
            'payment_method' => 'طريقة الدفع',
            'receipt_number' => 'رقم السند',
            'status' => 'الحالة',
            'collection_type' => 'نوع التحصيل',
            'payment_type' => 'نوع السداد',
            'collection_date' => 'تاريخ التحصيل',
            'created_by' => 'أنشأ بواسطة',
            'created_at' => 'تاريخ الإنشاء',
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
    public function map($collection): array
    {
        static $counter = 1;
        $row = [$counter++];

        foreach ($this->selectedFields as $field) {
            switch ($field) {
                case 'id':
                    $row[] = $collection->id;
                    break;
                case 'customer_name':
                    $row[] = $collection->customer->name ?? 'غير محدد';
                    break;
                case 'customer_account':
                    $row[] = $collection->customer->account_number ?? 'غير محدد';
                    break;
                case 'customer_identity_number':
                    $row[] = $collection->customer->identity_number ?? 'غير محدد';
                    break;
                case 'company_name':
                    $row[] = $collection->customer->company->name ?? 'غير محدد';
                    break;
                case 'portfolio_name':
                    $row[] = $collection->customer->portfolio->name ?? 'غير محدد';
                    break;
                case 'collected_amount':
                    $row[] = number_format($collection->collected_amount, 2);
                    break;
                case 'payment_method':
                    $row[] = $collection->paymentMethod->name ?? 'غير محدد';
                    break;
                case 'receipt_number':
                    $row[] = $collection->receipt_number ?: 'غير محدد';
                    break;
                case 'status':
                    $statusLabels = [
                        'approved' => 'معتمد',
                        'pending' => 'معلق',
                        'rejected' => 'مرفوض',
                    ];
                    $row[] = $statusLabels[$collection->status] ?? $collection->status;
                    break;
                case 'collection_type':
                    $row[] = $collection->collection_type === 'cash' ? 'نقدي' : 'مجدول';
                    break;
                case 'payment_type':
                    $row[] = $collection->payment_type === 'settlement' ? 'بتسوية' : 'بدون تسوية';
                    break;
                case 'collection_date':
                    $row[] = $collection->collection_date
                        ? Carbon::parse($collection->collection_date)->format('Y-m-d')
                        : 'غير محدد';
                    break;
                case 'created_by':
                    $row[] = $collection->addedBy->name ?? 'غير محدد';
                    break;
                case 'created_at':
                    $row[] = $collection->created_at->format('Y-m-d H:i');
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
        $lastRow = $this->rowCount + 1; // تعديل هنا من +2 إلى +1
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
            'id' => 12,
            'customer_name' => 25,
            'customer_account' => 20,
            'customer_identity_number' => 20,
            'company_name' => 20,
            'portfolio_name' => 20,
            'collected_amount' => 20,
            'payment_method' => 20,
            'receipt_number' => 18,
            'status' => 15,
            'collection_type' => 15,
            'payment_type' => 18,
            'collection_date' => 18,
            'created_by' => 20,
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

                $summaryRow = $lastRow + 1;
                $totalCollections = $this->collections->count();
                $approvedCollections = $this->collections->where('status', 'approved')->count();
                $totalApprovedAmount = $this->collections->where('status', 'approved')->sum('collected_amount');
                $pendingCollections = $this->collections->where('status', 'pending')->count();
                $rejectedCollections = $this->collections->where('status', 'rejected')->count();

                $summaryText = "إجمالي التحصيلات: " . number_format($totalCollections) .
                    " | المعتمدة: " . number_format($approvedCollections) .
                    " | المعلقة: " . number_format($pendingCollections) .
                    " | المرفوضة: " . number_format($rejectedCollections) .
                    " | إجمالي المبلغ المعتمد: " . number_format($totalApprovedAmount, 2) . " ريال";

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
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['argb' => '1F4E78'],
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
        return 'تقرير التحصيل المالي';
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
