<?php

declare(strict_types=1);

use App\Services\Tenants\Auctions\Reports\AuctionReportFonts;
use Illuminate\Contracts\Console\Kernel;
use Spatie\Browsershot\Browsershot;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$root = base_path('tmp/pdfs/remediation');
if (! is_dir($root)) {
    mkdir($root, 0775, true);
}
$fonts = app(AuctionReportFonts::class);
$html = '<html lang="ar"><head><meta charset="utf-8"><style>'.$fonts->css().'@page{size:A4;margin:0}body{margin:0}svg{width:595pt;height:842pt}</style></head><body><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 595 842">';
foreach (['573,000', '554,000', '220,000', '1,547', '1,000,006', '78,000', '0123456789,./:-%'] as $index => $value) {
    $html .= '<text x="45" y="'.(55 + 38 * $index).'" font-family="ArimoReportBold" font-weight="700" font-size="24">'.$value.'</text>';
}
foreach (['NotoSansArabic-Regular', 'NotoSansArabic-Bold', 'TheSans-Plain', 'TheSans-Bold', 'LamaSans-Bold', 'GEContrastBold-Bold'] as $index => $name) {
    $face = $fonts->resolve($name);
    $html .= '<text x="550" y="'.(380 + 55 * $index).'" direction="rtl" font-family="'.$face['family'].'" font-weight="'.$face['weight'].'" font-size="24">مزاد جوار الحرم - واجهة عسير</text>';
}
$html .= '</svg></body></html>';
file_put_contents($root.'/fonts-proof.html', $html);
$browser = Browsershot::html($html)->timeout(60)->waitUntilNetworkIdle()->waitForFunction('document.fonts.status === "loaded"');
if ($chrome = config('auction-renderer.chrome_path')) {
    $browser->setChromePath($chrome);
}
$browser->setOption('preferCSSPageSize', true)->margins(0, 0, 0, 0)->save($root.'/fonts-proof.pdf');
echo $root.'/fonts-proof.pdf'.PHP_EOL;
