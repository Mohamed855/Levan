<?php

declare(strict_types=1);

use App\Contracts\Auctions\BrowserDocumentRenderer;
use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
ini_set('memory_limit', '768M');
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$referenceDirectory = $argv[1] ?? getenv('AUCTION_REPORT_REFERENCE_DIR');
$output = base_path('output/pdf/reference-fixtures');
if (! is_dir($output)) {
    mkdir($output, 0775, true);
}
foreach (['haram' => 'outputs', 'asir' => 'report'] as $design => $type) {
    $snapshot = require base_path('tests/Fixtures/AuctionReports/'.$design.'.php');
    if ($design === 'asir' && is_string($referenceDirectory) && is_dir($referenceDirectory)) {
        $source = file_get_contents($referenceDirectory.'/asir.html');
        $manifest = require resource_path('auction-reports/manifest.php');
        if (hash('sha256', $source) !== $manifest['source_hashes']['asir']) {
            throw new RuntimeException('Reference source hash mismatch.');
        }
        $marker = '<script id="template-package" type="application/json">';
        $start = strpos($source, $marker) + strlen($marker);
        $end = strpos($source, '</script>', $start);
        $package = json_decode(substr($source, $start, $end - $start), true, flags: JSON_THROW_ON_ERROR);
        [$header, $encoded] = explode(',', $package['assets']['57f9524018b458c61391'], 2);
        $bytes = base64_decode($encoded, true);
        if ($bytes === false) {
            throw new RuntimeException('Invalid fixture landscape image.');
        }
        $snapshot['auction']['report_image'] = $header.','.base64_encode($bytes);
    }
    $html = app(AuctionReportRenderer::class)->html($snapshot, $type);
    file_put_contents($output.'/'.$design.'.html', $html);
    if (in_array('--png-only', $argv, true)) {
        app(BrowserDocumentRenderer::class)->png($html, $output.'/'.$design.'.png');
    } elseif (! in_array('--html-only', $argv, true)) {
        app(BrowserDocumentRenderer::class)->pdf($html, $output.'/'.$design.'.pdf');
    }
    preg_match_all('/data-page-type="([^"]+)"/', $html, $matches);
    echo json_encode(['design' => $design, 'pages' => count($matches[1]), 'families' => array_count_values($matches[1])], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
}
