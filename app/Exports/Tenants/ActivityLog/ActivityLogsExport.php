<?php

namespace App\Exports\Tenants\ActivityLog;

use App\Traits\ActivityLogPresenterTrait;
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

class ActivityLogsExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    ShouldAutoSize,
    WithEvents
{
    use ActivityLogPresenterTrait;

    protected $activities;

    public function __construct($activities)
    {
        $this->activities = $activities;
    }

    /*
    |--------------------------------------------------------------------------
    | Collection
    |--------------------------------------------------------------------------
    */
    public function collection()
    {
        return $this->activities;
    }

    /*
    |--------------------------------------------------------------------------
    | Headings
    |--------------------------------------------------------------------------
    */
    public function headings(): array
    {
        return [
            '#',
            'نوع النشاط',
            'الوصف',
            'قام به',
            'الموضوع',
            'عنوان IP',
            'الجهاز',
            'نظام التشغيل',
            'المتصفح',
            'التاريخ',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Map
    |--------------------------------------------------------------------------
    */
    public function map($activity): array
    {
        static $index = 0;
        $index++;

        $connectionInfo = $this->getActivityConnectionInfo($activity);

        return [
            $index,
            $this->activityLogNameLabel($activity->log_name),
            $activity->description,
            $activity->causer->name ?? 'النظام',
            $this->activitySubjectDisplay($activity),
            $connectionInfo['ip'] ?? '-',
            $connectionInfo['device'] ?? '-',
            $connectionInfo['os'] ?? '-',
            $connectionInfo['browser'] ?? '-',
            $activity->created_at ? $activity->created_at->format('Y-m-d H:i:s') : '-',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Styles
    |--------------------------------------------------------------------------
    */
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:J1')->applyFromArray([
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

        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A2:J' . $lastRow)->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A1:J' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | Column Widths
    |--------------------------------------------------------------------------
    */
    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 18,
            'C' => 45,
            'D' => 20,
            'E' => 30,
            'F' => 16,
            'G' => 14,
            'H' => 16,
            'I' => 18,
            'J' => 18,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Register Events
    |--------------------------------------------------------------------------
    */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->setRightToLeft(true);
                $event->sheet->getDelegate()->getRowDimension(1)->setRowHeight(25);
            },
        ];
    }
}
