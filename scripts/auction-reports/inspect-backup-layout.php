<?php

declare(strict_types=1);

use App\Services\Tenants\Auctions\Reports\AsirReportData;
use App\Services\Tenants\Auctions\Reports\AuctionReportArtwork;
use App\Services\Tenants\Auctions\Reports\AuctionReportPaginator;
use App\Services\Tenants\Auctions\Reports\AuctionReportTextLayout;
use App\Services\Tenants\Auctions\Reports\HaramReportData;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
app()->setLocale('ar');
foreach (in_array('--link-proof', $argv, true) ? ['link-proof'] : ['haram', 'asir'] as $fixture) {
    $path = $fixture === 'link-proof' ? 'tmp/pdfs/remediation/link-proof-snapshot.json' : 'output/pdf/remediation-acceptance/'.$fixture.'-snapshot.json';
    $snapshot = json_decode(file_get_contents(base_path($path)), true, flags: JSON_THROW_ON_ERROR);
    foreach (['outputs' => 'haram', 'report' => 'asir'] as $type => $design) {
        $binder = app($type === 'outputs' ? HaramReportData::class : AsirReportData::class);
        foreach (app(AuctionReportPaginator::class)->paginate($snapshot, $type) as $page) {
            $content = $binder->page($snapshot, $page);
            $definition = array_replace(app(AuctionReportArtwork::class)->page($design, $content['index']), $content['artwork_overrides'] ?? []);
            $parts = app(AuctionReportTextLayout::class)->flow($content, $definition);
            if (count($parts) <= 1) {
                continue;
            }
            $changes = [];
            foreach ($content['values'] as $key => $value) {
                if (count(array_unique(array_column(array_column($parts, 'values'), $key))) > 1) {
                    $changes[$key] = array_column(array_column($parts, 'values'), $key);
                }
            }
            echo json_encode(['fixture' => $fixture, 'type' => $type, 'page' => $page['page_type'], 'parts' => count($parts), 'changing_fields' => $changes], JSON_UNESCAPED_UNICODE).PHP_EOL;
        }
    }
}
