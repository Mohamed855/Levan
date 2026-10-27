<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Services\Tenants\Auctions\Reports\AuctionReportMedia;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AuctionReportMediaTest extends TestCase
{
    public function test_all_pdf_pages_replace_the_evidence_item_in_original_order(): void
    {
        $pdf = 'data:application/pdf;base64,'.base64_encode($this->pdf());
        $snapshot = ['evidence' => ['marketing_evidence' => [
            ['name' => 'before', 'uri' => 'data:image/png;base64,BEFORE'],
            ['name' => 'document.pdf', 'uri' => $pdf, 'display_order' => 2],
            ['name' => 'after', 'uri' => 'data:image/png;base64,AFTER'],
        ]]];
        $media = new AuctionReportMedia('pdftoppm');
        $prepared = $media->prepare($snapshot);
        $items = $prepared['evidence']['marketing_evidence'];
        self::assertCount(4, $items);
        self::assertSame(['before', 'document.pdf', 'document.pdf', 'after'], array_column($items, 'name'));
        self::assertSame([1, 2], [$items[1]['document_page'], $items[2]['document_page']]);
        foreach ([$items[1], $items[2]] as $page) {
            self::assertStringStartsWith('data:image/png;base64,', $page['uri']);
            self::assertSame('contain', $page['fit']);
            self::assertNotFalse(getimagesizefromstring(base64_decode(explode(',', $page['uri'], 2)[1])));
        }
        self::assertSame($prepared, $media->prepare($snapshot));
    }

    public function test_invalid_pdf_evidence_fails_instead_of_silently_replacing_it_with_a_logo(): void
    {
        $this->expectException(RuntimeException::class);
        (new AuctionReportMedia('pdftoppm'))->prepare(['evidence' => ['marketing_evidence' => [['uri' => 'data:application/pdf;base64,'.base64_encode('invalid')]]]]);
    }

    private function pdf(): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R 4 0 R] /Count 2 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 100 100] /Resources << >> /Contents 5 0 R >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 100 100] /Resources << >> /Contents 5 0 R >>',
            "<< /Length 22 >>\nstream\n1 0 0 rg 0 0 50 50 re f\nendstream",
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Root 1 0 R /Size 6 >>\nstartxref\n".$xref."\n%%EOF";
    }
}
