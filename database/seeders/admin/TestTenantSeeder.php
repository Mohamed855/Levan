<?php

declare(strict_types=1);

namespace Database\Seeders\admin;

use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\City;
use App\Models\Admin\Licenses\LicenseSeat;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Models\Admin\Tenants\Domain;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use Database\Seeders\tenant\AuctionsAndPropertiesSeeder;
use Database\Seeders\tenant\IssuingEntitySeeder;
use Database\Seeders\tenant\TenantDatabaseSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestTenantSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('seeding.test_seeders_enabled', false)) {
            $this->command?->warn('TestTenantSeeder is disabled. Set TEST_SEEDERS_ENABLED=true to enable test data.');

            return;
        }

        if (tenancy()->initialized) {
            throw new \RuntimeException('TestTenantSeeder must run from the central database context.');
        }
        $central = DB::connection('mysql')->getConfig();
        $tenantDatabase = (string) config('seeding.test_tenant_database');
        if (! preg_match('/^[A-Za-z0-9_]+$/D', $tenantDatabase) || strcasecmp($tenantDatabase, (string) ($central['database'] ?? '')) === 0) {
            throw new \RuntimeException('TEST_TENANT_DATABASE must name a separate, valid test database.');
        }
        $this->prepareTestDatabase($central, $tenantDatabase);

        $adminUser = AdminUser::first();
        if (! $adminUser) {
            $this->call(AdminUserSeeder::class);
            $adminUser = AdminUser::first();
        }

        $plan = Plan::updateOrCreate(
            ['name' => 'الباقة الاحترافية الشاملة'],
            [
                'description' => 'باقة متكاملة لجميع العمليات والتحصيل بدون قيود',
                'price' => 5000.00,
                'yearly_renewal_price' => 3000.00,
                'extra_user_license_price' => 150.00,
                'discount_percentage' => 0,
                'is_active' => true,
                'included_users' => 10,
                'subscription_duration_type' => 'limited',
                'subscription_billing_cycle' => 'yearly',
                'domain_management_enabled' => true,
                'created_by' => $adminUser?->id ?? 1,
            ]
        );

        // 2. Create Test Tenant
        $tenant = Tenant::updateOrCreate(
            ['id' => 'test'],
            [
                'db_database' => $tenantDatabase,
                'db_username' => $central['username'],
                'db_password' => $central['password'],
                'db_host' => $central['host'],
                'db_port' => $central['port'],
                'folderName' => 'test',
                'company_name' => 'تجربة',
                'commercial_number' => '2222222222',
                'tax_number' => '300000000000003',
                'city_id' => City::firstOrCreate(['name' => 'الرياض'])->id,
                'address' => 'حي العليا، الرياض',
                'access_type' => 'path',
                'custom_domain_active' => 0,
                'api_token' => hash('sha256', 'test-tenant-token'),
                'data' => [],
            ]
        );

        Cache::forget("tenant_connection_{$tenant->id}");

        // 3. Create Domain mapping
        Domain::updateOrCreate(
            ['domain' => 'test.localhost'],
            [
                'tenant_id' => $tenant->id,
                'is_active' => '1',
                'type' => 'linked',
            ]
        );

        // 4. Run Tenant Migrations and Seeders
        $migrationPath = database_path('migrations/tenant');
        $hasTenantMigrations = glob($migrationPath.'/*.php') !== [];

        if (! $hasTenantMigrations) {
            $this->command?->warn('Test tenant record created; tenant migrations are not present.');

            return;
        }

        $migrationExitCode = Artisan::call('tenants:migrate-fresh', [
            '--tenants' => [$tenant->id],
        ]);
        if ($migrationExitCode !== 0) {
            throw new \RuntimeException('Test tenant migrations failed; seeding was stopped.');
        }

        tenancy()->initialize($tenant);
        try {
            $this->call(TenantDatabaseSeeder::class);
            $this->call(IssuingEntitySeeder::class);
            $this->call(AuctionsAndPropertiesSeeder::class);
        } finally {
            tenancy()->end();
        }

        // 5. Create Active Subscription for Test Tenant
        $subscription = Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $plan->id,
                'subscription_number' => 'TEST-SUBSCRIPTION',
                'starts_at' => now()->subMonths(1),
                'status' => SubscriptionStatus::ACTIVE,
                'price' => 5000.00,
                'total_users' => 10,
                'used_users' => 4,
                'auto_renew' => true,
            ]
        );

        // 6. Create Active Subscription Period
        SubscriptionPeriod::updateOrCreate(
            [
                'subscription_id' => $subscription->id,
                'is_current' => true,
            ],
            [
                'period_number' => 1,
                'period_start' => now()->subMonths(1),
                'period_end' => now()->addYears(1),
                'plan_id_snapshot' => $plan->id,
                'price_snapshot' => 5000.00,
                'total_users_snapshot' => 10,
                'used_users_snapshot' => 4,
                'status' => SubscriptionPeriodStatus::ACTIVE,
            ]
        );

        // 7. Assign License Seats to Tenant Users
        $seatMap = [
            'demo@lot.com' => '11111111-1111-1111-1111-000000000001',
            'ahmed@lot.com' => '11111111-1111-1111-1111-000000000002',
            'sara@lot.com' => '11111111-1111-1111-1111-000000000003',
            'khalid@lot.com' => '11111111-1111-1111-1111-000000000004',
        ];

        tenancy()->initialize($tenant);
        try {
            $users = User::all();
            foreach ($users as $user) {
                $seatId = $seatMap[$user->email] ?? (string) Str::uuid();
                $seat = LicenseSeat::updateOrCreate(
                    [
                        'id' => $seatId,
                    ],
                    [
                        'tenant_id' => $tenant->id,
                        'subscription_id' => $subscription->id,
                        'assigned_user_uid' => $user->global_uid,
                        'status' => 'assigned',
                        'assigned_at' => now(),
                        'is_core' => $user->email === 'demo@lot.com',
                    ]
                );
            }
        } finally {
            tenancy()->end();
        }

        $this->command?->info('Test tenant [test] created, migrated, seeded, and activated successfully.');
    }

    private function prepareTestDatabase(array $central, string $database): void
    {
        $probeName = 'test_tenant_seed_probe';
        $configuration = array_replace($central, ['name' => $probeName, 'database' => $database]);
        // A cached URL or read/write override must never redirect the probe to central.
        unset($configuration['url'], $configuration['read'], $configuration['write']);

        try {
            $probe = DB::build($configuration);
            try {
                $probe->getPdo();

                return;
            } catch (\PDOException $exception) {
                // Only an unknown database justifies attempting CREATE DATABASE.
                if ((int) ($exception->errorInfo[1] ?? 0) !== 1049) {
                    throw $exception;
                }
            }
            DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $probe->getPdo();
        } catch (\PDOException $exception) {
            throw new \RuntimeException(
                "Unable to access test database [{$database}]. Check TEST_TENANT_DATABASE and the MySQL connection settings. "
                .'On managed hosting, create the database in your hosting control panel and grant the configured MySQL user access.',
                0,
                $exception,
            );
        } finally {
            DB::purge($probeName);
        }
    }
}
