<?php

declare(strict_types=1);

use App\Contracts\Auctions\BrowserDocumentRenderer;
use App\Services\Tenants\Auctions\Reports\AsirReportData;
use App\Services\Tenants\Auctions\Reports\AuctionReportArtwork;
use App\Services\Tenants\Auctions\Reports\AuctionReportLinks;
use App\Services\Tenants\Auctions\Reports\AuctionReportMedia;
use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use App\Services\Tenants\Auctions\Reports\AuctionReportTextLayout;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
app()->setLocale('ar');
DB::listen(static fn () => throw new RuntimeException('Asir cover proofs must not query a database.'));
$scenario = $argv[1] ?? 'normal';
$snapshot = (require base_path('tests/Fixtures/AuctionReports/asir-covers.php'))($scenario);
$directory = base_path('output/pdf/asir-cover-fixes');
if (! is_dir($directory)) {
    mkdir($directory, 0775, true);
}
try {
    $baseline = in_array('--baseline', $argv, true);
    $suffix = $baseline ? '-original' : '';
    $path = $directory.'/'.$scenario.$suffix.'.html';
    if ($baseline && is_file($path)) {
        throw new RuntimeException('The captured original cover proof must not be overwritten.');
    }
    $html = $baseline ? originalAsirCovers($snapshot) : app(AuctionReportRenderer::class)->html($snapshot, 'report');
    file_put_contents($path, $html);
    if (in_array('--pdf', $argv, true)) {
        app(BrowserDocumentRenderer::class)->pdf($html, $directory.'/'.$scenario.$suffix.'.pdf');
    }
    echo $path.PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage().PHP_EOL);
    exit(1);
}

/** Rebuild the original SVG covers for visual comparison only. */
function originalAsirCovers(array $snapshot): string
{
    $artwork = app(AuctionReportArtwork::class);
    $snapshot['auction']['report_image'] ??= $artwork->defaultReportImage();
    $snapshot = app(AuctionReportMedia::class)->prepare($snapshot);
    $binder = app(AsirReportData::class);
    $pages = [];
    foreach (['asir-cover', 'asir-closing'] as $type) {
        $content = $binder->page($snapshot, ['page_type' => $type, 'payload' => []]);
        unset($content['native_template'], $content['native_cover']);
        if (array_key_exists('branding', $snapshot)) {
            $content['artwork_overrides']['branding'] = $snapshot['branding'];
        }
        $definition = array_replace($artwork->page('asir', $content['index']), $content['artwork_overrides'] ?? []);
        foreach (app(AuctionReportTextLayout::class)->flow($content, $definition) as $part) {
            $part['links'] = (new AuctionReportLinks)->forPage($snapshot, $definition, $part['field_layouts'] ?? []);
            $pages[] = ['page_type' => $type, 'page_number' => count($pages) + 1, 'content' => $part];
        }
    }

    return view('tenant.auctions.reports.report', ['data' => $snapshot, 'pages' => $pages, 'artwork' => $artwork, 'asirData' => $binder])->render();
}
