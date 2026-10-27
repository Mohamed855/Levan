<?php

declare(strict_types=1);

use App\Models\Admin\Tenants\Tenant;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$source = storage_path('app/private/backups/auction-restoration-source.json');
$archive = storage_path('app/private/backups/etmam_levan_test-20261026-125042.sql.gz');
$data = json_decode(file_get_contents($source), true, flags: JSON_THROW_ON_ERROR);
if (! hash_equals($data['source_sha256'], hash_file('sha256', $archive))) {
    throw new RuntimeException('The restoration archive hash changed.');
}
$tables = $data['tables'];
if (count($tables['auctions']) !== 2 || count($tables['properties']) !== 24 || count($tables['auction_attachments']) !== 62) {
    throw new RuntimeException('Unexpected restoration record counts.');
}
tenancy()->initialize(Tenant::query()->findOrFail('test'));
try {
    $database = DB::connection('tenant');
    $names = array_column($tables['auctions'], 'name');
    if ($database->table('auctions')->whereIn('name', $names)->exists()) {
        throw new RuntimeException('An acceptance auction already exists; refusing to create duplicates.');
    }
    $missing = [];
    foreach ($tables['auction_attachments'] as $attachment) {
        if (! Storage::disk('tenant_public')->exists($attachment['file_path'])) {
            $missing[] = $attachment['file_path'];
        }
    }
    foreach ($tables['auctions'] as $auction) {
        foreach (['cover_image', 'report_image'] as $field) {
            if ($auction[$field] && ! Storage::disk('tenant_public')->exists($auction[$field])) {
                $missing[] = $auction[$field];
            }
        }
    }
    echo json_encode(['database' => $database->getDatabaseName(), 'auctions' => $names, 'properties' => 24, 'attachments' => 62, 'missing_files' => count($missing)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
    if ($missing !== []) {
        throw new RuntimeException('Restore the missing tenant files before inserting records.');
    }
    if (! in_array('--apply', $argv, true)) {
        exit(0);
    }
    $creator = $database->table('users')->orderBy('id')->value('id');
    if ($creator === null) {
        throw new RuntimeException('A tenant user is required.');
    }
    $columns = [];
    $insert = static function (string $table, array $row) use ($database, $creator, &$columns): int {
        unset($row['id']);
        $columns[$table] ??= array_flip($database->getSchemaBuilder()->getColumnListing($table));
        $row = array_intersect_key($row, $columns[$table]);
        if (array_key_exists('created_by', $row) && $row['created_by'] !== null) {
            $row['created_by'] = $creator;
        }

        return (int) $database->table($table)->insertGetId($row);
    };
    $maps = $database->transaction(static function () use ($tables, $insert): array {
        $maps = ['assignments' => [], 'properties' => [], 'auctions' => []];
        foreach ($tables['assignments'] as $row) {
            $maps['assignments'][$row['id']] = $insert('assignments', $row);
        }
        foreach ($tables['properties'] as $row) {
            $original = $row['id'];
            $row['assignment_id'] = $maps['assignments'][$row['assignment_id']];
            $maps['properties'][$original] = $insert('properties', $row);
        }
        foreach ($tables['auctions'] as $row) {
            $original = $row['id'];
            $row['assignment_id'] = $maps['assignments'][$row['assignment_id']];
            foreach (['brochure_template_id', 'billboard_template_id', 'social_template_id'] as $field) {
                $row[$field] = null;
            }
            $maps['auctions'][$original] = $insert('auctions', $row);
        }
        foreach (['auction_properties', 'auction_attachments'] as $table) {
            foreach ($tables[$table] as $row) {
                $row['auction_id'] = $maps['auctions'][$row['auction_id']];
                if ($row['property_id'] !== null) {
                    $row['property_id'] = $maps['properties'][$row['property_id']];
                }
                $insert($table, $row);
            }
        }

        return $maps;
    });
    $receipt = storage_path('app/private/backups/auction-restoration-'.now()->format('Ymd-His').'.json');
    file_put_contents($receipt, json_encode(['source_sha256' => $data['source_sha256'], 'maps' => $maps], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    echo json_encode(['restored' => $maps['auctions'], 'receipt' => $receipt], JSON_PRETTY_PRINT).PHP_EOL;
} finally {
    tenancy()->end();
}
