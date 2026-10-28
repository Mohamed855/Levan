<?php

declare(strict_types=1);

use App\Contracts\Auctions\BrowserDocumentRenderer;
use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
app()->setLocale('ar');
$snapshot = require base_path('tests/Fixtures/AuctionReports/haram.php');
$snapshot['auction']['platform_url'] = 'https://example.com/platform';
$snapshot['auction']['status'] = 'completed';
$snapshot['marketing']['contacts'] = ['phone' => '0534811800', 'unified_phone' => '920000000', 'social' => 'https://example.com/social'];
foreach (['brochure', 'video', 'marketing_evidence', 'boards'] as $role) {
    $snapshot['marketing']['links'][] = ['label' => $role, 'role' => $role, 'url' => 'https://example.com/'.$role];
}
foreach (['x' => 573000, 'tiktok' => 554000, 'snapchat' => 220000, 'whatsapp' => 0] as $channel => $impressions) {
    $snapshot['marketing']['campaigns'][] = ['platform' => $channel, 'url' => 'https://example.com/'.$channel, 'impressions' => $impressions];
}
foreach (['outputs', 'report'] as $type) {
    $html = app(AuctionReportRenderer::class)->html($snapshot, $type);
    $path = base_path('tmp/pdfs/remediation/link-proof-'.$type.'.pdf');
    file_put_contents(str_replace('.pdf', '.html', $path), $html);
    file_put_contents(base_path('tmp/pdfs/remediation/link-proof-snapshot.json'), json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    if (!in_array('--html-only', $argv, true)) {
        app(BrowserDocumentRenderer::class)->pdf($html, $path);
    }
    echo $path.PHP_EOL;
}
