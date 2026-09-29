<?php

namespace Database\Seeders\admin;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsRolesSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            'dashboard' => [
                'dashboard.main',
            ],

            'users_management' => [
                'users.view',
                'users.create',
                'users.edit',
                'users.delete',
                'users.change_status',
            ],

            'plans_management' => [
                'plans.view',
                'plans.create',
                'plans.edit',
                'plans.delete',
            ],

            'coupons_management' => [
                'coupons.view',
                'coupons.create',
                'coupons.edit',
                'coupons.delete',
            ],

            'subscriptions_management' => [
                'subscriptions.view',
                'subscriptions.create',
                'subscriptions.edit',
                'subscriptions.delete',
            ],

            'financial_transactions_management' => [
                'financial_transactions.view',
            ],

            'tickets_management' => [
                'tickets.view',
                'tickets.show',
                'tickets.reply',
                'tickets.delete',
            ],

            'clients_management' => [
                'clients.view',
                'clients.create',
                'clients.edit',
                'clients.delete',
            ],

            'versions_management' => [
                'versions.view',
                'versions.create',
                'versions.edit',
                'versions.delete',
            ],

            'version_testers_management' => [
                'version_testers.view',
                'version_testers.create',
                'version_testers.edit',
                'version_testers.delete',
            ],

            'system_management' => [
                'system.settings',
                'system.groups',
                'system.security_systems',
                'roles.view',
                'roles.create',
                'roles.edit',
                'roles.delete',
                'roles.assign',
                'activity_log.view',
                'domain_extension_prices.settings',
                'bank_settings.view',
            ],
        ];

        foreach ($permissions as $section => $perms) {
            foreach ($perms as $perm) {
                Permission::firstOrCreate([
                    'name'          => $perm,
                    'section'       => $section,
                    'guard_name'    => 'admin',
                ]);
            }
        }

        $roleSuperAdmin = Role::firstOrCreate(
            [
                'name'             => 'SuperAdmin',
                'guard_name'        => 'admin'
            ]
        );

        $roleSuperAdmin->syncPermissions(Permission::where('guard_name', 'admin')->get());
    }
}
