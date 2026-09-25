<?php

namespace App\Services\shared\Tenant;

use App\Models\Admin\Licenses\LicenseSeat;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Settings\Settings;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

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
        $seat = LicenseSeat::create([
            'tenant_id'         => $tenant->id,
            'subscription_id'   => $subscription->id,
            'assigned_user_uid' => $user->global_uid,
            'status'            => 'assigned',
            'is_core'           => true,
            'assigned_at'       => now(),
        ]);

        // العودة إلى بيئة التيننت لتحديث المستخدم
        tenancy()->initialize($tenant);
        $user->update(['license_seat_id' => $seat->id]);
        tenancy()->end();
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
        Artisan::call('tenants:migrate', [
            '--tenants' => [$tenant->id]
        ]);
    }

    private function createFirstUser(array $metadata): User
    {
        $user = User::create([
            'name'              => $metadata['billing_name'] ?? 'مستخدم جديد',
            'employee_number'   => 'EMP001',
            'email'             => $metadata['billing_email'],
            'email_verified_at' => now(),
            'password'          => Hash::make($metadata['billing_password']),
            'phone'             => $metadata['billing_phone'],
            'status'            => 'active',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $user->assignRole('Admin');

        return $user;
    }

    private function createSettings(array $metadata): void
    {
        Settings::create([
            'image'       => 'default-office.png',
            'office_name' =>  $metadata['billing_company_name'],
            'logo_text'   => 'ليفـــان LEVAN',
        ]);
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
