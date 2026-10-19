<?php

declare(strict_types=1);

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
            'business_center' => [
                'companies.view',
                'companies.create',
                'companies.edit',
                'companies.delete',
            ],
            'system_management' => [
                'system.settings',
                'roles.view',
                'roles.create',
                'roles.edit',
                'roles.delete',
                'roles.assign',
                'activity_log.view',
            ],
            'archive_management' => [
                'user_archive.view',
                'user_archive.restore',
                'user_archive.force_delete',
                'companies_archive.view',
                'companies_archive.restore',
                'companies_archive.force_delete',
                'assignments_archive.view',
                'assignments_archive.restore',
                'assignments_archive.force_delete',
                'properties_archive.view',
                'properties_archive.restore',
                'properties_archive.force_delete',
                'auctions_archive.view',
                'auctions_archive.restore',
                'auctions_archive.force_delete',
            ],
            'assignments_management' => [
                'assignments.view',
                'assignments.create',
                'assignments.edit',
                'assignments.delete',
                'assignments.toggle_status',
            ],
            'properties_management' => [
                'properties.view',
                'properties.create',
                'properties.edit',
                'properties.delete',
                'properties.toggle_status',
            ],
            'auctions_management' => [
                'auctions.view',
                'auctions.create',
                'auctions.edit',
                'auctions.delete',
                'auctions.toggle_status',
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
