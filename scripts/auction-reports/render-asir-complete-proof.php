<?php

declare(strict_types=1);

use App\Contracts\Auctions\BrowserDocumentRenderer;
use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
ini_set('memory_limit', '512M');
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
app()->setLocale('ar');
DB::listen(static function (): void {
    throw new RuntimeException('The complete Asir example must not query tenant or central records.');
});
$snapshot = (require base_path('tests/Fixtures/AuctionReports/asir-complete.php'))();
$directory = base_path('output/pdf/asir-complete-review');
if (! is_dir($directory)) {
    mkdir($directory, 0775, true);
}
$html = app(AuctionReportRenderer::class)->html($snapshot, 'report');
file_put_contents($directory.'/auction-final-report-complete.html', $html);
app(BrowserDocumentRenderer::class)->pdf($html, $directory.'/auction-final-report-complete.pdf');
echo $directory.'/auction-final-report-complete.pdf'.PHP_EOL;
