<?php

namespace App\Exports\Tenants\Dashboard\Admin;

use App\Models\Tenants\FinancialCollection\CollectionProcess\CollectionProcessSession;
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
use Carbon\Carbon;

class CollectionSessionsExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithStyles, 
    WithColumnWidths, 
    ShouldAutoSize,
    WithEvents
{
    protected $date;
    protected $status;

    public function __construct($date = null, $status = null)
    {
        $this->date = $date ?? today()->toDateString();
        $this->status = $status;
    }

    /*
    |--------------------------------------------------------------------------
    | Collection
    |--------------------------------------------------------------------------
    */
    public function collection(): \Illuminate\Support\Enumerable
    {
        $query = CollectionProcessSession::with([
            'user:id,name,employee_number',
            'workPeriods' => function ($q) {
                $q->orderBy('entry_time', 'asc');
            }
        ])->whereDate('date', $this->date);

        if ($this->status === 'active') {
            $query->where('is_active', true);
        } elseif ($this->status === 'inactive') {
            $query->where('is_active', false);
        }

        return $query->orderBy('is_active', 'desc')
            ->orderBy('total_work_minutes', 'desc')
            ->get();
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
            'اسم الموظف',
            'الرقم الوظيفي',
            'الحالة',
            'أول دخول',
            'آخر نشاط',
            'نوع النشاط',
            'ساعات العمل',
            'عدد المتابعات',
            'عدد النشاطات',
            'التاريخ',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Map
    |--------------------------------------------------------------------------
    */
    public function map($session): array
    {
        static $index = 0;
        $index++;

        $totalMinutes = $session->total_work_minutes;
        $activityTime = '-';
        $activityType = '-';

        if ($session->is_active) {
            $openPeriod = $session->workPeriods->whereNull('exit_time')->first();
            if ($openPeriod) {
                $currentPeriodMinutes = $openPeriod->entry_time->diffInMinutes(now());
                $totalMinutes += $currentPeriodMinutes;
                $activityTime = $openPeriod->entry_time->format('H:i');
                $activityType = 'دخول';
            }
        } else {
            $lastPeriod = $session->workPeriods->sortByDesc('exit_time')->first();
            if ($lastPeriod && $lastPeriod->exit_time) {
                $activityTime = $lastPeriod->exit_time->format('H:i');
                $activityType = 'خروج';
            }
        }

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        $workingHours = sprintf('%02d:%02d', $hours, $minutes);

        return [
            $index,
            $session->user->name ?? 'غير محدد',
            $session->user->employee_number ?? 'غير محدد',
            $session->is_active ? 'متواجد الآن' : 'غير متواجد',
            $session->first_entry_time ? $session->first_entry_time->format('H:i') : '-',
            $activityTime,
            $activityType,
            $workingHours,
            $session->total_follows ?? 0,
            $session->total_activities ?? 0,
            Carbon::parse($session->date)->format('Y-m-d'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Styles
    |--------------------------------------------------------------------------
    */
    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:K1')->applyFromArray([
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
        $sheet->getStyle('A2:K' . $lastRow)->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A1:K' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        for ($row = 2; $row <= $lastRow; $row++) {
            $statusValue = $sheet->getCell('D' . $row)->getValue();
            if ($statusValue === 'متواجد الآن') {
                $sheet->getStyle('D' . $row)->applyFromArray([
                    'font' => ['color' => ['rgb' => '28B78D'], 'bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E8F5E9'],
                    ],
                ]);
            } else {
                $sheet->getStyle('D' . $row)->applyFromArray([
                    'font' => ['color' => ['rgb' => '999999']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F5F5F5'],
                    ],
                ]);
            }
        }

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
            'A' => 8,
            'B' => 25,
            'C' => 18,
            'D' => 18,
            'E' => 15,
            'F' => 15,
            'G' => 18,
            'H' => 18,
            'I' => 18,
            'J' => 18,
            'K' => 15,
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
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getDelegate()->setRightToLeft(true);
                $event->sheet->getDelegate()->getRowDimension(1)->setRowHeight(25);
                $lastRow = $event->sheet->getDelegate()->getHighestRow();
                for ($row = 2; $row <= $lastRow; $row++) {
                    $event->sheet->getDelegate()->getRowDimension($row)->setRowHeight(20);
                }
            },
        ];
    }
}