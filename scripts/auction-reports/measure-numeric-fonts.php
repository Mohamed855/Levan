<?php

declare(strict_types=1);

use App\Services\Tenants\Auctions\Reports\AuctionReportArtwork;
use Illuminate\Contracts\Console\Kernel;
use Spatie\Browsershot\Browsershot;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$catalog = require resource_path('auction-reports/fonts/catalog.php');
$html = '<html><head><style>'.app(AuctionReportArtwork::class)->fontCss().'</style></head><body></body></html>';
$browser = Browsershot::html($html)->timeout(60)->waitUntilNetworkIdle();
foreach (['node_binary' => 'setNodeBinary', 'npm_binary' => 'setNpmBinary', 'chrome_path' => 'setChromePath'] as $key => $method) {
    if ($value = config('auction-renderer.'.$key)) {
        $browser->{$method}($value);
    }
}
$expression = <<<'JS'
(async () => {
    const catalog = __CATALOG__;
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    const result = {};
    for (const [name, font] of Object.entries(catalog)) {
        await document.fonts.load(`100px ${font.family}`);
        context.font = `100px ${font.family}`;
        context.fontKerning = 'none';
        result[name] = {};
        for (const character of '0123456789٠١٢٣٤٥٦٧٨٩ ()/,:%×*+.-') {
            result[name][character] = context.measureText(character).width / 100;
        }
    }
    return JSON.stringify(result);
})()
JS;
$metrics = json_decode($browser->evaluate(str_replace('__CATALOG__', json_encode($catalog, JSON_THROW_ON_ERROR), $expression)), true, flags: JSON_THROW_ON_ERROR);
file_put_contents(resource_path('auction-reports/fonts/numeric-metrics.php'), "<?php\n\ndeclare(strict_types=1);\n\nreturn ".var_export($metrics, true).";\n");
echo count($metrics).' numeric font metric sets measured in Chromium.'.PHP_EOL;
