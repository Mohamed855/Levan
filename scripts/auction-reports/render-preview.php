<?php

declare(strict_types=1);

use App\Contracts\Auctions\BrowserDocumentRenderer;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\Reports\AuctionReportDataBuilder;
use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

ini_set('memory_limit', '768M');
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$tenantId = $argv[1] ?? null;
$auctionId = filter_var($argv[2] ?? null, FILTER_VALIDATE_INT);
$type = $argv[3] ?? 'outputs';
if (! is_string($tenantId) || $auctionId === false || ! in_array($type, ['outputs', 'report'], true)) {
    throw new InvalidArgumentException('Usage: render-preview.php TENANT AUCTION_ID outputs|report');
}
$tenant = Tenant::query()->findOrFail($tenantId);
tenancy()->initialize($tenant);
try {
    $auction = Auction::query()->findOrFail($auctionId);
    $snapshot = json_decode(json_encode(app(AuctionReportDataBuilder::class)->build($auction), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    $html = app(AuctionReportRenderer::class)->html($snapshot, $type);
    $directory = base_path('output/pdf/fidelity-preview');
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
    $path = $directory.'/auction-'.$auctionId.'-'.$type;
    file_put_contents($path.'.html', $html);
    app(BrowserDocumentRenderer::class)->pdf($html, $path.'.pdf');
    echo json_encode(['tenant' => $tenantId, 'auction' => $auctionId, 'type' => $type, 'properties' => count($snapshot['properties']), 'pages' => substr_count($html, 'data-page-type='), 'pdf' => $path.'.pdf'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
} finally {
    tenancy()->end();
}
