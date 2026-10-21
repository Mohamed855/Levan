<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Access\Permission;
use App\Models\Access\Role;
use Illuminate\Database\Seeder;

final class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'users.view' => 'عرض المستخدمين', 'users.create' => 'إضافة مستخدمين', 'users.update' => 'تعديل المستخدمين', 'users.delete' => 'أرشفة المستخدمين',
            'roles.view' => 'عرض الأدوار', 'roles.create' => 'إضافة الأدوار', 'roles.update' => 'تعديل الأدوار', 'roles.delete' => 'حذف الأدوار',
            'assignments.view' => 'عرض الإسنادات', 'assignments.create' => 'إضافة الإسنادات', 'assignments.update' => 'تعديل الإسنادات',
            'properties.view' => 'عرض العقارات', 'properties.create' => 'إضافة العقارات', 'properties.update' => 'تعديل العقارات',
            'auctions.view' => 'عرض المزادات', 'auctions.create' => 'إضافة المزادات', 'auctions.update' => 'تعديل المزادات',
        ];

        $permissionIds = [];
        foreach ($permissions as $name => $displayName) {
            $permissionIds[$name] = Permission::query()->updateOrCreate(
                ['guard' => 'tenant', 'name' => $name],
                ['display_name' => $displayName],
            )->getKey();
        }

        $owner = Role::query()->updateOrCreate(
            ['guard' => 'tenant', 'name' => 'tenant_owner'],
            ['display_name' => 'مالك المستأجر'],
        );
        $owner->permissions()->sync(array_values($permissionIds));

        $manager = Role::query()->updateOrCreate(
            ['guard' => 'tenant', 'name' => 'tenant_manager'],
            ['display_name' => 'مدير المستأجر'],
        );
        $manager->permissions()->sync(array_values(array_diff_key($permissionIds, array_flip([
            'users.delete',
            'roles.delete',
        ]))));

        Role::query()->updateOrCreate(
            ['guard' => 'tenant', 'name' => 'tenant_user'],
            ['display_name' => 'مستخدم المستأجر'],
        );
    }
}
