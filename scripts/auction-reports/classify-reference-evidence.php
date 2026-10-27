<?php

declare(strict_types=1);

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Auctions\Auction;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
ini_set('memory_limit', '768M');
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
[$script, $tenantId, $auctionId, $referenceDirectory, $design] = $argv + array_fill(0, 5, null);
if (! is_string($tenantId) || ! ctype_digit((string) $auctionId) || ! is_dir((string) $referenceDirectory) || ! in_array($design, ['haram', 'asir'], true)) {
    throw new InvalidArgumentException('Usage: classify-reference-evidence.php TENANT AUCTION_ID REFERENCE_DIR haram|asir');
}
$manifest = require resource_path('auction-reports/manifest.php');
$sourcePath = $referenceDirectory.'/'.$design.'.html';
if (hash_file('sha256', $sourcePath) !== $manifest['source_hashes'][$design]) {
    throw new RuntimeException('Reference hash mismatch.');
}
$source = file_get_contents($sourcePath);
$marker = '<script id="template-package" type="application/json">';
$start = strpos($source, $marker) + strlen($marker);
$end = strpos($source, '</script>', $start);
$package = json_decode(substr($source, $start, $end - $start), true, flags: JSON_THROW_ON_ERROR);
$definitions = require resource_path('auction-reports/'.$design.'/pages.php');
$matches = [];
$designAssets = [];
foreach ($package['pages'] as $pageIndex => $page) {
    $document = new DOMDocument;
    $document->loadXML($page['svg'], LIBXML_NONET);
    foreach ((new DOMXPath($document))->query('//*[@data-image-def]') as $image) {
        $id = $image->getAttribute('data-image-def');
        preg_match('/@@([^@]+)@@/', $image->getAttributeNS('http://www.w3.org/1999/xlink', 'href'), $asset);
        $uri = $package['assets'][$asset[1]];
        $bytes = base64_decode(explode(',', $uri, 2)[1], true);
        $hash = hash('sha256', $bytes);
        $binding = $definitions[$pageIndex]['image_defs'][$id] ?? null;
        if ($binding === null && $design === 'haram') {
            $section = match ($pageIndex) { 3 => 'marketing_evidence', 4 => 'board_before', 5 => 'brokers', default => null };
            if ($section !== null && str_contains($image->getAttribute('id'), '_photo_')) {
                $binding = ['section' => $section, 'slot' => (int) substr($id, 1)];
            }
        }
        if ($binding === null) {
            $designAssets[$hash] = ['page' => $pageIndex + 1, 'section' => 'design_asset', 'order' => 0, 'reference_image' => $id];
            continue;
        }
        if ($binding === null || $binding['section'] === 'report_image') {
            continue;
        }
        $offset = in_array($pageIndex, [8, 17], true) && $design === 'asir' ? 6 : 0;
        $matches[$hash][] = ['page' => $pageIndex + 1, 'section' => $binding['section'], 'order' => $binding['slot'] + $offset, 'reference_image' => $id];
    }
}
tenancy()->initialize(Tenant::query()->findOrFail($tenantId));
try {
    $auction = Auction::query()->with('attachments')->findOrFail((int) $auctionId);
    $proposal = [];
    foreach ($auction->attachments as $attachment) {
        $disk = Storage::disk('tenant_public');
        $hash = $disk->exists($attachment->file_path) ? hash('sha256', $disk->get($attachment->file_path)) : null;
        $candidates = $matches[$hash] ?? [];
        $sameSection = array_values(array_filter($candidates, static fn (array $candidate): bool => $candidate['section'] === $attachment->report_section));
        $selected = $sameSection[0] ?? $candidates[0] ?? $designAssets[$hash] ?? null;
        $proposal[] = [
            'id' => $attachment->id, 'file' => $attachment->file_name,
            'current_section' => $attachment->report_section, 'current_order' => $attachment->display_order,
            'proposed_section' => $selected['section'] ?? $attachment->report_section,
            'proposed_order' => $selected['order'] ?? $attachment->display_order,
            'reference_match' => $selected, 'sha256' => $hash,
        ];
    }
    $output = base_path('output/pdf/fidelity-preview/auction-'.$auctionId.'-classification.json');
    file_put_contents($output, json_encode($proposal, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    if (in_array('--apply', $argv, true)) {
        $backupDirectory = storage_path('app/private/backups');
        if (! is_dir($backupDirectory)) {
            mkdir($backupDirectory, 0775, true);
        }
        $backup = $backupDirectory.'/auction-'.$auctionId.'-attachment-classification-'.now()->format('Ymd-His-u').'.json';
        file_put_contents($backup, json_encode(['tenant' => $tenantId, 'auction_id' => $auctionId, 'attachments' => $proposal], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        DB::connection('tenant')->transaction(function () use ($auction, $proposal): void {
            foreach ($proposal as $item) {
                if ($item['reference_match'] === null) {
                    continue;
                }
                $attachment = $auction->attachments()->whereKey($item['id'])->lockForUpdate()->firstOrFail();
                if ($attachment->report_section !== $item['current_section'] || (int) $attachment->display_order !== (int) $item['current_order']) {
                    throw new RuntimeException('Attachment classification changed during review.');
                }
                $attachment->update(['report_section' => $item['proposed_section'], 'display_order' => $item['proposed_order']]);
            }
        });
        echo 'Applied approved attachment classification; backup: '.$backup.PHP_EOL;
    }
    echo json_encode(['auction' => $auctionId, 'attachments' => count($proposal), 'matched' => count(array_filter($proposal, static fn (array $item): bool => $item['reference_match'] !== null)), 'current' => array_count_values(array_column($proposal, 'current_section')), 'proposed' => array_count_values(array_column($proposal, 'proposed_section')), 'proposal_file' => $output], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
} finally {
    tenancy()->end();
}
