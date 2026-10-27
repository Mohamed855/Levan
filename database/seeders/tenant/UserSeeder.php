<?php

namespace Database\Seeders\tenant;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin User
        $admin = User::updateOrCreate(
            ['email' => 'demo@levan.com'],
            [
                'name'              => 'مدير النظام',
                'email_verified_at' => now(),
                'password'          => Hash::make('123456789'),
                'phone'             => '0500000001',
                'status'            => 'active',
                'image'             => '',
                'global_uid'        => '00000000-0000-0000-0000-000000000001',
            ]
        );
        $admin->syncRoles(['Admin']);

        // 2. Agents / Relationship Managers
        $agents = [
            [
                'name'       => 'أحمد السعيد',
                'email'      => 'ahmed@levan.com',
                'phone'      => '0500000002',
                'global_uid' => '00000000-0000-0000-0000-000000000002',
            ],
            [
                'name'       => 'سارة الشمري',
                'email'      => 'sara@levan.com',
                'phone'      => '0500000003',
                'global_uid' => '00000000-0000-0000-0000-000000000003',
            ],
            [
                'name'       => 'خالد القحطاني',
                'email'      => 'khalid@levan.com',
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
                    'image'             => '',
                    'global_uid'        => $agentData['global_uid'],
                ]
            );
            $user->syncRoles(['User']);
        }
    }
}
