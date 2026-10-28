<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Permission\Models\Permission;

/** Development-only fixtures. Every write is guarded by the two exact CI schemas. */
final class AuctionReportAcceptanceFixture
{
    public function prepare(array $settings, string $receiptPath): array
    {
        AuctionReportDatabaseGuard::assertSafe($settings);
        $source = storage_path('app/private/backups/auction-restoration-source.json');
        $hash = hash_file('sha256', $source);
        $data = json_decode(file_get_contents($source), true, flags: JSON_THROW_ON_ERROR)['tables'];
        $template = config('database.connections.mysql');
        $tenant = Tenant::query()->firstOrCreate(['id' => $settings['tenant_id']], [
            'folderName' => $settings['tenant_id'], 'db_database' => $settings['tenant'], 'db_username' => $template['username'],
            'db_password' => $template['password'], 'db_host' => $template['host'], 'db_port' => (string) $template['port'],
            'company_name' => 'Report acceptance CI', 'commercial_number' => '2222222222', 'tax_number' => '300000000000003', 'access_type' => 'path',
        ]);
        AuctionReportDatabaseGuard::assertSafe(array_replace($settings, ['tenant' => (string) $tenant->db_database]));
        $tenant->domains()->firstOrCreate(['domain' => 'auction-report-ci.localhost']);
        tenancy()->initialize($tenant);
        try {
            if (is_file($receiptPath)) {
                $receipt = json_decode(file_get_contents($receiptPath), true, flags: JSON_THROW_ON_ERROR);
                if ($receipt['source_sha256'] !== $hash || DB::connection('tenant')->table('auctions')->whereIn('id', array_values($receipt['maps']['auctions']))->count() !== 2) {
                    throw new RuntimeException('Existing CI fixture receipt differs or its records are missing; refusing an implicit overwrite.');
                }

                return $receipt;
            }
            if (DB::connection('tenant')->table('auctions')->whereIn('name', array_column($data['auctions'], 'name'))->exists()) {
                throw new RuntimeException('CI already contains similarly named auctions without this fixture receipt.');
            }
            $owner = User::withoutEvents(fn () => User::query()->firstOrCreate(['email' => 'report-acceptance@invalid.example'], ['global_uid' => (string) \Illuminate\Support\Str::uuid(), 'name' => 'Report CI acceptance', 'password' => bcrypt(bin2hex(random_bytes(24))), 'status' => 'active']));
            foreach (['auctions.view', 'auctions.create', 'auctions.edit'] as $name) {
                $owner->givePermissionTo(Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'tenant']));
            }
            $snapshots = [];
            foreach (['haram', 'asir'] as $design) {
                $snapshot = json_decode(file_get_contents(base_path('output/pdf/remediation-acceptance/'.$design.'-snapshot.json')), true, flags: JSON_THROW_ON_ERROR);
                foreach ($snapshot['evidence'] as $section => $items) {
                    foreach ($items as $item) {
                        $snapshots[$item['id']] = $item + ['section' => $section];
                    }
                }
            }
            $sourceRoot = realpath(storage_path('app/tenants/test/public'));
            $copy = static function (?string $relative) use ($sourceRoot): void {
                if (empty($relative)) {
                    return;
                }
                $path = realpath($sourceRoot.DIRECTORY_SEPARATOR.$relative);
                if ($path === false || !str_starts_with(strtolower($path), strtolower($sourceRoot.DIRECTORY_SEPARATOR))) {
                    throw new RuntimeException('Source fixture media is missing or outside its explicit root.');
                }
                $bytes = file_get_contents($path);
                $disk = Storage::disk('tenant_public');
                if ($disk->exists($relative)) {
                    if (!hash_equals(hash('sha256', $bytes), hash('sha256', $disk->get($relative)))) {
                        throw new RuntimeException('Refusing to overwrite different CI media.');
                    }
                } else {
                    $disk->put($relative, $bytes);
                }
            };
            foreach ($data['auction_attachments'] as $attachment) {
                $copy($attachment['file_path']);
            }
            foreach ($data['auctions'] as $auction) {
                $copy($auction['report_image']);
                $copy($auction['cover_image']);
            }
            $database = DB::connection('tenant');
            $columns = [];
            $insert = static function (string $table, array $row) use ($database, $owner, &$columns): int {
                unset($row['id']);
                $columns[$table] ??= array_flip($database->getSchemaBuilder()->getColumnListing($table));
                $row = array_intersect_key($row, $columns[$table]);
                if (array_key_exists('created_by', $row) && $row['created_by'] !== null) {
                    $row['created_by'] = $owner->id;
                }

                return (int) $database->table($table)->insertGetId($row);
            };
            $maps = $database->transaction(static function () use ($data, $insert, $snapshots, $hash): array {
                $maps = ['assignments' => [], 'properties' => [], 'auctions' => []];
                foreach ($data['assignments'] as $row) {
                    $row['assignment_number'] = 'CI-REPORT-'.substr($hash, 0, 8).'-'.$row['id'];
                    $maps['assignments'][$row['id']] = $insert('assignments', $row);
                }
                foreach ($data['properties'] as $row) {
                    $sourceId = $row['id'];
                    $row['assignment_id'] = $maps['assignments'][$row['assignment_id']];
                    $maps['properties'][$sourceId] = $insert('properties', $row);
                }
                foreach ($data['auctions'] as $row) {
                    $sourceId = $row['id'];
                    $row['assignment_id'] = $maps['assignments'][$row['assignment_id']];
                    foreach (['brochure_template_id', 'billboard_template_id', 'social_template_id'] as $key) {
                        $row[$key] = null;
                    }
                    $maps['auctions'][$sourceId] = $insert('auctions', $row);
                }
                foreach (['auction_properties', 'auction_attachments'] as $table) {
                    foreach ($data[$table] as $row) {
                        if ($table === 'auction_attachments') {
                            $meta = $snapshots[$row['id']];
                            $row['report_section'] = $meta['section'];
                            $row['display_order'] = $meta['display_order'];
                            $row['report_metadata'] = json_encode(array_intersect_key($meta, array_flip(config('auction-report-inputs.metadata'))), JSON_THROW_ON_ERROR);
                        }
                        $row['auction_id'] = $maps['auctions'][$row['auction_id']];
                        $row['property_id'] = $row['property_id'] === null ? null : $maps['properties'][$row['property_id']];
                        $insert($table, $row);
                    }
                }

                return $maps;
            });
            $receipt = ['source_sha256' => $hash, 'tenant' => $settings['tenant_id'], 'database' => $database->getDatabaseName(), 'owner' => $owner->id, 'maps' => $maps];
            file_put_contents($receiptPath, json_encode($receipt, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return $receipt;
        } finally {
            tenancy()->end();
        }
    }
}
