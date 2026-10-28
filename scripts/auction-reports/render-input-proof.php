<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\ViewErrorBag;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
app()->setLocale('ar');
view()->share('errors', new ViewErrorBag);
$directory = base_path('tmp/pdfs/remediation');
if (!is_dir($directory)) {
    mkdir($directory, 0775, true);
}
file_put_contents($directory.'/report-input-proof.html', '<!doctype html><meta charset="utf-8"><form id="report-proof">'.view('tenant.auctions.partials.steps.step4-settings')->render().'</form>');
echo $directory.'/report-input-proof.html'.PHP_EOL;
