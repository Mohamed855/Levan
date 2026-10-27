<?php

declare(strict_types=1);

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Auctions\Auction;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$directory = $argv[1] ?? '';
$source = file_get_contents($directory.'/asir.html');
$manifest = require resource_path('auction-reports/manifest.php');
if (hash('sha256', $source) !== $manifest['source_hashes']['asir']) {
    throw new RuntimeException('Reference source hash mismatch.');
}
$marker = '<script id="template-package" type="application/json">';
$start = strpos($source, $marker) + strlen($marker);
$end = strpos($source, '</script>', $start);
$package = json_decode(substr($source, $start, $end - $start), true, flags: JSON_THROW_ON_ERROR);
$bytes = base64_decode(explode(',', $package['assets']['57f9524018b458c61391'], 2)[1], true);
if ($bytes === false || getimagesizefromstring($bytes) === false) {
    throw new RuntimeException('Invalid reference landscape.');
}
tenancy()->initialize(Tenant::query()->findOrFail('test'));
try {
    $auction = Auction::query()->whereKey(21)->where('name', 'واجهة عسير')->sole();
    $backup = storage_path('app/private/backups/auction-21-landscape-'.now()->format('Ymd-His').'.json');
    file_put_contents($backup, json_encode($auction->only(['id', 'report_image']), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    $path = 'auctions/report-images/restored-asir-'.Str::uuid().'.jpg';
    if (! Storage::disk('tenant_public')->put($path, $bytes)) {
        throw new RuntimeException('Could not store the restored landscape.');
    }
    $auction->update(['report_image' => $path]);
    echo json_encode(['auction' => $auction->id, 'backup' => $backup, 'image' => $path], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
} finally {
    tenancy()->end();
}
