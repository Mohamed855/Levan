<?php

declare(strict_types=1);

use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
DB::listen(static function (): void {
    throw new RuntimeException('Native index proofs must not query a database.');
});
$snapshot = require base_path('tests/Fixtures/AuctionReports/haram-native-index.php');
$scenario = $argv[1] ?? 'reference';
if (! in_array($scenario, ['reference', 'many', 'long'], true)) {
    throw new InvalidArgumentException('Use reference, many or long.');
}
if ($scenario === 'long') {
    $snapshot['assignment']['name'] = str_repeat('مشروع عقارات الرياض ', 10);
    $snapshot['auction']['project_manager_name'] = str_repeat('مدير المشروع العقاري ', 6);
}
if ($scenario === 'many') {
    $property = $snapshot['properties'][0];
    $snapshot['properties'] = [];
    for ($index = 1; $index <= 29; $index++) {
        $snapshot['properties'][] = array_replace($property, [
            'id' => $index, 'number' => 'PROP-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            'type' => $index % 4 === 0 ? str_repeat('أرض سكنية استثمارية ', 3) : 'أرض',
        ]);
    }
}
$directory = base_path('output/pdf/haram-native-index');
if (! is_dir($directory)) {
    mkdir($directory, 0775, true);
}
$path = $directory.'/'.$scenario.'.html';
file_put_contents($path, app(AuctionReportRenderer::class)->html($snapshot, 'outputs'));
echo $path.PHP_EOL;
