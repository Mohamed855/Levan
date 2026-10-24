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
            'assignments.view' => 'عرض الإسنادات', 'assignments.create' => 'إضافة الإسنادات', 'assignments.edit' => 'تعديل الإسنادات',
            'properties.view' => 'عرض العقارات', 'properties.create' => 'إضافة العقارات', 'properties.edit' => 'تعديل العقارات',
            'auctions.view' => 'عرض المزادات', 'auctions.create' => 'إضافة المزادات', 'auctions.edit' => 'تعديل المزادات',
            'companies.view' => 'عرض الشركات', 'companies.create' => 'إضافة الشركات', 'companies.edit' => 'تعديل الشركات', 'companies.delete' => 'أرشفة الشركات',
            'bills.view' => 'عرض الفواتير',
            'subscriptions.view' => 'عرض الاشتراك', 'subscriptions.renew' => 'تجديد الاشتراك', 'subscriptions.add_licenses' => 'شراء تراخيص إضافية',
            'domains.view' => 'عرض النطاقات', 'domains.manage' => 'إدارة النطاقات',
            'tickets.view' => 'عرض التذاكر', 'tickets.create' => 'إنشاء التذاكر', 'tickets.reply' => 'الرد على التذاكر',
            'activity_log.view' => 'عرض سجل النشاط',
            'settings.view' => 'عرض الإعدادات', 'settings.update' => 'تحديث الإعدادات',
            'notifications.view' => 'عرض الإشعارات',
            'archives.view' => 'عرض الأرشيف', 'archives.restore' => 'استعادة الأرشيف', 'archives.force_delete' => 'الحذف النهائي من الأرشيف',
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
