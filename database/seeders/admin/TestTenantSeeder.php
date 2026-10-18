<?php

namespace Database\Seeders\admin;

use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\City;
use App\Models\Admin\Licenses\LicenseSeat;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Models\Admin\Tenants\Domain;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Version\Version;
use App\Models\Tenants\UserMangment\User\User;
use Database\Seeders\tenant\TenantDatabaseSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestTenantSeeder extends Seeder
{
    public function run(): void
    {
        $version = Version::first();

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
                'created_by' => 1,
            ]
        );

        // 2. Create Test Tenant
        $tenant = Tenant::updateOrCreate(
            ['id' => 'test'],
            [
                'db_database' => env('TEST_TENANT_DATABASE', 'levan_test'),
                'db_username' => env('DB_USERNAME', 'root'),
                'db_password' => env('DB_PASSWORD', ''),
                'db_host' => env('DB_HOST', 'localhost'),
                'db_port' => env('DB_PORT', '3306'),
                'folderName' => 'test',
                'company_name' => 'تجربة',
                'commercial_number' => '2222222222',
                'version_id' => $version?->id ?? 1,
                'tax_number' => '300000000000003',
                'city_id' => City::firstOrCreate(['name' => 'الرياض'])->id,
                'address' => 'حي العليا، الرياض',
                'access_type' => 'path',
                'custom_domain_active' => 0,
                'api_token' => hash('sha256', 'test-tenant-token'),
                'data' => [],
            ]
        );

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

        $tenantDbName = $tenant->db_database ?? env('TEST_TENANT_DATABASE', 'etmam_levan_test');
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$tenantDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        Artisan::call('tenants:migrate-fresh', [
            '--tenants' => [$tenant->id],
        ]);

        tenancy()->initialize($tenant);
        try {
            $this->call(TenantDatabaseSeeder::class);
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
            'demo@levan.com' => '11111111-1111-1111-1111-000000000001',
            'ahmed@levan.com' => '11111111-1111-1111-1111-000000000002',
            'sara@levan.com' => '11111111-1111-1111-1111-000000000003',
            'khalid@levan.com' => '11111111-1111-1111-1111-000000000004',
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
                        'is_core' => $user->email === 'demo@levan.com',
                    ]
                );
                $user->update(['license_seat_id' => $seat->id]);
            }
        } finally {
            tenancy()->end();
        }

        $this->command?->info('Test tenant [test] created, migrated, seeded, and activated successfully.');
    }
}
