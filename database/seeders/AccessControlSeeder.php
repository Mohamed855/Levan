<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use Illuminate\Database\Seeder;

final class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $permissionsByGuard = [
            'admin' => [
                'dashboard.view',
                'plans.view', 'plans.create', 'plans.update',
                'auction_templates.view', 'auction_templates.create', 'auction_templates.edit', 'auction_templates.publish',
                'tenants.view', 'tenants.create',
                'subscriptions.view', 'subscriptions.update',
                'coupons.view', 'coupons.create', 'coupons.update',
                'admin_users.view', 'admin_users.create', 'admin_users.update',
                'roles.view', 'roles.create', 'roles.update', 'roles.delete',
                'public_requests.view', 'public_requests.reply', 'public_requests.process',
                'bills.view', 'bills.create', 'bills.update',
                'tickets.view', 'tickets.reply', 'tickets.update',
                'settings.view', 'settings.update',
                'banks.view', 'banks.create', 'banks.update',
                'control_domains.view', 'control_domains.create', 'control_domains.update',
                'faq.view', 'faq.create', 'faq.update',
                'activity_log.view',
            ],
            'tenant' => [
                'users.view', 'users.create', 'users.update', 'users.delete',
                'roles.view', 'roles.create', 'roles.update', 'roles.delete',
                'companies.view', 'companies.create', 'companies.edit', 'companies.delete',
                'assignments.view', 'assignments.create', 'assignments.edit',
                'properties.view', 'properties.create', 'properties.edit',
                'auctions.view', 'auctions.create', 'auctions.edit',
                'bills.view',
                'tickets.view', 'tickets.create', 'tickets.reply',
                'activity_log.view',
                'settings.view', 'settings.update',
                'notifications.view',
                'archives.view', 'archives.restore', 'archives.force_delete',
            ],
        ];

        foreach ($permissionsByGuard as $guard => $names) {
            $permissionIds = [];
            foreach ($names as $name) {
                $permission = Permission::query()->firstOrCreate(
                    ['guard' => $guard, 'name' => $name],
                    ['display_name' => $name]
                );
                $permissionIds[] = $permission->id;
            }

            if ($guard === 'admin') {
                $superAdminRole = Role::query()->firstOrCreate(
                    ['guard' => 'admin', 'name' => 'SuperAdmin'],
                    ['display_name' => 'المدير العام']
                );
                $superAdminRole->permissions()->syncWithoutDetaching($permissionIds);

                $managerRole = Role::query()->firstOrCreate(
                    ['guard' => 'admin', 'name' => 'manager'],
                    ['display_name' => 'مدير المنصة']
                );
                $managerRole->permissions()->syncWithoutDetaching($permissionIds);
            } else {
                $tenantManagerRole = Role::query()->firstOrCreate(
                    ['guard' => 'tenant', 'name' => 'manager'],
                    ['display_name' => 'مدير المستأجر']
                );
                $tenantManagerRole->permissions()->syncWithoutDetaching($permissionIds);
            }
        }
    }
}
