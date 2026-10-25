<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Shared\Pdf\PdfFormWidgetLocator;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PdfFormWidgetLocatorTest extends TestCase
{
    public function test_it_locates_named_widget_rectangles_on_their_pdf_pages(): void
    {
        $pdfPath = storage_path('framework/testing/widget-locator-'.uniqid().'.pdf');
        File::ensureDirectoryExists(dirname($pdfPath));
        File::put($pdfPath, <<<'PDF'
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 600 800] /Annots [4 0 R 5 0 R] >>
endobj
4 0 obj
<< /Type /Annot /Subtype /Widget /T (cover_image) /Rect [50 300 250 600] >>
endobj
5 0 obj
<< /Type /Annot /Subtype /Widget /Parent 6 0 R /Rect [310 660 550 710] >>
endobj
6 0 obj
<< /FT /Tx /T (auction_name) >>
endobj
PDF);

        try {
            $widgets = app(PdfFormWidgetLocator::class)->locate($pdfPath);

            $this->assertSame([
                'cover_image' => [[
                    'page' => 1,
                    'rect' => [50.0, 300.0, 250.0, 600.0],
                    'page_size' => [600.0, 800.0],
                ]],
                'auction_name' => [[
                    'page' => 1,
                    'rect' => [310.0, 660.0, 550.0, 710.0],
                    'page_size' => [600.0, 800.0],
                ]],
            ], $widgets);
        } finally {
            File::delete($pdfPath);
        }
    }
}
