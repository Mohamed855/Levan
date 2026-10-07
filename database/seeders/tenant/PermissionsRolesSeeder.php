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
            'business_center' => [
                'companies.view', 'companies.create', 'companies.edit', 'companies.delete',
                'portfolios.view', 'portfolios.create', 'portfolios.edit', 'portfolios.delete',
                'customers.view', 'customers.my_view', 'customers.create', 'customers.edit',
                'customers.edit_all', 'customers.delete', 'customers.delete_all',
                'customers.send_message', 'customers.update_directly', 'customers.add_phone',
                'contracts.view', 'contracts.create', 'contracts.edit', 'contracts.delete',
            ],
            'system_management' => [
                'system.settings',
                'countries.view',
                'payment_methods.view',
                'message_templates.view',
                'document_request_reasons.view',
                'contact_methods.view',
                'customer_statuses.view',
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
                'companies_archive.view', 'companies_archive.restore', 'companies_archive.force_delete',
                'portfolios_archive.view', 'portfolios_archive.restore', 'portfolios_archive.force_delete',
                'customers_archive.view', 'customers_archive.restore', 'customers_archive.force_delete',
                'contracts_archive.view', 'contracts_archive.restore', 'contracts_archive.force_delete',
                'assignments_archive.view', 'assignments_archive.restore', 'assignments_archive.force_delete',
                'properties_archive.view', 'properties_archive.restore', 'properties_archive.force_delete',
                'auctions_archive.view', 'auctions_archive.restore', 'auctions_archive.force_delete',
            ],
            'assignments_management' => [
                'assignments.view', 'assignments.create', 'assignments.edit', 'assignments.delete', 'assignments.toggle_status',
            ],
            'properties_management' => [
                'properties.view', 'properties.create', 'properties.edit', 'properties.delete', 'properties.toggle_status',
            ],
            'auctions_management' => [
                'auctions.view', 'auctions.create', 'auctions.edit', 'auctions.delete', 'auctions.toggle_status',
            ],
            'tickets_management' => [
                'tickets.view',
                'tickets.create',
                'tasks.view', 'my_tasks.view',
                'collections.view', 'collections.create', 'collections.edit', 'collections.delete',
                'admin_collections.view', 'collection_process.start', 'collection_targets.view', 'collection_targets.create', 'collection_targets.edit', 'collection_targets.delete',
                'customer_distributions.view', 'customer_distributions.create', 'customer_distributions.delete',
                'tasks.delete', 'customer_follows.edit', 'customer_follows.delete',
                'customers.export', 'archive.restore', 'archive.force_delete', 'collections_archive.view',
                'collections.approve', 'collections.reject', 'admin_collections.approve_all',
                'admin_collections.reject_all', 'admin_collections.delete_selected', 'admin_collections.import',
                'customer_update_requests.view', 'customer_update_requests.approve', 'customer_update_requests.reject',
                'reconciliation.view', 'company_letters.view', 'company_letters.send',
                'reports.customers.view', 'reports.companies.view', 'reports.users.view',
                'reports.executive.view', 'reports.performance.view',
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

        Role::firstOrCreate(['name' => 'collector', 'guard_name' => 'tenant']);

        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'tenant']);
        $userRole->syncPermissions(
            Permission::where('guard_name', 'tenant')
                ->whereIn('name', ['dashboard.view', 'tickets.view'])
                ->get()
        );
    }
}
