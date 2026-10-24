<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Admin\AdminUser;
use App\Models\Access\Role;
use App\Models\Tenant\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DashboardDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $admin = AdminUser::query()->updateOrCreate(
            ['email' => 'admin@levan.test'],
            ['name' => 'مدير المنصة', 'phone' => '0500000001', 'status' => 'active', 'password' => Hash::make('password')],
        );
        $admin->assignRole(Role::query()->where('guard', 'admin')->where('name', 'manager')->firstOrFail());

        $tenantUser = User::query()->updateOrCreate(
            ['email' => 'tenant@levan.test'],
            ['name' => 'مدير المستأجر', 'phone' => '0500000002', 'status' => 'active', 'email_verified_at' => now(), 'password' => Hash::make('password')],
        );
        $tenantUser->assignRole(Role::query()->where('guard', 'tenant')->where('name', 'manager')->firstOrFail());
    }
}
