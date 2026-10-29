<?php

declare(strict_types=1);

use App\Contracts\Auctions\BrowserDocumentRenderer;
use App\Services\Tenants\Auctions\Reports\AuctionReportArtwork;
use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
app()->setLocale('ar');
DB::listen(static function (): void { throw new RuntimeException('Cover proof must not query a database.'); });
$directory = base_path('output/pdf/optional-cover-proof');
if (!is_dir($directory)) { mkdir($directory, 0775, true); }
foreach (['missing', 'uploaded'] as $case) {
    $snapshot = require base_path('tests/Fixtures/AuctionReports/haram.php');
    $snapshot['auction']['cover_image'] = $case === 'uploaded' ? app(AuctionReportArtwork::class)->defaultReportImage() : null;
    $snapshot['auction']['report_image'] = $case === 'uploaded' ? app(AuctionReportArtwork::class)->defaultReportImage() : null;
    foreach (['outputs', 'report'] as $type) {
        $html = app(AuctionReportRenderer::class)->html($snapshot, $type);
        file_put_contents($directory.'/'.$case.'-'.$type.'.html', $html);
        app(BrowserDocumentRenderer::class)->pdf($html, $directory.'/'.$case.'-'.$type.'.pdf');
        echo $case.'-'.$type.PHP_EOL;
    }
}
