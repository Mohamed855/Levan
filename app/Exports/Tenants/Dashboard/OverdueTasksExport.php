<?php

namespace App\Exports\Tenants\Dashboard;

use App\Services\Tenants\Dashboard\DashboardService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OverdueTasksExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    ShouldAutoSize,
    WithEvents
{
    protected $userId;
    protected $filters;
    protected $dashboardService;

    public function __construct(?int $userId, array $filters)
    {
        $this->userId = $userId;
        $this->filters = $filters;
        $this->dashboardService = app(DashboardService::class);
    }

    public function collection(): \Illuminate\Support\Enumerable
    {
        return $this->dashboardService
            ->getOverdueTasksQuery($this->userId, $this->filters)
            ->get();
    }

    public function headings(): array
    {
        $headers = [
            '#',
            'اسم العميل',
            'رقم الهاتف',
            'رقم الحساب',
        ];

        if ($this->userId === null) {
            $headers[] = 'الشركة';
            $headers[] = 'المحفظة';
        }

        $headers[] = 'نوع المهمة';
        $headers[] = 'التاريخ المحدد';
        $headers[] = 'أيام التأخير';
        $headers[] = 'المديونية (ريال)';

        if ($this->userId === null) {
            $headers[] = 'مسؤول العلاقات';
            $headers[] = 'الرقم الوظيفي';
        }

        return $headers;
    }

    public function map($row): array
    {
        static $index = 0;
        $index++;

        $taskTypes = [
            3 => 'وعد بالسداد',
            4 => 'معاودة اتصال',
            9 => 'طلب مستندات',
        ];

        $data = [
            $index,
            $row->name,
            $row->phone ?: 'غير محدد',
            $row->account_number ?: 'غير محدد',
        ];

        if ($this->userId === null) {
            $data[] = $row->company_name ?: 'غير محدد';
            $data[] = $row->portfolio_name ?: 'غير محدد';
        }

        $data[] = $taskTypes[$row->last_result_id] ?? 'غير محدد';
        $data[] = $row->scheduled_date ?: '-';
        $data[] = (int)$row->overdue_days;
        $data[] = number_format((float)$row->remaining_debt, 2);

        if ($this->userId === null) {
            $data[] = $row->rm_name ?: 'غير محدد';
            $data[] = $row->rm_emp ?: 'غير محدد';
        }

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $highestColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F2A44'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A2:' . $highestColumn . $lastRow)->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A1:' . $highestColumn . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        $overdueDaysColumn = $this->userId === null ? 'I' : 'G';

        for ($row = 2; $row <= $lastRow; $row++) {
            $days = (int)$sheet->getCell($overdueDaysColumn . $row)->getValue();

            if ($days <= 3) {
                $color = 'FFF3CD';
                $fontColor = '856404';
            } elseif ($days <= 7) {
                $color = 'FFE5CC';
                $fontColor = 'CC5500';
            } else {
                $color = 'F8D7DA';
                $fontColor = '721C24';
            }

            $sheet->getStyle($overdueDaysColumn . $row)->applyFromArray([
                'font' => [
                    'color' => ['rgb' => $fontColor],
                    'bold' => true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $color],
                ],
            ]);
        }

        $taskTypeColumn = $this->userId === null ? 'G' : 'E';

        for ($row = 2; $row <= $lastRow; $row++) {
            $taskType = $sheet->getCell($taskTypeColumn . $row)->getValue();

            switch ($taskType) {
                case 'وعد بالسداد':
                    $bgColor = 'E8F5E9';
                    $fontColor = '28B78D';
                    break;
                case 'معاودة اتصال':
                    $bgColor = 'E3F2FD';
                    $fontColor = '1976D2';
                    break;
                case 'طلب مستندات':
                    $bgColor = 'FFF8E1';
                    $fontColor = 'F57C00';
                    break;
                default:
                    $bgColor = 'F5F5F5';
                    $fontColor = '999999';
            }

            $sheet->getStyle($taskTypeColumn . $row)->applyFromArray([
                'font' => [
                    'color' => ['rgb' => $fontColor],
                    'bold' => true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $bgColor],
                ],
            ]);
        }

        return [];
    }

    public function columnWidths(): array
    {
        if ($this->userId === null) {
            return [
                'A' => 8,
                'B' => 25,
                'C' => 18,
                'D' => 18,
                'E' => 22,
                'F' => 22,
                'G' => 20,
                'H' => 18,
                'I' => 16,
                'J' => 18,
                'K' => 25,
                'L' => 18,
            ];
        } else {
            return [
                'A' => 8,
                'B' => 25,
                'C' => 18,
                'D' => 18,
                'E' => 20,
                'F' => 18,
                'G' => 16,
                'H' => 18,
            ];
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->setRightToLeft(true);
                $event->sheet->getDelegate()->getRowDimension(1)->setRowHeight(25);
                $lastRow = $event->sheet->getDelegate()->getHighestRow();
                for ($row = 2; $row <= $lastRow; $row++) {
                    $event->sheet->getDelegate()->getRowDimension($row)->setRowHeight(20);
                }
                $event->sheet->getDelegate()->freezePane('A2');
            },
        ];
    }
}
