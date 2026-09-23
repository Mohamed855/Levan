<?php

namespace App\Exports\Tenants\BusinessCenter\Task;

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

class TasksExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected $tasks;
    protected $rowCount = 0;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct($tasks)
    {
        $this->tasks = $tasks;
    }

    /*
    |--------------------------------------------------------------------------
    | Collection
    |--------------------------------------------------------------------------
    */
    public function collection()
    {
        return $this->tasks;
    }

    /*
    |--------------------------------------------------------------------------
    | Headings - عناوين الأعمدة
    |--------------------------------------------------------------------------
    */
    public function headings(): array
    {
        return [
            'م',
            'اسم العميل',
            'رقم الهاتف',
            'رقم الحساب',
            'رقم الهوية',
            'الشركة',
            'المحفظة',
            'نوع المهمة',
            'التاريخ المحدد',
            'أيام التأخير',
            'الحالة',
            'المديونية المتبقية',
            'مسؤول العلاقات',
            'رقم الموظف',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Map - تحويل البيانات للصفوف
    |--------------------------------------------------------------------------
    */
    public function map($task): array
    {
        static $counter = 1;

        // Task Type Label
        $taskTypeLabels = [
            'promise' => 'وعد بالسداد',
            'callback' => 'معاودة اتصال',
            'document_request' => 'طلب مستندات',
        ];
        $taskType = $taskTypeLabels[$task->task_type] ?? 'غير محدد';

        // Status Label
        if ($task->status === 'completed') {
            $status = 'مكتملة';
        } elseif ($task->status === 'pending' && $task->due_date && now()->toDateString() > $task->due_date) {
            $status = 'متأخرة';
        } else {
            $status = 'معلقة';
        }

        // Overdue Days
        $days = (int)$task->overdue_days;
        if ($days < 0) {
            $overdueDays = 'متبقي ' . abs($days) . ' يوم';
        } elseif ($days == 0) {
            $overdueDays = 'اليوم';
        } else {
            $overdueDays = $days . ' يوم';
        }

        $row = [
            $counter++,
            $task->customer_name ?: 'غير محدد',
            $task->customer_phone ?: 'غير محدد',
            $task->account_number ?: 'غير محدد',
            $task->identity_number ?: 'غير محدد',
            $task->company_name ?: 'غير محدد',
            $task->portfolio_name ?: 'غير محدد',
            $taskType,
            $task->due_date ? Carbon::parse($task->due_date)->format('Y-m-d') : 'غير محدد',
            $overdueDays,
            $status,
            number_format((float)$task->remaining_debt, 2) . ' ريال',
            $task->relationship_manager_name ?: 'غير محدد',
            $task->relationship_manager_emp ?: 'غير محدد',
        ];

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
        $lastColumnLetter = 'N'; // عدد الأعمدة = 14

        return [
            // تنسيق صف العناوين
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

            // تنسيق البيانات
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
        return [
            'A' => 8,   // م
            'B' => 25,  // اسم العميل
            'C' => 18,  // رقم الهاتف
            'D' => 18,  // رقم الحساب
            'E' => 18,  // رقم الهوية
            'F' => 20,  // الشركة
            'G' => 20,  // المحفظة
            'H' => 18,  // نوع المهمة
            'I' => 15,  // التاريخ المحدد
            'J' => 15,  // أيام التأخير
            'K' => 12,  // الحالة
            'L' => 20,  // المديونية المتبقية
            'M' => 20,  // مسؤول العلاقات
            'N' => 15,  // رقم الموظف
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

                // تجميد الصف الأول
                $sheet->freezePane('A2');

                $lastRow = $this->rowCount + 1;
                $lastColumnLetter = 'N';

                // إضافة الحدود
                $sheet->getStyle("A1:{$lastColumnLetter}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);

                // إضافة الفلاتر التلقائية
                $sheet->setAutoFilter("A1:{$lastColumnLetter}{$lastRow}");

                // تفعيل RTL
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
        return 'تقرير المهام';
    }
}
