<?php

namespace App\Services\shared\Tenant\Seeders;

use App\Contracts\Shared\TenantSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TenantPermissionSeeder implements TenantSeeder
{
    public function seed(): void
    {
        $this->createPermissions();
        $this->createAdminRole();
        $this->createCollectorRole();
    }

    /*
    |============================================================================
    |============================================================================
    |                         Private Methods
    |============================================================================
    |============================================================================
    */
    private array $permissions = [
        'dashboard' => [
            'dashboard.view'
        ],

        'subscriptions_management' => [
            'subscriptions.view',
            'subscriptions.renew',
            'subscriptions.add_licenses',
            'subscriptions.upgrade'
        ],

        'domain_management' => [
            'domain.view',
            'domain.reserve',
            'domain.connect',
            'domain.transfer',
        ],

        'bills_management' => [
            'bills.view',
            'bills.pay'
        ],

        'tasks_management' => [
            'tasks.view',
            'tasks.my_tasks'
        ],

        'operations_center' => [
            'companies.view',
            'companies.create',
            'companies.edit',
            'companies.delete',

            'contracts.view',
            'contracts.create',
            'contracts.edit',
            'contracts.delete',

            'portfolios.view',
            'portfolios.create',
            'portfolios.edit',
            'portfolios.delete',

            'clients.view',
            'clients.my_clients',

            'clients.distribution_view',
            'clients.distribution_create',
            'clients.update_requests',

            'clients.create',
            'clients.edit',
            'clients.edit_all',
            'clients.delete',
            'clients.delete_all',
            'clients.live_update',
            'clients.change_relationship_officer',
            'clients.export_excel',
            'clients.send_message',

            'clients.details',
            'clients.follow_up_view',
            'clients.follow_up_create',
            'clients.follow_up_edit',
            'clients.follow_up_delete',
            'clients.payment_plan',
            'clients.settlement_requests',
            'clients.notes',
            'clients.attachments',
            'clients.add_phone',

            'operations.approve',
            'operations.reject',

            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'teams.manage',
        ],

        'collection_management' => [
            'collection_targets.view',
            'collection_targets.create',
            'collection_targets.delete',
            'collection_targets.edit',

            'collections.my_collections',
            'collections.create',
            'collections.edit',
            'collections.delete',
            'collections.approve',
            'collections.reject',

            'collections.users_collections',
            'collections.mass_approve',
            'collections.mass_reject',
            'collections.mass_delete',

            'collections.payment_reconciliation',
            'collections.start_collection',
        ],

        'system_management' => [
            'system.settings',
            'client_statuses.view',
            'contact_results.view',
            'payment_methods.view',
            'debt_types.view',
            'countries.view',
            'message_templates.view',
            'contact_channels.view',
            'document_request_reasons.view',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'roles.assign',
            'activity_log.view',
            'message_logs.view',
            'system.groups',
        ],

        'reports_and_archives' => [
            'reports.executive',
            'reports.clients',
            'reports.collections',
            'reports.companies',
            'reports.employee_performance',
            'reports.users',

            'archives.companies',
            'archives.portfolios',
            'archives.clients',
            'archives.contracts',
            'user_archive.view',
            'user_archive.restore',
            'user_archive.force_delete',
        ]
    ];

    private function createPermissions(): void
    {
        foreach ($this->permissions as $section => $perms) {
            foreach ($perms as $perm) {
                Permission::firstOrCreate(
                    ['name' => $perm, 'guard_name' => 'tenant'],
                    ['section' => $section]
                );
            }
        }
    }

    private function createAdminRole(): void
    {
        $role = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'tenant']);

        $allPermissions = Permission::where('guard_name', 'tenant')->get();
        $role->syncPermissions($allPermissions);
    }

    private function createCollectorRole(): void
    {
        Role::firstOrCreate(['name' => 'Collector', 'guard_name' => 'tenant']);
    }
}
