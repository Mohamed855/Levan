<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Access\Role;
use App\Models\Admin\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::query()->where('guard', 'admin')->where('name', 'SuperAdmin')->first()
            ?? Role::query()->firstOrCreate(['guard' => 'admin', 'name' => 'SuperAdmin'], ['display_name' => 'المدير العام']);

        $managerRole = Role::query()->where('guard', 'admin')->where('name', 'manager')->first()
            ?? Role::query()->firstOrCreate(['guard' => 'admin', 'name' => 'manager'], ['display_name' => 'مدير المنصة']);

        $defaultAdmins = [
            [
                'email'    => 'admin@levan.test',
                'name'     => 'مدير النظام الرئيسي',
                'phone'    => '0500000001',
                'password' => 'password',
            ],
            [
                'email'    => 'admin@lot.sa',
                'name'     => 'مدير لوت العام',
                'phone'    => '0500000000',
                'password' => 'password',
            ],
            [
                'email'    => 'admin@admin.com',
                'name'     => 'مدير النظام',
                'phone'    => '0500000003',
                'password' => 'password',
            ],
        ];

        foreach ($defaultAdmins as $data) {
            $admin = AdminUser::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'phone'             => $data['phone'],
                    'status'            => 'active',
                    'password'          => Hash::make($data['password']),
                    'email_verified_at' => now(),
                ]
            );

            $admin->assignRole($superAdminRole);
            $admin->assignRole($managerRole);
        }
    }
}
