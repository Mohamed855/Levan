<?php

namespace Database\Seeders\tenant;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsRolesSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard' => [
                'dashboard.view',
            ],
            'subscriptions_management' => [
                'subscriptions.view',
                'subscriptions.renew',
                'subscriptions.add_licenses',
                'subscriptions.upgrade',
            ],
            'domain_management' => [
                'domain.view',
                'domain.reserve',
                'domain.connect',
                'domain.transfer',
            ],
            'bills_management' => [
                'bills.view',
                'bills.pay',
            ],
            'users_management' => [
                'users.view',
                'users.create',
                'users.edit',
                'users.delete',
                'system.groups',
                'teams.manage',
            ],
            'branches_management' => [
                'branches.view',
                'branches.create',
                'branches.edit',
                'branches.delete',
            ],
            'system_management' => [
                'system.settings',
                'countries.view',
                'payment_methods.view',
                'message_templates.view',
                'roles.view',
                'roles.create',
                'roles.edit',
                'roles.delete',
                'roles.assign',
                'activity_log.view',
                'message_logs.view',
                'message_logs.show',
                'message_logs.delete',
                'message_logs.export',
            ],
            'archive_management' => [
                'user_archive.view',
                'user_archive.restore',
                'user_archive.force_delete',
            ],
            'tickets_management' => [
                'tickets.view',
                'tickets.create',
            ],
        ];

        foreach ($permissions as $section => $perms) {
            foreach ($perms as $perm) {
                Permission::firstOrCreate(
                    ['name' => $perm, 'guard_name' => 'tenant'],
                    ['section' => $section]
                );
            }
        }

        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'tenant']);
        $adminRole->syncPermissions(Permission::where('guard_name', 'tenant')->get());

        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'tenant']);
        $userRole->syncPermissions(
            Permission::where('guard_name', 'tenant')
                ->whereIn('name', ['dashboard.view', 'tickets.view'])
                ->get()
        );
    }
}
