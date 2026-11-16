<?php

declare(strict_types=1);

use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
DB::listen(static function (): void {
    throw new RuntimeException('Native content proofs must not query a database.');
});
$scenario = $argv[1] ?? 'reference';
if (! in_array($scenario, ['reference', 'many', 'long', 'partial'], true)) {
    throw new InvalidArgumentException('Use reference, many, long or partial.');
}
$snapshot = require base_path('tests/Fixtures/AuctionReports/haram-native-index.php');
$snapshot['closing_saved'] = true;
$snapshot['properties'][0] = array_replace($snapshot['properties'][0], [
    'result_status' => 'sold', 'opening_price' => 3000000, 'sale_price' => 3650000,
    'participants_count' => 14, 'bids_count' => 38,
]);
$snapshot['properties'][1]['result_status'] = 'unsold';
$snapshot['properties'][1]['unsold_reason'] = '';
$snapshot['marketing'] = array_replace($snapshot['marketing'], [
    'results_narrative' => 'تم ترسية العقار الأول (المزرعة النموذجية) بنجاح وجاري البت في العطاءات المقدمة للعقار الثاني.',
    'board_removal_date' => '2026-11-19',
    'evidence_url' => 'https://example.com/evidence', 'boards_url' => 'https://example.com/boards',
    'campaigns' => [
        ['id' => 1, 'platform' => 'x', 'impressions' => 380000, 'url' => 'https://example.com/x'],
        ['id' => 2, 'platform' => 'tiktok', 'views' => 290000, 'reach' => 195000, 'url' => 'https://example.com/tiktok'],
        ['id' => 3, 'platform' => 'snapchat', 'impressions' => 510000],
    ],
]);
$image = static function (string $filename, int $id, array $metadata = []) use ($scenario): array {
    $path = base_path('tmp/pdfs/haram-reference/'.$filename);
    $uri = is_file($path) && $scenario === 'reference'
        ? 'data:image/png;base64,'.base64_encode(file_get_contents($path))
        : 'data:image/svg+xml;base64,'.base64_encode(file_get_contents(resource_path('auction-reports/fallback.svg')));

    return ['id' => $id, 'uri' => $uri, 'fit' => 'contain'] + $metadata;
};
$snapshot['evidence'] = [
    'x' => [$image('page-4-image-1.png', 1, ['campaign_id' => 1, 'channel' => 'x'])],
    'social' => [$image('page-4-image-2.png', 2, ['campaign_id' => 3, 'channel' => 'snapchat'])],
    'board_before' => [$image('page-5-image-1.png', 3)],
];
$snapshot['evidence']['social'][0]['fit'] = 'cover';
if ($scenario === 'many' || $scenario === 'long') {
    $property = $snapshot['properties'][0];
    $snapshot['properties'] = [];
    for ($index = 1; $index <= 47; $index++) {
        $snapshot['properties'][] = array_replace($property, [
            'id' => $index, 'number' => 'PROP-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            'result_status' => $index <= 19 ? 'sold' : 'unsold',
            'unsold_reason' => $scenario === 'long' ? str_repeat('سبب عدم البيع المسجل للعقار مع جميع التفاصيل. ', 35) : 'سبب عدم البيع للعقار '.$index,
        ]);
    }
    $snapshot['evidence']['board_before'] = [];
    $snapshot['evidence']['board_after'] = [];
    $snapshot['evidence']['brokers'] = [];
    for ($index = 1; $index <= 17; $index++) {
        $snapshot['evidence']['board_before'][] = $image('', 100 + $index);
        $snapshot['evidence']['board_after'][] = $image('', 200 + $index);
        $snapshot['evidence']['brokers'][] = $image('', 300 + $index, ['display_name' => 'الوسيط العقاري '.$index]);
    }
    if ($scenario === 'long') {
        $snapshot['marketing']['results_narrative'] = str_repeat('نتائج المزاد والبيانات المسجلة عن عملية البيع. ', 220);
    }
}
if ($scenario === 'partial') {
    $snapshot['closing_saved'] = false;
    $snapshot['evidence'] = [];
    $snapshot['marketing'] = ['campaigns' => [['platform' => 'x', 'views' => 0]]];
}
$directory = base_path('output/pdf/haram-native-content');
if (! is_dir($directory)) {
    mkdir($directory, 0775, true);
}
$path = $directory.'/'.$scenario.'.html';
file_put_contents($path, app(AuctionReportRenderer::class)->html($snapshot, 'outputs'));
echo $path.PHP_EOL;
