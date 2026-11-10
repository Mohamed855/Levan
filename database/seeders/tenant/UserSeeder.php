<?php

namespace Database\Seeders\tenant;

use App\Models\Tenants\UserMangment\User\User;
use Database\Seeders\Support\DemoAssets;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin User
        $admin = User::updateOrCreate(
            ['email' => 'demo@lot.com'],
            [
                'name'              => 'مدير النظام',
                'email_verified_at' => now(),
                'password'          => Hash::make('123456789'),
                'phone'             => '0500000001',
                'status'            => 'active',
                'image'             => config('seeding.test_seeders_enabled', false) ? DemoAssets::image('users/demo-admin.jpg') : '',
                ...(config('seeding.test_seeders_enabled', false) ? ['background_image' => DemoAssets::image('users/demo-admin-background.jpg')] : []),
                'global_uid'        => '00000000-0000-0000-0000-000000000001',
            ]
        );
        $admin->syncRoles(['Admin']);
        $admin->syncPermissions(Permission::where('guard_name', 'tenant')->get());

        // 2. Agents / Relationship Managers
        $agents = [
            [
                'name'       => 'أحمد السعيد',
                'email'      => 'ahmed@lot.com',
                'phone'      => '0500000002',
                'global_uid' => '00000000-0000-0000-0000-000000000002',
            ],
            [
                'name'       => 'سارة الشمري',
                'email'      => 'sara@lot.com',
                'phone'      => '0500000003',
                'global_uid' => '00000000-0000-0000-0000-000000000003',
            ],
            [
                'name'       => 'خالد القحطاني',
                'email'      => 'khalid@lot.com',
                'phone'      => '0500000004',
                'global_uid' => '00000000-0000-0000-0000-000000000004',
            ],
        ];

        foreach ($agents as $agentData) {
            $user = User::updateOrCreate(
                ['email' => $agentData['email']],
                [
                    'name'              => $agentData['name'],
                    'email_verified_at' => now(),
                    'password'          => Hash::make('123456789'),
                    'phone'             => $agentData['phone'],
                    'status'            => 'active',
                    'image'             => config('seeding.test_seeders_enabled', false) ? DemoAssets::image('users/demo-'.md5($agentData['email']).'.jpg') : '',
                    ...(config('seeding.test_seeders_enabled', false) ? ['background_image' => DemoAssets::image('users/demo-'.md5($agentData['email']).'-background.jpg')] : []),
                    'global_uid'        => $agentData['global_uid'],
                ]
            );
            $user->syncRoles(['User']);
        }
    }
}
