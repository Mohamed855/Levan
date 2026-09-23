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
            'لوحة تحكم الإدارة' => 'لوحة القيادة',
            'المستخدمين' => 'إدارة المستخدمين',
            'إضافة مستخدم' => 'إدارة المستخدمين',
            'تعديل مستخدم' => 'إدارة المستخدمين',
            'حذف مستخدم' => 'إدارة المستخدمين',
            'تغيير حالة المستخدم' => 'إدارة المستخدمين',
            'الفروع' => 'إدارة النظام',
            'إضافة فرع' => 'إدارة النظام',
            'تعديل فرع' => 'إدارة النظام',
            'حذف فرع' => 'إدارة النظام',
            'المجموعات' => 'إدارة النظام',
            'الانظمة الامنية' => 'إدارة النظام',
            'إضافة دور' => 'إدارة النظام',
            'تعديل دور' => 'إدارة النظام',
            'حذف دور' => 'إدارة النظام',
            'إسناد دور لمستخدم' => 'إدارة النظام',
            'سجل النشاطات' => 'إدارة النظام',
        ];

        foreach ($permissions as $name => $section) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'tenant'],
                ['section' => $section]
            );
        }

        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'tenant']);
        $admin->syncPermissions(Permission::where('guard_name', 'tenant')->get());
    }
}
