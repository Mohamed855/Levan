<?php

declare(strict_types=1);

use App\Http\Controllers\Tenants\Auctions\AuctionReportController;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Auctions\Reports\AuctionReportService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

require dirname(__DIR__, 2).'/vendor/autoload.php';
ini_set('memory_limit', '768M');
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$mode = $argv[1] ?? '';
if (! in_array($mode, ['--enqueue', '--collect'], true)) {
    throw new InvalidArgumentException('Use --enqueue or --collect.');
}
tenancy()->initialize(Tenant::query()->findOrFail('test'));
try {
    $directory = base_path('output/pdf/queued-acceptance');
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
    $receiptPath = $directory.'/receipt.json';
    if ($mode === '--enqueue') {
        $queue = 'auction-fidelity-'.now()->format('Ymd-His');
        config(['queue.connections.auction_reports.queue' => $queue]);
        $receipt = ['tenant' => 'test', 'queue' => $queue, 'auctions' => []];
        foreach (['مزاد جوار الحرم' => [11, 11, 'outputs'], 'واجهة عسير' => [13, 51, 'report']] as $name => [$properties, $attachments, $type]) {
            $auction = Auction::query()->where('name', $name)->withCount(['properties', 'attachments'])->sole();
            if ($auction->properties_count !== $properties || $auction->attachments_count !== $attachments) {
                throw new RuntimeException('Acceptance input counts changed: '.$name);
            }
            $generation = app(AuctionReportService::class)->requestGeneration($auction, 'test');
            if ($generation === null) {
                throw new RuntimeException('The report could not be queued.');
            }
            $receipt['auctions'][] = ['id' => $auction->id, 'generation' => $generation->id, 'revision' => $generation->revision_token, 'type' => $type, 'name' => $name, 'properties' => $properties, 'attachments' => $attachments];
        }
        file_put_contents($receiptPath, json_encode($receipt, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        echo json_encode($receipt, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
        exit(0);
    }
    $receipt = json_decode(file_get_contents($receiptPath), true, flags: JSON_THROW_ON_ERROR);
    $user = User::query()->get()->first(static fn (User $user): bool => $user->can('auctions.view'));
    if ($user === null) {
        throw new RuntimeException('No authorized tenant user can download the report.');
    }
    Auth::shouldUse('tenant');
    Auth::guard('tenant')->setUser($user);
    foreach ($receipt['auctions'] as &$item) {
        $auction = Auction::query()->findOrFail($item['id']);
        $generation = $auction->latestReportGeneration;
        if ($generation?->id !== $item['generation'] || $generation->status !== 'ready') {
            throw new RuntimeException('Generation is not ready: '.($generation?->status ?? 'missing').' '.($generation?->error_message ?? ''));
        }
        $path = $item['type'] === 'outputs' ? $generation->outputs_path : $generation->report_path;
        if (Storage::disk('tenant_public')->exists($path)) {
            throw new RuntimeException('A generated report was exposed on the public disk.');
        }
        $response = app(AuctionReportController::class)->download($auction, $item['type']);
        ob_start();
        $response->sendContent();
        $bytes = ob_get_clean();
        if ($response->getStatusCode() !== 200 || ! str_starts_with($bytes, '%PDF-')) {
            throw new RuntimeException('Authorized download did not return a PDF.');
        }
        $item['pdf'] = $directory.'/auction-'.$auction->id.'-'.$item['type'].'.pdf';
        file_put_contents($item['pdf'], $bytes);
        $item['sha256'] = hash('sha256', $bytes);
        $item['status'] = $generation->status;
        $item['private'] = true;
    }
    unset($item);
    file_put_contents($directory.'/result.json', json_encode($receipt, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    echo json_encode($receipt, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
} finally {
    tenancy()->end();
}
