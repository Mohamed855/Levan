<?php

declare(strict_types=1);

namespace App\Services\Shared\Pdf;

use mikehaertl\pdftk\Pdf;
use RuntimeException;

final class PdfFormFiller
{
    /** @param array<string, string> $values */
    public function fill(string $templatePath, array $values, string $outputPath): void
    {
        $pdf = $this->pdf($templatePath)->fillForm($values)->needAppearances();

        if (! $pdf->saveAs($outputPath)) {
            throw new RuntimeException('Unable to fill the PDF form: '.$pdf->getError());
        }
    }

    public function uncompress(string $templatePath, string $outputPath): void
    {
        $pdf = $this->pdf($templatePath)->compress(false);

        if (! $pdf->saveAs($outputPath)) {
            throw new RuntimeException('Unable to inspect the PDF form fields: '.$pdf->getError());
        }
    }

    public function stamp(string $documentPath, string $overlayPath, string $outputPath): void
    {
        $pdf = $this->pdf($documentPath)->multiStamp($overlayPath);

        if (! $pdf->saveAs($outputPath)) {
            throw new RuntimeException('Unable to place PDF form images: '.$pdf->getError());
        }
    }

    private function pdf(string $path): Pdf
    {
        $options = [];
        $command = config('services.pdftk.path', env('PDFTK_PATH'));

        if ($command) {
            $options['command'] = $command;
        }

        return new Pdf($path, $options);
    }
}
