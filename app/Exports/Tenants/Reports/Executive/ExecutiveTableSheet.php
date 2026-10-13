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

class ExecutiveTableSheet implements FromArray, WithColumnWidths, WithEvents, WithStyles, WithTitle
{
    private int $rowCount = 0;

    public function __construct(
        private readonly string $title,
        private readonly array $head,
        private readonly array $rows,
    ) {}

    public function array(): array
    {
        $data = [];

        if ($this->head) {
            $data[] = array_map(fn ($h) => strip_tags($h), $this->head);
        }

        foreach ($this->rows as $row) {
            $data[] = array_map(fn ($cell) => strip_tags((string) $cell), $row);
            $this->rowCount++;
        }

        return $data;
    }

    public function title(): string
    {
        // Excel sheet names ≤ 31 chars
        return mb_substr($this->title, 0, 31);
    }

    public function columnWidths(): array
    {
        $widths = [];
        $letters = range('A', 'Z');
        foreach ($this->head as $i => $heading) {
            $widths[$letters[$i]] = max(18, mb_strlen(strip_tags($heading)) * 2);
        }

        return $widths;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '1F4E78']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $colCount = count($this->head);
                if ($colCount === 0) {
                    return;
                }

                $lastCol = $this->numToLetter($colCount);
                $lastRow = $this->rowCount + 1;

                // حدود الجدول
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'CCCCCC']]],
                ]);

                // محاذاة يمين
                $sheet->getStyle("A2:{$lastCol}{$lastRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // تلوين متناوب
                for ($r = 2; $r <= $lastRow; $r++) {
                    if ($r % 2 === 0) {
                        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('F0F4FA');
                    }
                }

                $sheet->setAutoFilter("A1:{$lastCol}1");
                $sheet->setRightToLeft(true);
                $sheet->freezePane('A2');
            },
        ];
    }

    private function numToLetter(int $n): string
    {
        $letter = '';
        while ($n > 0) {
            $n--;
            $letter = chr(65 + ($n % 26)).$letter;
            $n = intdiv($n, 26);
        }

        return $letter;
    }
}
