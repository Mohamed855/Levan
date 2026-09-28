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
            'لوحة القيادة' => [
                'لوحة القيادة',
            ],
            'إدارة الإشتراك' => [
                'الإشتراك',
                'تجديد الإشتراك',
                'شراء تراخيص إضافية',
                'الترقية للنسخة الجديدة'
            ],
            'إعدادات الدومين' => [
                'إعدادات الدومين',
                'حجز دومين',
                'ربط دومين',
                'نقل دومين',
            ],
            'إدارة الفواتير' => [
                'الفواتير',
                'دفع فاتورة'
            ],
            'إدارة المستخدمين' => [
                'المستخدمين',
                'إضافة مستخدم',
                'تعديل مستخدم',
                'حذف مستخدم',
                'المجموعات',
                'إدارة الفريق',
            ],
            'إدارة الفروع' => [
                'الفروع',
                'إضافة فرع',
                'تعديل فرع',
                'حذف فرع',
            ],
            'إدارة النظام' => [
                'إعدادات النظام',
                'قائمة الدول',
                'طرق الدفع',
                'نماذج الرسائل',
                'الانظمة الامنية',
                'إضافة دور',
                'تعديل دور',
                'حذف دور',
                'إسناد دور لمستخدم',
                'سجل النشاطات',
                'سجل الرسائل',
                'عرض سجلات الرسائل',
                'حذف سجلات الرسائل',
                'تصدير سجلات الرسائل',
            ],
            'الأرشيف' => [
                'أرشيف المستخدمين',
                'إسترجاع السجلات',
                'حذف نهائي للسجلات',
            ],
            'الدعم الفني' => [
                'الدعم الفني',
                'إنشاء تذكرة',
            ]
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
                ->whereIn('name', ['لوحة القيادة', 'الدعم الفني'])
                ->get()
        );
    }
}
