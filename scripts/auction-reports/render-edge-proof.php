<?php

declare(strict_types=1);

use App\Contracts\Auctions\BrowserDocumentRenderer;
use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
app()->setLocale('ar');
DB::listen(static function (): void { throw new RuntimeException('Synthetic layout proof must not query a database.'); });
$snapshot = require base_path('tests/Fixtures/AuctionReports/asir.php');
$snapshot['auction']['name'] = 'مزاد جوار الحرم للعقارات السكنية';
$sold = array_values(array_filter($snapshot['properties'], static fn ($row) => $row['result_status'] === 'sold'));
$unsold = array_slice(array_values(array_filter($snapshot['properties'], static fn ($row) => $row['result_status'] === 'unsold')), 0, 7);
foreach ($unsold as &$property) {
    $property['unsold_reason'] = str_repeat('سبب عدم البيع ', 20);
}
unset($property);
$snapshot['properties'] = array_merge([$sold[0]], $unsold);
$snapshot['marketing']['campaigns'] = [
    ['platform' => 'snapchat', 'impressions' => null], ['platform' => 'tiktok', 'impressions' => 0], ['platform' => 'x', 'impressions' => 3],
    ['platform' => 'whatsapp', 'delivered' => 70, 'failed' => 20, 'pending' => 10, 'total' => 100],
    ['platform' => 'واتساب', 'delivered' => 10, 'failed' => 80, 'pending' => 10, 'total' => 100],
];
$metrics = array_fill_keys(config('auction-report-inputs.integer_metrics'), '123');
$snapshot['marketing']['campaigns'][] = $metrics + ['platform' => 'youtube', 'watch_hours' => '12.50', 'profile_change' => '-12.50', 'reach_change' => '0', 'views_change' => '1.25', 'url' => 'https://example.com/first-video'];
$snapshot['marketing']['campaigns'][] = ['platform' => 'youtube', 'views' => 456, 'url' => 'https://example.com/second-video'];
$snapshot['evidence']['social'] = [['channel' => 'youtube', 'display_name' => 'لقطة إضافية', 'uri' => null]];
$directory = base_path('output/pdf/edge-acceptance');
if (!is_dir($directory)) {
    mkdir($directory, 0775, true);
}
$receipt = [];
foreach (['outputs', 'report'] as $type) {
    $html = app(AuctionReportRenderer::class)->html($snapshot, $type);
    $path = $directory.'/'.$type.'.pdf';
    file_put_contents($directory.'/'.$type.'.html', $html);
    app(BrowserDocumentRenderer::class)->pdf($html, $path);
    $receipt[$type] = ['path' => $path, 'pages' => substr_count($html, 'data-page-type='), 'sha256' => hash_file('sha256', $path)];
}
file_put_contents($directory.'/receipt.json', json_encode($receipt, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode($receipt, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
