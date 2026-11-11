<?php

declare(strict_types=1);

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'mysql';

    private const BILL_TYPES = ['new_domain', 'transfer_domain', 'renewal_domain', 'domain'];

    private const RETIRED_TABLES = ['domain_payment_attempts', 'transfer_domains', 'cpanel_accounts', 'control_domains'];

    private const RETIRED_KEYS = [
        'domain', 'domains', 'domain_id', 'domain_name', 'domain_management', 'domain_management_enabled',
        'custom_domain_active', 'domain_activated_at', 'registered_domains', 'domain_payment_attempt_id',
        'namecheap', 'whm', 'server_domain', 'transfer_code', 'trasnfer_status',
    ];

    private array $retiredDomains = [];

    public function up(): void
    {
        if (! app()->environment(['local', 'testing', 'development'])) {
            throw new LogicException('This irreversible development-data cleanup cannot run in this environment.');
        }

        $central = DB::connection($this->getConnection());
        $connections = [$central];
        $temporaryConnections = [];

        try {
            if ($central->getSchemaBuilder()->hasTable('tenants')) {
                foreach ($central->table('tenants')->get() as $tenant) {
                    if (empty($tenant->db_database) || $tenant->db_database === $central->getDatabaseName()) {
                        continue;
                    }
                    $name = 'retired_domain_cleanup_'.sha1((string) $tenant->id);
                    $configuration = $central->getConfig();
                    unset($configuration['name']);
                    $configuration['url'] = null;
                    $configuration['database'] = $tenant->db_database;
                    foreach (['host', 'port', 'username', 'password'] as $field) {
                        if (isset($tenant->{'db_'.$field}) && ($field === 'password' || $tenant->{'db_'.$field} !== '')) {
                            $configuration[$field] = $tenant->{'db_'.$field};
                        }
                    }
                    config(['database.connections.'.$name => $configuration]);
                    DB::purge($name);
                    $temporaryConnections[] = $name;
                    $connections[] = DB::connection($name);
                }
            }

            // Establish every configured connection before deleting any development data.
            foreach ($connections as $connection) {
                $connection->getPdo();
            }
            foreach ($connections as $connection) {
                if ($connection->getSchemaBuilder()->hasTable('domains')) {
                    $this->retiredDomains = array_merge($this->retiredDomains, $connection->table('domains')->pluck('domain')->all());
                }
            }
            foreach ($connections as $connection) {
                $this->purgeDatabase($connection);
            }
        } finally {
            foreach ($temporaryConnections as $name) {
                DB::purge($name);
                config(['database.connections.'.$name => null]);
            }
        }
    }

    public function down(): void
    {
        throw new LogicException('Permanently deleted domain data cannot be restored by a migration rollback.');
    }

    private function purgeDatabase(Connection $connection): void
    {
        $schema = $connection->getSchemaBuilder();
        $schemaName = $connection->getDriverName() === 'sqlite' ? 'main' : $connection->getDatabaseName();
        $tables = $schema->getTableListing($schemaName, false);

        if ($schema->hasTable('permissions')) {
            $permissions = $connection->table('permissions')->where(function ($query): void {
                $query->where('name', 'like', 'domain.%')->orWhere('name', 'domain_extension_prices.settings');
            })->pluck('id');
            foreach (['role_has_permissions', 'model_has_permissions'] as $pivot) {
                if ($schema->hasTable($pivot)) {
                    $connection->table($pivot)->whereIn('permission_id', $permissions)->delete();
                }
            }
            $connection->table('permissions')->whereIn('id', $permissions)->delete();
        }

        if ($schema->hasTable('bills') && $schema->hasColumn('bills', 'type')) {
            $connection->table('bills')->whereIn('type', self::BILL_TYPES)->delete();
        }

        foreach ($tables as $table) {
            if (preg_match('/^activity_log(?:s|_\d{4}_\d{2})?$/', $table) && $schema->hasColumn($table, 'subject_type')) {
                $connection->table($table)->whereIn('subject_type', [
                    'App\\Models\\Admin\\Tenants\\Domain',
                    'Stancl\\Tenancy\\Database\\Models\\Domain',
                    'App\\Models\\Admin\\ControlDomain\\ControlDomain',
                    'App\\Models\\Admin\\Cpanel\\CpanelAccount',
                    'App\\Models\\Admin\\Payments\\DomainPaymentAttempt',
                ])->delete();
            }
        }

        foreach (['jobs' => 'payload', 'failed_jobs' => 'payload', 'job_batches' => 'options', 'notifications' => 'data', 'sessions' => 'payload', 'cache' => 'value'] as $table => $column) {
            if (! $schema->hasTable($table) || ! $schema->hasColumn($table, $column)) {
                continue;
            }
            $key = $table === 'cache' ? 'key' : 'id';
            foreach ($connection->table($table)->select([$key, $column])->cursor() as $row) {
                $payload = (string) $row->{$column};
                if ($table === 'sessions') {
                    $payload = base64_decode($payload, true) ?: $payload;
                }
                $payload = str_replace('\\\\', '\\', $payload);
                if ($this->isRetiredPayload($payload)) {
                    $connection->table($table)->where($key, $row->{$key})->delete();
                }
            }
        }

        if ($schema->hasTable('telescope_entries') && $schema->hasColumn('telescope_entries', 'uuid')) {
            $connection->table('telescope_entries')->select(['sequence', 'uuid', 'content'])
                ->chunkById(500, function ($entries) use ($connection, $schema): void {
                    $ids = $entries->filter(fn ($entry): bool => $this->isRetiredPayload((string) $entry->content))->pluck('uuid');
                    if ($ids->isEmpty()) {
                        return;
                    }
                    if ($schema->hasTable('telescope_entries_tags')) {
                        $connection->table('telescope_entries_tags')->whereIn('entry_uuid', $ids)->delete();
                    }
                    $connection->table('telescope_entries')->whereIn('uuid', $ids)->delete();
                }, 'sequence');
        }

        foreach (['cache', 'cache_locks'] as $table) {
            if ($schema->hasTable($table)) {
                foreach ($connection->table($table)->select('key')->cursor() as $row) {
                    if (preg_match('/domain_(?:info|check|availability|price)|domain-(?:ownership|operation|hosting|connection):|spatie\.permission\.cache/', $row->key)) {
                        $connection->table($table)->where('key', $row->key)->delete();
                    }
                }
            }
        }

        foreach (self::RETIRED_TABLES as $table) {
            $schema->dropIfExists($table);
        }
        if ($schema->hasTable('domains')) {
            $connection->table('domains')->delete();
            $columns = array_values(array_intersect(['is_active', 'type', 'transfer_code', 'trasnfer_status'], $schema->getColumnListing('domains')));
            if ($columns !== []) {
                $schema->table('domains', fn (Blueprint $table) => $table->dropColumn($columns));
            }
        }
        if ($schema->hasTable('tenants')) {
            if ($schema->hasColumn('tenants', 'access_type')) {
                $connection->table('tenants')->update(['access_type' => 'path']);
            }
            $columns = array_values(array_intersect(['custom_domain_active', 'domain_activated_at'], $schema->getColumnListing('tenants')));
            if ($columns !== []) {
                $schema->table('tenants', fn (Blueprint $table) => $table->dropColumn($columns));
            }
        }
        if ($schema->hasTable('plans') && $schema->hasColumn('plans', 'domain_management_enabled')) {
            $schema->table('plans', fn (Blueprint $table) => $table->dropColumn('domain_management_enabled'));
        }
        if ($schema->hasTable('migrations')) {
            $connection->table('migrations')->whereIn('migration', [
                '2026_09_23_000012_create_control_domains_table',
                '2026_09_23_000013_create_cpanel_accounts_table',
                '2026_10_06_000001_create_domain_payment_attempts_table',
            ])->delete();
        }

        foreach ($schema->getTableListing($schemaName, false) as $table) {
            $columns = $schema->getColumnListing($table);
            if (! in_array('id', $columns, true)) {
                continue;
            }
            foreach (array_intersect(['data', 'metadata', 'features', 'properties', 'attribute_changes', 'settings', 'options', 'content', 'value'], $columns) as $column) {
                foreach ($connection->table($table)->select(['id', $column])->whereNotNull($column)->cursor() as $row) {
                    $value = json_decode((string) $row->{$column}, true);
                    if (! is_array($value)) {
                        continue;
                    }
                    $clean = $this->cleanJson($value);
                    if ($clean !== $value) {
                        $connection->table($table)->where('id', $row->id)->update([$column => json_encode($clean, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
                    }
                }
            }
        }
    }

    private function cleanJson(array $value): array
    {
        $isList = array_is_list($value);
        foreach ($value as $key => $item) {
            $retiredValue = is_string($item) && (in_array($item, self::BILL_TYPES, true)
                || in_array($item, $this->retiredDomains, true)
                || preg_match('/^(domain\.|domain_management|domain_extension_prices\.)/', $item)
                || in_array($item, ['إدارة النطاقات', 'إعدادات الدومين', 'إدارة الدومينات'], true));
            if (in_array((string) $key, self::RETIRED_KEYS, true) || $retiredValue) {
                unset($value[$key]);
            } elseif (is_array($item)) {
                $value[$key] = $this->cleanJson($item);
            }
        }

        return $isList ? array_values($value) : $value;
    }

    private function isRetiredPayload(string $payload): bool
    {
        $payload = str_replace('\\\\', '\\', $payload);
        $payload = str_replace('\\/', '/', $payload);
        if (preg_match('/"(?:type|operation_type)"\s*:\s*"domain"/', $payload)) {
            return true;
        }
        foreach (array_merge($this->retiredDomains, array_diff(self::BILL_TYPES, ['domain']), [
            'App\\Jobs\\Domain\\', 'App\\Jobs\\Tenants\\TransferDomain\\',
            'App\\Models\\Admin\\ControlDomain\\', 'DomainPaymentAttempt', 'NameCheap',
            'DomainController', 'DomainDataTable', 'ControlDomain',
            '\\Http\\Requests\\Tenants\\Domains\\', '\\Services\\Shared\\Domain\\',
            '/domains/', 'domain_management', 'domain.view', 'domain.reserve', 'domain.connect', 'domain.transfer',
            'control_domains', 'domain_payment_attempts', 'cpanel_accounts', 'custom_domain_active', 'domain_activated_at',
        ]) as $marker) {
            if ($marker !== '' && stripos($payload, $marker) !== false) {
                return true;
            }
        }

        return false;
    }
};
