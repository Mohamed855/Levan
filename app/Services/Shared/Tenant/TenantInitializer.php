<?php

namespace App\Services\Shared\Tenant;

use App\Models\Admin\Licenses\LicenseSeat;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Settings\Settings;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class TenantInitializer
{
    public function __construct(private array $seeders) {}

    public function setupTenantEnvironment(Tenant $tenant, array $metadata)
    {
        tenancy()->initialize($tenant);

        try {
            $this->runMigrations($tenant); // تشغيل Migrations
            $this->runSeeders();
            $user = $this->createFirstUser($metadata);    // إنشاء المستخدم الأول
            $this->createSettings($metadata); // إنشاء الإعدادات الأساسية

            return $user;
        } finally {
            // إنهاء جلسة التيننت
            tenancy()->end();
        }
    }

    public function createCoreSeat(Tenant $tenant, Subscription $subscription, User $user): void
    {
        LicenseSeat::firstOrCreate([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'is_core' => true,
        ], [
            'assigned_user_uid' => $user->global_uid,
            'status' => 'assigned',
            'is_core' => true,
            'assigned_at' => now(),
        ]);
    }

    /*
    |============================================================================
    |============================================================================
    |                           private methods
    |============================================================================
    |============================================================================
    */
    private function runMigrations(Tenant $tenant): void
    {
        $exitCode = Artisan::call('tenants:migrate', [
            '--tenants' => [$tenant->id],
            '--force' => true,
        ]);
        if ($exitCode !== 0) {
            throw new \RuntimeException('Tenant database migrations failed: '.Artisan::output());
        }
    }

    private function createFirstUser(array $metadata): User
    {
        $user = User::firstOrCreate(['email' => $metadata['billing_email']], [
            'name' => $metadata['billing_name'] ?? 'مستخدم جديد',
            'email' => $metadata['billing_email'],
            'email_verified_at' => now(),
            'password' => $metadata['billing_password_hash'] ?? Hash::make($metadata['billing_password']),
            'phone' => $metadata['billing_phone'],
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->syncRoles(['Admin']);
        $user->syncPermissions(Permission::where('guard_name', 'tenant')->get());
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function createSettings(array $metadata): void
    {
        $allRegions = app(CentralCityOptionsGateway::class)->allRegionOptionsByName();
        $defaultRegions = ! empty($allRegions) ? array_values($allRegions) : [
            'المنطقة الشرقية',
            'منطقة الباحة',
            'منطقة الجوف',
            'منطقة الحدود الشمالية',
            'منطقة الرياض',
            'منطقة القصيم',
            'منطقة المدينة المنورة',
            'منطقة تبوك',
            'منطقة جازان',
            'منطقة حائل',
            'منطقة عسير',
            'منطقة مكة المكرمة',
            'منطقة نجران',
        ];

        $settings = Settings::first();
        if ($settings) {
            if (empty($settings->operational_regions)) {
                $settings->operational_regions = $defaultRegions;
            }
            if (! empty($metadata['billing_company_name'])) {
                $settings->office_name = $metadata['billing_company_name'];
            }
            $settings->save();
        } else {
            Settings::create([
                'image' => 'default-office.png',
                'office_name' => $metadata['billing_company_name'] ?? 'لوت LOT',
                'logo_text' => 'لوت LOT',
                'operational_regions' => $defaultRegions,
            ]);
        }
    }

    private function runSeeders(): void
    {
        foreach ($this->seeders as $seeder) {
            if (is_string($seeder)) {
                app($seeder)->run();
            } elseif (is_object($seeder) && method_exists($seeder, 'run')) {
                $seeder->run();
            } elseif (is_object($seeder) && method_exists($seeder, 'seed')) {
                $seeder->seed();
            }
        }
    }
}
