<?php

namespace App\Exports\Tenants\BusinessCenter\Customer\UpdateRequest;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CustomerUpdateRequestsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected Collection $requests;
    protected array $selectedFields;
    protected int $rowCount = 0;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(Collection $requests, array $selectedFields)
    {
        $this->requests = $requests;
        $this->selectedFields = $selectedFields;
    }

    /*
    |--------------------------------------------------------------------------
    | Collection
    |--------------------------------------------------------------------------
    */
    public function collection(): \Illuminate\Support\Enumerable
    {
        return $this->requests;
    }

    /*
    |--------------------------------------------------------------------------
    | Headings
    |--------------------------------------------------------------------------
    */
    public function headings(): array
    {
        $headings = ['م'];

        $fieldLabels = [
            'id' => 'رقم الطلب',
            'customer_name' => 'اسم العميل',
            'customer_identity_number' => 'رقم الهوية',
            'requested_by_name' => 'مقدم الطلب',
            'created_at' => 'تاريخ الطلب',
            'status' => 'الحالة',
            'approved_by_name' => 'معالج الطلب',
            'approved_at' => 'تاريخ المعالجة',
            'rejection_reason' => 'سبب الرفض',
            'requested_data' => 'البيانات المطلوبة'
        ];

        foreach ($this->selectedFields as $field) {
            $headings[] = $fieldLabels[$field] ?? $field;
        }

        return $headings;
    }

    /*
    |--------------------------------------------------------------------------
    | Map
    |--------------------------------------------------------------------------
    */
    public function map($request): array
    {
        static $counter = 1;
        $row = [$counter++];

        foreach ($this->selectedFields as $field) {
            switch ($field) {
                case 'id':
                    $row[] = $request->id;
                    break;
                case 'customer_name':
                    $row[] = $request->customer?->name ?? 'غير محدد';
                    break;
                case 'customer_identity_number':
                    $row[] = $request->customer?->identity_number ?? 'غير محدد';
                    break;
                case 'requested_by_name':
                    $row[] = $request->requestedBy?->name ?? 'غير محدد';
                    break;
                case 'created_at':
                    $row[] = $request->created_at?->format('Y-m-d H:i:s') ?? '-';
                    break;
                case 'status':
                    $row[] = $this->getStatusLabel($request->status);
                    break;
                case 'approved_by_name':
                    $row[] = $request->approvedBy?->name ?? 'غير محدد';
                    break;
                case 'approved_at':
                    $row[] = $request->approved_at?->format('Y-m-d H:i:s') ?? '-';
                    break;
                case 'rejection_reason':
                    $row[] = $request->rejection_reason ?? '-';
                    break;
                case 'requested_data':
                    $row[] = $this->formatRequestedData($request->requested_data);
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
    | Get Status Label
    |--------------------------------------------------------------------------
    */
    private function getStatusLabel($status): string
    {
        $statuses = [
            'pending' => 'معلق',
            'approved' => 'موافق عليه',
            'rejected' => 'مرفوض'
        ];

        return $statuses[$status] ?? $status;
    }

    /*
    |--------------------------------------------------------------------------
    | Format Requested Data
    |--------------------------------------------------------------------------
    */
    private function formatRequestedData($data): string
    {
        if (!$data || !is_array($data)) {
            return '-';
        }

        $formattedData = [];
        foreach ($data as $key => $value) {
            if ($key === 'phones' && is_array($value)) {
                $phones = [];
                foreach ($value as $phone) {
                    $phones[] = $phone['phone'] ?? '';
                }
                $formattedData[] = 'أرقام الهواتف: ' . implode(', ', $phones);
            } else {
                $formattedData[] = "$key: $value";
            }
        }

        return implode("\n", $formattedData);
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
            'id' => 12,
            'customer_name' => 25,
            'customer_identity_number' => 20,
            'requested_by_name' => 20,
            'created_at' => 20,
            'status' => 15,
            'approved_by_name' => 20,
            'approved_at' => 20,
            'rejection_reason' => 30,
            'requested_data' => 40
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
    | Styles
    |--------------------------------------------------------------------------
    */
    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->rowCount + 1;
        $lastColumn = $this->getColumnLetter(count($this->selectedFields) + 1);

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

            "A2:{$lastColumn}{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('A2');

                $lastRow = $this->rowCount + 1;
                $lastColumn = $this->getColumnLetter(count($this->selectedFields) + 1);

                $summaryRow = $lastRow + 1;
                $totalRequests = $this->requests->count();
                $pendingRequests = $this->requests->where('status', 'pending')->count();
                $approvedRequests = $this->requests->where('status', 'approved')->count();
                $rejectedRequests = $this->requests->where('status', 'rejected')->count();

                $summaryText = "إجمالي الطلبات: " . number_format($totalRequests) .
                    " | المعلقة: " . number_format($pendingRequests) .
                    " | الموافق عليها: " . number_format($approvedRequests) .
                    " | المرفوضة: " . number_format($rejectedRequests);

                $sheet->setCellValue("A{$summaryRow}", $summaryText);
                $sheet->mergeCells("A{$summaryRow}:{$lastColumn}{$summaryRow}");

                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getStyle("A{$summaryRow}:{$lastColumn}{$summaryRow}")->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['argb' => '1F4E78'],
                        ],
                    ],
                ]);

                $sheet->getStyle("A{$summaryRow}:{$lastColumn}{$summaryRow}")->applyFromArray([
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

                $sheet->setAutoFilter("A1:{$lastColumn}{$lastRow}");
                $sheet->setRightToLeft(true);
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
        return 'طلبات تحديث بيانات العملاء';
    }

    /*
    |--------------------------------------------------------------------------
    | Get Column Letter
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