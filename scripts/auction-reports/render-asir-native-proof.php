<?php

declare(strict_types=1);

use App\Contracts\Auctions\BrowserDocumentRenderer;
use App\Services\Tenants\Auctions\Reports\AsirReportData;
use App\Services\Tenants\Auctions\Reports\AuctionReportArtwork;
use App\Services\Tenants\Auctions\Reports\AuctionReportChannels;
use App\Services\Tenants\Auctions\Reports\AuctionReportLinks;
use App\Services\Tenants\Auctions\Reports\AuctionReportMedia;
use App\Services\Tenants\Auctions\Reports\AuctionReportPaginator;
use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use App\Services\Tenants\Auctions\Reports\AuctionReportTextLayout;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
ini_set('memory_limit', '512M');
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
app()->setLocale('ar');
DB::listen(static function (): void {
    throw new RuntimeException('Asir visual proofs must not query a database.');
});
$scenario = $argv[1] ?? 'reference';
if (! in_array($scenario, ['reference', 'populated', 'sparse', 'many', 'long', 'appendix'], true)) {
    throw new InvalidArgumentException('Unknown Asir proof scenario.');
}
$snapshot = (require base_path('tests/Fixtures/AuctionReports/asir-native.php'))($scenario);
$directory = base_path('output/pdf/asir-native-content');
if (! is_dir($directory)) {
    mkdir($directory, 0775, true);
}
$suffix = in_array('--baseline', $argv, true) ? '-original' : '';
$path = $directory.'/'.$scenario.$suffix.'.html';
if ($suffix !== '' && is_file($path)) {
    throw new RuntimeException('The captured original must not be overwritten.');
}
$html = $suffix !== '' ? originalAsirHtml($snapshot) : app(AuctionReportRenderer::class)->html($snapshot, 'report');
file_put_contents($path, $html);
if (in_array('--pdf', $argv, true)) {
    app(BrowserDocumentRenderer::class)->pdf($html, $directory.'/'.$scenario.$suffix.'-engine.pdf');
}
echo $path.PHP_EOL;

/** Render the unchanged SVG templates for review only; never switch production back to SVG. */
function originalAsirHtml(array $snapshot): string
{
    $artwork = app(AuctionReportArtwork::class);
    $binder = app(AsirReportData::class);
    if (empty($snapshot['auction']['report_image'])) {
        $snapshot['auction']['report_image'] = $artwork->defaultReportImage();
    }
    $snapshot = app(AuctionReportMedia::class)->prepare($snapshot);
    $pages = [];
    foreach (app(AuctionReportPaginator::class)->paginate($snapshot, 'report') as $page) {
        $pageSnapshot = $snapshot;
        if (array_key_exists('campaigns', $page['payload'])) {
            $pageSnapshot['marketing']['campaigns'] = $page['payload']['campaigns'];
            $campaign = AuctionReportChannels::campaign($page['payload']['campaigns'], 'whatsapp');
            $pageSnapshot['marketing']['whatsapp_date'] = $campaign['campaign_date'] ?? null;
        }
        $content = $binder->page($pageSnapshot, $page);
        unset($content['native_template']);
        if (array_key_exists('branding', $snapshot)) {
            $content['artwork_overrides']['branding'] = $snapshot['branding'];
        }
        $definition = array_replace($artwork->page('asir', $content['index']), $content['artwork_overrides'] ?? []);
        foreach (app(AuctionReportTextLayout::class)->flow($content, $definition) as $part) {
            $part['links'] = (new AuctionReportLinks)->forPage($pageSnapshot, $definition, $part['field_layouts'] ?? [], $part['link_destinations'] ?? []);
            $pages[] = ['page_type' => $page['page_type'], 'page_number' => count($pages) + 1, 'content' => $part];
        }
    }

    return view('tenant.auctions.reports.report', ['data' => $snapshot, 'pages' => $pages, 'artwork' => $artwork, 'asirData' => $binder])->render();
}
