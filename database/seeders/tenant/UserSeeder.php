<?php

namespace Database\Seeders\tenant;

use App\Models\Tenants\Branch\Branch;
use App\Models\Tenants\UserMangment\Team\Team;
use App\Models\Tenants\UserMangment\User\User;
use App\Models\Tenants\UserMangment\User\UserBranchAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $mainBranch = Branch::where('code', 'MAIN')->first();
        $team = Team::first();

        // 1. Admin User
        $admin = User::updateOrCreate(
            ['email' => 'demo@levan.com'],
            [
                'name'              => 'مدير النظام',
                'employee_number'   => 'EMP001',
                'email_verified_at' => now(),
                'password'          => Hash::make('123456789'),
                'phone'             => '0500000001',
                'status'            => 'active',
                'image'             => '',
                'branch_id'         => $mainBranch?->id,
                'team_id'           => $team?->id,
                'is_team_leader'    => true,
                'global_uid'        => '00000000-0000-0000-0000-000000000001',
                'license_seat_id'   => '11111111-1111-1111-1111-000000000001',
            ]
        );
        $admin->syncRoles(['Admin']);

        // 2. Agents / Relationship Managers
        $agents = [
            [
                'name'            => 'أحمد السعيد',
                'email'           => 'ahmed@levan.com',
                'employee_number' => 'EMP002',
                'phone'           => '0500000002',
                'global_uid'      => '00000000-0000-0000-0000-000000000002',
                'license_seat_id' => '11111111-1111-1111-1111-000000000002',
            ],
            [
                'name'            => 'سارة الشمري',
                'email'           => 'sara@levan.com',
                'employee_number' => 'EMP003',
                'phone'           => '0500000003',
                'global_uid'      => '00000000-0000-0000-0000-000000000003',
                'license_seat_id' => '11111111-1111-1111-1111-000000000003',
            ],
            [
                'name'            => 'خالد القحطاني',
                'email'           => 'khalid@levan.com',
                'employee_number' => 'EMP004',
                'phone'           => '0500000004',
                'global_uid'      => '00000000-0000-0000-0000-000000000004',
                'license_seat_id' => '11111111-1111-1111-1111-000000000004',
            ],
        ];

        foreach ($agents as $agentData) {
            $user = User::updateOrCreate(
                ['email' => $agentData['email']],
                [
                    'name'              => $agentData['name'],
                    'employee_number'   => $agentData['employee_number'],
                    'email_verified_at' => now(),
                    'password'          => Hash::make('123456789'),
                    'phone'             => $agentData['phone'],
                    'status'            => 'active',
                    'image'             => '',
                    'branch_id'         => $mainBranch?->id,
                    'team_id'           => $team?->id,
                    'is_team_leader'    => false,
                    'global_uid'        => $agentData['global_uid'],
                    'license_seat_id'   => $agentData['license_seat_id'],
                ]
            );
            $user->syncRoles(['User']);

            if ($mainBranch) {
                UserBranchAccess::updateOrCreate([
                    'user_id' => $user->id,
                    'branch_id' => $mainBranch->id,
                ]);
            }
        }
    }
}
