<?php

namespace Database\Seeders\admin;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Licenses\LicenseSeat;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Tenants\UserMangment\User\User;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Version\Version;
use Database\Seeders\tenant\TenantDatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestTenantSeeder extends Seeder
{
    public function run(): void
    {
        $version = Version::first();

        $tenant = Tenant::updateOrCreate(
            ['id' => 'test'],
            [
                'db_database' => env('TEST_TENANT_DATABASE', 'etmam_levan_test'),
                'db_username' => env('DB_USERNAME', 'admin'),
                'db_password' => env('DB_PASSWORD', 'admin'),
                'db_host'     => env('DB_HOST', '127.0.0.1'),
                'db_port'     => env('DB_PORT', '3306'),
                'folderName'  => 'test',
                'company_name' => 'مستخدم النسخة الثانية',
                'commercial_number' => '2222222222',
                'version_id'  => $version?->id ?? 1,
                'access_type' => 'path',
                'custom_domain_active' => 0,
                'api_token'   => hash('sha256', 'test-tenant-token'),
                'data'        => [],
            ]
        );

        $migrationPath = database_path('migrations/tenant');
        $hasTenantMigrations = glob($migrationPath . '/*.php') !== [];

        if (!$hasTenantMigrations) {
            $this->command?->warn('Test tenant record created; tenant migrations are not present, so its database was not initialized.');
            return;
        }

        Artisan::call('tenants:migrate', [
            '--tenants' => [$tenant->id],
            '--force' => true,
        ]);

        tenancy()->initialize($tenant);

        try {
            $this->call(TenantDatabaseSeeder::class);
        } finally {
            tenancy()->end();
        }

        $subscription = Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'subscription_number' => 'TEST-SUBSCRIPTION',
                'starts_at' => now(),
                'status' => SubscriptionStatus::ACTIVE,
                'price' => 0,
                'total_users' => 10,
                'used_users' => 1,
                'auto_renew' => false,
            ]
        );

        tenancy()->initialize($tenant);
        try {
            $user = User::where('email', 'demo@levan.com')->firstOrFail();
        } finally {
            tenancy()->end();
        }

        $seat = LicenseSeat::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
                'assigned_user_uid' => $user->global_uid,
            ],
            [
                'id' => (string) Str::uuid(),
                'status' => 'assigned',
                'assigned_at' => now(),
                'is_core' => true,
            ]
        );

        tenancy()->initialize($tenant);
        try {
            $user->update(['license_seat_id' => $seat->id]);
        } finally {
            tenancy()->end();
        }

        $this->command?->info('Test tenant [test] created and initialized.');
    }
}
