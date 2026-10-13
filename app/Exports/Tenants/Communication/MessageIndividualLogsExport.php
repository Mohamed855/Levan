<?php

namespace App\Exports\Tenants\Communication;

use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
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

class MessageIndividualLogsExport implements FromCollection, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected Collection $logs;

    protected $rowCount = 0;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Initialize the export with logs collection.
    */
    public function __construct(Collection $logs)
    {
        $this->logs = $logs;
    }

    /*
    |--------------------------------------------------------------------------
    | Collection Source
    |--------------------------------------------------------------------------
    | Return the collection to be exported.
    */
    public function collection(): Enumerable
    {
        return $this->logs;
    }

    /*
    |--------------------------------------------------------------------------
    | Column Headings
    |--------------------------------------------------------------------------
    | Define the column headers for the Excel file.
    */
    public function headings(): array
    {
        return [
            'م',
            'اسم العميل',
            'رقم الهوية',
            'رقم الحساب',
            'نوع العميل',
            'الشركة',
            'المحفظة',
            'أرقام الهاتف',
            'عدد الأرقام',
            'نوع الرسالة',
            'اسم القالب',
            'محتوى الرسالة',
            'الحالة',
            'تاريخ الإرسال',
            'أرسل بواسطة',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Row Mapping
    |--------------------------------------------------------------------------
    | Map each model instance to Excel row data.
    */
    public function map($log): array
    {
        static $counter = 1;

        $row = [
            $counter++,
            $log->customer_name,
            $log->customer?->identity_number ?? '—',
            $log->customer?->account_number ?? '—',
            $log->customer ? ($log->customer->customer_type == 'individual' ? 'فردي' : 'شركة') : 'غير محدد',
            $log->company ? $log->company->name : 'غير محدد',
            $log->portfolio ? $log->portfolio->name : 'غير محدد',
            $this->formatPhoneNumbers($log->phone_numbers),
            $log->phone_numbers_count,
            $this->getMessageTypeLabel($log->message_type),
            $log->template_name ?: 'رسالة مخصصة',
            $this->formatMessageContent($log->message_content),
            $this->getStatusLabel($log->status),
            $log->sent_at ? $log->sent_at->format('Y-m-d H:i:s') : '-',
            $log->sentBy ? $log->sentBy->name : 'غير محدد',
        ];

        $this->rowCount++;

        return $row;
    }

    /*
    |--------------------------------------------------------------------------
    | Format Phone Numbers
    |--------------------------------------------------------------------------
    | Format phone numbers array for display.
    */
    private function formatPhoneNumbers($phoneNumbers): string
    {
        // التحقق إذا كانت القيمة فارغة
        if (! $phoneNumbers) {
            return 'لا يوجد';
        }

        // التحقق إذا كانت القيمة مصفوفة بالفعل (بسبب cast في المودل)
        if (is_array($phoneNumbers)) {
            $phones = $phoneNumbers;
        }
        // إذا كانت نصًا (لأي سبب غير متوقع)، قم بفك تشفيره
        elseif (is_string($phoneNumbers)) {
            $phones = json_decode($phoneNumbers, true);
        } else {
            // لأي نوع بيانات آخر، اعتبره غير صالح
            return 'لا يوجد';
        }

        // التحقق من أن المصفوفة ليست فارغة بعد المعالجة
        if (! is_array($phones) || empty($phones)) {
            return 'لا يوجد';
        }

        return implode(', ', $phones);
    }

    /*
    |--------------------------------------------------------------------------
    | Format Message Content
    |--------------------------------------------------------------------------
    | Format message content for Excel display.
    */
    private function formatMessageContent($content): string
    {
        if (! $content) {
            return '-';
        }

        // Limit content length for Excel display
        $maxLength = 100;
        if (mb_strlen($content) > $maxLength) {
            return mb_substr($content, 0, $maxLength).'...';
        }

        return $content;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Message Type Label
    |--------------------------------------------------------------------------
    | Convert message type to Arabic label.
    */
    private function getMessageTypeLabel($type): string
    {
        return match ($type) {
            'template' => 'قالب',
            'custom' => 'مخصصة',
            'sms' => 'رسالة نصية',
            default => $type
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Get Status Label
    |--------------------------------------------------------------------------
    | Convert status to Arabic label.
    */
    private function getStatusLabel($status): string
    {
        return match ($status) {
            'pending' => 'معلق',
            'processing' => 'قيد المعالجة',
            'sent' => 'تم الإرسال',
            'failed' => 'فشل',
            'cancelled' => 'ملغي',
            default => $status
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Column Widths
    |--------------------------------------------------------------------------
    | Define column widths for better readability.
    */
    public function columnWidths(): array
    {
        return [
            'A' => 8,   // م
            'B' => 25,  // اسم العميل
            'C' => 18,  // رقم الهوية
            'D' => 18,  // رقم الحساب
            'E' => 12,  // نوع العميل
            'F' => 20,  // الشركة
            'G' => 20,  // المحفظة
            'H' => 30,  // أرقام الهاتف
            'I' => 12,  // عدد الأرقام
            'J' => 15,  // نوع الرسالة
            'K' => 20,  // اسم القالب
            'L' => 40,  // محتوى الرسالة
            'M' => 15,  // الحالة
            'N' => 20,  // تاريخ الإرسال
            'O' => 20,  // أرسل بواسطة
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Sheet Styles
    |--------------------------------------------------------------------------
    | Apply styles to the Excel sheet.
    */
    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->rowCount + 1;
        $lastColumn = 'O';  // تعديل من 'S' إلى 'M'

        return [
            // Header row styling
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

            // All cells alignment
            "A1:{$lastColumn}{$lastRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],

        ];
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
                $lastColumn = 'O';  // تعديل من 'S' إلى 'M'

                $summaryRow = $lastRow + 1;
                $totalMessages = $this->logs->count();
                $sentMessages = $this->logs->where('status', 'sent')->count();
                $failedMessages = $this->logs->where('status', 'failed')->count();
                $pendingMessages = $this->logs->where('status', 'pending')->count();
                $processingMessages = $this->logs->where('status', 'processing')->count();
                $cancelledMessages = $this->logs->where('status', 'cancelled')->count();
                $totalPhones = $this->logs->sum('phone_numbers_count');

                $summaryText = 'إجمالي الرسائل: '.number_format($totalMessages).
                    ' | المرسلة: '.number_format($sentMessages).
                    ' | الفاشلة: '.number_format($failedMessages).
                    ' | المعلقة: '.number_format($pendingMessages).
                    ' | قيد المعالجة: '.number_format($processingMessages).
                    ' | الملغية: '.number_format($cancelledMessages).
                    ' | إجمالي أرقام الهواتف: '.number_format($totalPhones);

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
    | Sheet Title
    |--------------------------------------------------------------------------
    | Define the title for the Excel sheet.
    */
    public function title(): string
    {
        return 'سجلات الرسائل الفردية';
    }
}
