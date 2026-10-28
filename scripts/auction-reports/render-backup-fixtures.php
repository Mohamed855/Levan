<?php

declare(strict_types=1);

use App\Contracts\Auctions\BrowserDocumentRenderer;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionAttachment;
use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Auctions\Reports\AuctionReportDataBuilder;
use App\Services\Tenants\Auctions\Reports\AuctionReportRenderer;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require dirname(__DIR__, 2).'/vendor/autoload.php';
ini_set('memory_limit', '1024M');
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
app()->setLocale('ar');
// File-only acceptance: no tenant is initialized and no business record is saved.
DB::listen(static function (): void { throw new RuntimeException('File acceptance must not execute a database query.'); });
$directory = base_path('output/pdf/remediation-acceptance');
if (!is_dir($directory)) {
    mkdir($directory, 0775, true);
}
$source = storage_path('app/private/backups/auction-restoration-source.json');
$data = json_decode(file_get_contents($source), true, flags: JSON_THROW_ON_ERROR);
$tables = $data['tables'];
$mediaRoot = storage_path('app/tenants/test/public');
config(['filesystems.disks.tenant_public.root' => $mediaRoot]);
Storage::forgetDisk('tenant_public');
$classifications = [];
foreach (['auction-20-attachment-classification-20261027-104910-473700.json', 'auction-21-attachment-classification-20261027-104912-966710.json'] as $filename) {
    $classification = json_decode(file_get_contents(storage_path('app/private/backups/'.$filename)), true, flags: JSON_THROW_ON_ERROR);
    foreach ($classification['attachments'] as $item) {
        $classifications[$item['file']] = $item;
    }
}
$receipt = ['source_sha256' => hash_file('sha256', $source), 'mode' => 'file-only / original backup values / approved hash-based classification', 'database_queries' => 0, 'auctions' => []];
foreach ($tables['auctions'] as $row) {
    $design = $row['name'] === 'مزاد جوار الحرم' ? 'haram' : 'asir';
    $auction = (new Auction)->setRawAttributes($row);
    $assignment = array_values(array_filter($tables['assignments'], static fn ($item): bool => $item['id'] === $row['assignment_id']))[0];
    $auction->setRelation('assignment', (new Assignment)->setRawAttributes($assignment));
    $pivots = array_values(array_filter($tables['auction_properties'], static fn ($item): bool => $item['auction_id'] === $row['id']));
    usort($pivots, static fn ($a, $b): int => (int) $a['sort_order'] <=> (int) $b['sort_order']);
    $properties = [];
    foreach ($pivots as $pivot) {
        $attributes = array_values(array_filter($tables['properties'], static fn ($item): bool => $item['id'] === $pivot['property_id']))[0];
        $property = (new Property)->setRawAttributes($attributes);
        $property->setRelation('pivot', Pivot::fromRawAttributes($auction, $pivot, 'auction_properties', true));
        $property->setRelation('galleryAttachments', new Collection);
        $properties[] = $property;
    }
    $auction->setRelation('properties', new Collection($properties));
    $attachments = [];
    $inventory = [];
    foreach ($tables['auction_attachments'] as $attributes) {
        if ($attributes['auction_id'] !== $row['id']) {
            continue;
        }
        $path = realpath($mediaRoot.'/'.$attributes['file_path']);
        $root = realpath($mediaRoot);
        if ($path === false || !str_starts_with(strtolower($path), strtolower($root.DIRECTORY_SEPARATOR))) {
            throw new RuntimeException('Acceptance evidence is missing or outside the source media root.');
        }
        $hash = hash_file('sha256', $path);
        $classification = $classifications[$attributes['file_name']] ?? null;
        $meta = ['kind' => str_starts_with($attributes['report_section'], 'board_') ? 'photo' : 'screenshot'];
        if ($classification !== null) {
            if (!hash_equals($classification['sha256'], $hash)) {
                throw new RuntimeException('The approved media classification no longer matches this file.');
            }
            $attributes['report_section'] = $classification['proposed_section'];
            $attributes['display_order'] = $classification['proposed_order'];
            if ($attributes['report_section'] === 'social') {
                $meta['channel'] = ['i48' => 'snapchat', 'i14' => 'tiktok', 'i15' => 'tiktok', 'i32' => 'x'][$classification['reference_match']['reference_image']] ?? 'other';
            }
            if (in_array($attributes['report_section'], ['x', 'video', 'whatsapp'], true)) {
                $meta['channel'] = $attributes['report_section'] === 'video' ? 'youtube' : $attributes['report_section'];
            }
        }
        $attributes['report_metadata'] = json_encode($meta, JSON_THROW_ON_ERROR);
        $attachments[] = (new AuctionAttachment)->setRawAttributes($attributes);
        $inventory[] = ['id' => (int) $attributes['id'], 'name' => $attributes['file_name'], 'section' => $attributes['report_section'], 'order' => (int) $attributes['display_order'], 'sha256' => $hash, 'present' => true, 'kind' => $meta['kind'], 'channel' => $meta['channel'] ?? null];
    }
    usort($attachments, static fn ($a, $b): int => [$a->report_section, $a->display_order, $a->id] <=> [$b->report_section, $b->display_order, $b->id]);
    $auction->setRelation('attachments', new Collection($attachments));
    $snapshot = app(AuctionReportDataBuilder::class)->build($auction);
    $snapshot['generated_at'] = $row['updated_at'];
    $totals = $snapshot['totals'];
    $expected = $design === 'haram' ? [11, 4, 7, 5670000.0] : [13, 3, 10, 4026000.0];
    if ([$totals['properties_count'], $totals['sold_count'], $totals['unsold_count'], $totals['recorded_sales_total']] !== $expected) {
        throw new RuntimeException('The original backup does not have the expected business totals.');
    }
    $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    file_put_contents($directory.'/'.$design.'-snapshot.json', $json);
    $item = ['name' => $row['name'], 'snapshot_sha256' => hash('sha256', $json), 'totals' => $totals, 'property_ids' => array_column($snapshot['properties'], 'id'), 'media' => $inventory, 'documents' => []];
    foreach (['outputs', 'report'] as $type) {
        $html = app(AuctionReportRenderer::class)->html($snapshot, $type);
        $path = $directory.'/'.$design.'-'.$type;
        file_put_contents($path.'.html', $html);
        preg_match_all('/data-page-type="([^"]+)"/', $html, $matches);
        if (!in_array('--html-only', $argv, true)) {
            app(BrowserDocumentRenderer::class)->pdf($html, $path.'.pdf');
        }
        $item['documents'][$type] = ['path' => $path.'.pdf', 'page_families' => array_count_values($matches[1]), 'pages' => count($matches[1])];
    }
    $receipt['auctions'][$design] = $item;
    echo $design.' '.json_encode(array_map(static fn ($doc): int => $doc['pages'], $item['documents'])).PHP_EOL;
}
file_put_contents($directory.'/receipt.json', json_encode($receipt, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo 'Read-only backup acceptance receipt: '.$directory.'/receipt.json'.PHP_EOL;
