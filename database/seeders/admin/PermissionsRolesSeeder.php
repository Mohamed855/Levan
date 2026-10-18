<?php

namespace Database\Seeders\admin;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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
                'users.restore',
                'users.force_delete',
            ],

            'plans_management' => [
                'plans.view',
                'plans.create',
                'plans.edit',
                'plans.delete',
                'plans.reorder',
                'plans.restore',
                'plans.force_delete',
            ],

            'coupons_management' => [
                'coupons.view',
                'coupons.create',
                'coupons.edit',
                'coupons.delete',
                'coupons.restore',
                'coupons.force_delete',
            ],

            'companies_management' => [
                'companies.view',
                'companies.create',
                'companies.show',
                'companies.edit',
                'companies.delete',
                'companies.restore',
                'companies.force_delete',
            ],

            'subscriptions_management' => [
                'subscriptions.view',
                'subscriptions.create',
                'subscriptions.delete',
                'subscriptions.toggle_status',
                'subscriptions.pending.view',
                'subscriptions.pending.approve',
                'subscriptions.pending.reject',
            ],

            'financial_transactions_management' => [
                'financial_transactions.view',
                'financial_transactions.restore',
                'financial_transactions.force_delete',
            ],

            'tickets_management' => [
                'tickets.view',
                'tickets.show',
                'tickets.reply',
                'tickets.delete',
            ],

            'public_requests_management' => [
                'contact_requests.view',
                'contact_requests.reply',
                'trial_requests.view',
                'trial_requests.approve',
                'trial_requests.reject',
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

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($permissions as $section => $perms) {
            foreach ($perms as $perm) {
                Permission::updateOrCreate(
                    [
                        'name' => $perm,
                        'guard_name' => 'admin',
                    ],
                    [
                        'section' => $section,
                    ]
                );
            }
        }

        $roleSuperAdmin = Role::firstOrCreate(
            [
                'name' => 'SuperAdmin',
                'guard_name' => 'admin',
            ]
        );

        $roleSuperAdmin->syncPermissions(Permission::where('guard_name', 'admin')->get());
    }
}
