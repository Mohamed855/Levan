<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

final class AuctionReportMedia
{
    private array $documents = [];

    public function __construct(private readonly ?string $binary = null) {}

    public function prepare(array $snapshot): array
    {
        foreach ($snapshot['evidence'] ?? [] as $section => $items) {
            if ($section === 'design_asset') {
                continue;
            }
            $prepared = [];
            foreach ($items as $item) {
                $uri = $item['uri'] ?? '';
                if (! str_starts_with($uri, 'data:application/pdf;base64,')) {
                    $prepared[] = $item;

                    continue;
                }
                foreach ($this->pages($uri) as $index => $page) {
                    $prepared[] = array_replace($item, ['uri' => $page, 'mime' => 'image/png', 'fit' => 'contain', 'document_page' => $index + 1]);
                }
            }
            $snapshot['evidence'][$section] = $prepared;
        }

        return $snapshot;
    }

    /** @return list<string> */
    private function pages(string $uri): array
    {
        $key = hash('sha256', $uri);
        if (isset($this->documents[$key])) {
            return $this->documents[$key];
        }
        $bytes = base64_decode(substr($uri, strlen('data:application/pdf;base64,')), true);
        if ($bytes === false || ! str_starts_with($bytes, '%PDF-')) {
            throw new RuntimeException('تعذر قراءة مرفق PDF الخاص بالتقرير.');
        }
        $directory = sys_get_temp_dir().'/auction-media-'.bin2hex(random_bytes(16));
        if (! mkdir($directory, 0700)) {
            throw new RuntimeException('Unable to create the private report media workspace.');
        }
        try {
            if (file_put_contents($directory.'/source.pdf', $bytes) === false) {
                throw new RuntimeException('Unable to write the report evidence file.');
            }
            $process = new Process([
                $this->binary ?? config('auction-renderer.pdf_rasterizer_binary', 'pdftoppm'),
                '-r', '150', '-scale-to', '1800', '-png', $directory.'/source.pdf', $directory.'/page',
            ]);
            $process->setTimeout(90)->mustRun();
            $files = glob($directory.'/page-*.png');
            natsort($files);
            $pages = [];
            foreach ($files as $file) {
                $png = file_get_contents($file);
                if ($png === false || getimagesizefromstring($png) === false) {
                    throw new RuntimeException('The PDF rasterizer returned an invalid page.');
                }
                $pages[] = 'data:image/png;base64,'.base64_encode($png);
            }
            if ($pages === []) {
                throw new RuntimeException('The PDF evidence has no renderable pages.');
            }

            return $this->documents[$key] = $pages;
        } catch (Throwable $exception) {
            throw new RuntimeException('تعذر تحويل صفحات مرفق PDF. تحقق من صلاحية الملف وتثبيت Poppler.', 0, $exception);
        } finally {
            foreach (glob($directory.'/*') as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }
}
