<?php

namespace App\Exports\Tenants\Reports\Executive;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExecutiveKpiSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths, WithEvents
{
    public function __construct(
        private readonly array  $kpis,
        private readonly string $dateLabel,
    ) {}

    public function array(): array
    {
        $rows = [['المؤشر', 'القيمة']];

        foreach ($this->kpis as $kpi) {
            $rows[] = [$kpi['title'] ?? '', $kpi['value'] ?? ''];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'المؤشرات الرئيسية';
    }

    public function columnWidths(): array
    {
        return ['A' => 35, 'B' => 28];
    }

    public function styles(Worksheet $sheet): array: array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '1F4E78']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $lastRow  = count($this->kpis) + 1;

                $sheet->getStyle("A1:B{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'CCCCCC']]],
                ]);

                $sheet->getStyle("A2:B{$lastRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // تلوين الصفوف بالتناوب
                for ($r = 2; $r <= $lastRow; $r++) {
                    if ($r % 2 === 0) {
                        $sheet->getStyle("A{$r}:B{$r}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('F0F4FA');
                    }
                }

                if ($this->dateLabel) {
                    $infoRow = $lastRow + 2;
                    $sheet->setCellValue("A{$infoRow}", 'الفترة: ' . $this->dateLabel);
                    $sheet->mergeCells("A{$infoRow}:B{$infoRow}");
                    $sheet->getStyle("A{$infoRow}")->getFont()->setBold(true);
                }

                $sheet->setRightToLeft(true);
                $sheet->freezePane('A2');
            },
        ];
    }
}
