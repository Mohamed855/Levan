<?php

declare(strict_types=1);

use App\Actions\Tenants\Auctions\UpdateAuctionAction;
use App\Http\Controllers\Tenants\Auctions\AuctionReportController;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Auctions\Reports\AuctionReportService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\Support\AuctionReportAcceptanceFixture;
use Tests\Support\AuctionReportAcceptanceReceipt;
use Tests\Support\AuctionReportDatabaseGuard;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$settings = [
    'central' => getenv('DB_DATABASE') ?: '', 'tenant' => getenv('AUCTION_REPORT_TEST_TENANT_DATABASE') ?: '',
    'tenant_id' => getenv('AUCTION_REPORT_TEST_TENANT_ID') ?: '', 'url' => getenv('DB_URL') ?: '',
];
AuctionReportDatabaseGuard::assertSafe($settings);
ini_set('memory_limit', '1024M');
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
AuctionReportDatabaseGuard::assertSafe(array_replace($settings, ['central' => (string) config('database.connections.mysql.database'), 'url' => (string) config('database.connections.mysql.url')]));
app()->setLocale('ar');
$mode = $argv[1] ?? '';
if (!in_array($mode, ['--prepare', '--enqueue', '--auto-save', '--work', '--collect'], true)) {
    throw new InvalidArgumentException('Use --prepare, --enqueue, --auto-save, --work or --collect with explicit isolated CI environment.');
}
$tenantId = $settings['tenant_id'];
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--tenant=') && substr($argument, 9) !== $tenantId) {
        throw new InvalidArgumentException('The requested tenant differs from the isolated acceptance tenant.');
    }
}
$directory = base_path('output/pdf/queued-ci-acceptance');
if (!is_dir($directory)) {
    mkdir($directory, 0775, true);
}
if ($mode === '--prepare') {
    $fixture = (new AuctionReportAcceptanceFixture)->prepare($settings, $directory.'/fixture-receipt.json');
    echo json_encode(['prepared' => $fixture['maps']['auctions'], 'database' => $fixture['database']], JSON_PRETTY_PRINT).PHP_EOL;
    exit(0);
}
$tenant = Tenant::query()->findOrFail($tenantId);
AuctionReportDatabaseGuard::assertSafe(array_replace($settings, ['tenant' => (string) $tenant->db_database]));
$trigger = in_array('--trigger=auto', $argv, true) || $mode === '--auto-save' ? 'auto' : 'manual';
$receiptPath = $directory.'/'.$trigger.'-receipt.json';
tenancy()->initialize($tenant);
try {
    if (in_array($mode, ['--enqueue', '--auto-save'], true)) {
        $queue = 'report-ci-'.bin2hex(random_bytes(8));
        config(['queue.connections.auction_reports.queue' => $queue]);
        $receipt = ['tenant' => $tenantId, 'queue' => $queue, 'trigger' => $trigger, 'auctions' => []];
        foreach (['مزاد جوار الحرم' => [11, 11], 'واجهة عسير' => [13, 51]] as $name => [$properties, $attachments]) {
            $auction = Auction::query()->where('name', $name)->withCount(['properties', 'attachments'])->sole();
            if ($auction->properties_count !== $properties || $auction->attachments_count !== $attachments) {
                throw new RuntimeException('CI acceptance input counts changed: '.$name);
            }
            if ($mode === '--auto-save') {
                // An actual fixture-only change, not a no-op save.
                $auction = app(UpdateAuctionAction::class)->execute($auction, ['description' => $auction->description.' (CI auto export)']);
                $generation = $auction->latestReportGeneration()->first();
            } else {
                $generation = app(AuctionReportService::class)->requestGeneration($auction, $tenantId);
            }
            if ($generation === null) {
                throw new RuntimeException('The report could not be queued.');
            }
            $generation->refresh();
            $receipt['auctions'][] = ['id' => $auction->id, 'generation' => $generation->id, 'revision' => $generation->revision_token, 'snapshot_sha256' => hash('sha256', json_encode($generation->snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)), 'name' => $name, 'properties' => $properties, 'attachments' => $attachments];
        }
        file_put_contents($receiptPath, json_encode($receipt, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        echo json_encode(['trigger' => $trigger, 'queue' => $queue, 'generations' => array_column($receipt['auctions'], 'generation')], JSON_PRETTY_PRINT).PHP_EOL;
        exit(0);
    }
    $receipt = json_decode(file_get_contents($receiptPath), true, flags: JSON_THROW_ON_ERROR);
    if ($receipt['tenant'] !== $tenantId || !preg_match('/^report-ci-[a-f0-9]{16}$/D', $receipt['queue'])) {
        throw new RuntimeException('Receipt tenant or queue is outside the dedicated CI scope.');
    }
    if ($mode === '--work') {
        $exit = Artisan::call('queue:work', ['connection' => 'auction_reports', '--queue' => $receipt['queue'], '--stop-when-empty' => true, '--tries' => 1, '--timeout' => 420, '--sleep' => 1]);
        echo Artisan::output();
        exit($exit);
    }
    $user = User::query()->where('email', 'report-acceptance@invalid.example')->sole();
    if (!$user->can('auctions.view')) {
        throw new RuntimeException('The CI acceptance user lacks download permission.');
    }
    Auth::shouldUse('tenant');
    Auth::guard('tenant')->setUser($user);
    $documents = (new AuctionReportAcceptanceReceipt)->documents($receipt);
    foreach ($documents as &$item) {
        $auction = Auction::query()->findOrFail($item['id']);
        $generation = $auction->latestReportGeneration;
        if ($generation?->id !== $item['generation'] || $generation->revision_token !== $item['revision'] || $generation->status !== 'ready') {
            throw new RuntimeException('Expected generation is not ready: '.($generation?->status ?? 'missing').' '.($generation?->error_message ?? ''));
        }
        $snapshotHash = hash('sha256', json_encode($generation->snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        if (!hash_equals($item['snapshot_sha256'], $snapshotHash)) {
            throw new RuntimeException('The generation snapshot changed between queue and download.');
        }
        $path = $item['type'] === 'outputs' ? $generation->outputs_path : $generation->report_path;
        if (Storage::disk('tenant_public')->exists($path)) {
            throw new RuntimeException('A generated report was exposed on the public disk.');
        }
        $response = app(AuctionReportController::class)->download($auction, $item['type']);
        ob_start();
        $response->sendContent();
        $bytes = ob_get_clean();
        if ($response->getStatusCode() !== 200 || !str_starts_with($bytes, '%PDF-')) {
            throw new RuntimeException('Authorized download did not return a PDF.');
        }
        $item['pdf'] = $directory.'/'.$trigger.'-auction-'.$auction->id.'-'.$item['type'].'.pdf';
        file_put_contents($item['pdf'], $bytes);
        $item['sha256'] = hash('sha256', $bytes);
        $item['status'] = $generation->status;
        $item['private'] = true;
    }
    unset($item);
    $receipt['documents'] = $documents;
    file_put_contents($directory.'/'.$trigger.'-result.json', json_encode($receipt, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    echo json_encode(['trigger' => $trigger, 'documents' => count($documents), 'ready' => true, 'private' => true], JSON_PRETTY_PRINT).PHP_EOL;
} finally {
    tenancy()->end();
}
