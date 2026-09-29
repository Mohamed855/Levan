<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $adminMap = [
            'لوحة القيادة الرئيسية' => ['name' => 'dashboard.main', 'section' => 'dashboard'],
            'المستخدمين' => ['name' => 'users.view', 'section' => 'users_management'],
            'إضافة مستخدم' => ['name' => 'users.create', 'section' => 'users_management'],
            'تعديل مستخدم' => ['name' => 'users.edit', 'section' => 'users_management'],
            'حذف مستخدم' => ['name' => 'users.delete', 'section' => 'users_management'],
            'تغيير حالة المستخدم' => ['name' => 'users.change_status', 'section' => 'users_management'],
            'الباقات' => ['name' => 'plans.view', 'section' => 'plans_management'],
            'إضافة باقة' => ['name' => 'plans.create', 'section' => 'plans_management'],
            'تعديل باقة' => ['name' => 'plans.edit', 'section' => 'plans_management'],
            'حذف باقة' => ['name' => 'plans.delete', 'section' => 'plans_management'],
            'الكوبونات' => ['name' => 'coupons.view', 'section' => 'coupons_management'],
            'إضافة كوبون' => ['name' => 'coupons.create', 'section' => 'coupons_management'],
            'تعديل كوبون' => ['name' => 'coupons.edit', 'section' => 'coupons_management'],
            'حذف كوبون' => ['name' => 'coupons.delete', 'section' => 'coupons_management'],
            'الإشتراكات' => ['name' => 'subscriptions.view', 'section' => 'subscriptions_management'],
            'إضافة إشتراك' => ['name' => 'subscriptions.create', 'section' => 'subscriptions_management'],
            'تعديل إشتراك' => ['name' => 'subscriptions.edit', 'section' => 'subscriptions_management'],
            'حذف إشتراك' => ['name' => 'subscriptions.delete', 'section' => 'subscriptions_management'],
            'المعاملات المالية' => ['name' => 'financial_transactions.view', 'section' => 'financial_transactions_management'],
            'التذاكر' => ['name' => 'tickets.view', 'section' => 'tickets_management'],
            'عرض تذكرة' => ['name' => 'tickets.show', 'section' => 'tickets_management'],
            'الرد على تذكرة' => ['name' => 'tickets.reply', 'section' => 'tickets_management'],
            'العملاء' => ['name' => 'clients.view', 'section' => 'clients_management'],
            'إضافة عميل' => ['name' => 'clients.create', 'section' => 'clients_management'],
            'تعديل عميل' => ['name' => 'clients.edit', 'section' => 'clients_management'],
            'حذف عميل' => ['name' => 'clients.delete', 'section' => 'clients_management'],
            'الإصدارات' => ['name' => 'versions.view', 'section' => 'versions_management'],
            'إضافة إصدارة' => ['name' => 'versions.create', 'section' => 'versions_management'],
            'تعديل إصدارة' => ['name' => 'versions.edit', 'section' => 'versions_management'],
            'حذف إصدارة' => ['name' => 'versions.delete', 'section' => 'versions_management'],
            'فريق إختبار الإصدارات' => ['name' => 'version_testers.view', 'section' => 'version_testers_management'],
            'إضافة عضو' => ['name' => 'version_testers.create', 'section' => 'version_testers_management'],
            'تعديل عضو' => ['name' => 'version_testers.edit', 'section' => 'version_testers_management'],
            'حذف عضو' => ['name' => 'version_testers.delete', 'section' => 'version_testers_management'],
            'إعدادات النظام' => ['name' => 'system.settings', 'section' => 'system_management'],
            'المجموعات' => ['name' => 'system.groups', 'section' => 'system_management'],
            'الانظمة الامنية' => ['name' => 'roles.view', 'section' => 'system_management'],
            'إضافة دور' => ['name' => 'roles.create', 'section' => 'system_management'],
            'تعديل دور' => ['name' => 'roles.edit', 'section' => 'system_management'],
            'حذف دور' => ['name' => 'roles.delete', 'section' => 'system_management'],
            'إسناد دور لمستخدم' => ['name' => 'roles.assign', 'section' => 'system_management'],
            'سجل النشاطات' => ['name' => 'activity_log.view', 'section' => 'system_management'],
            'إعدادات اسعار الامتدادات' => ['name' => 'domain_extension_prices.settings', 'section' => 'system_management'],
            'إعدادات البنوك' => ['name' => 'bank_settings.view', 'section' => 'system_management'],
        ];

        foreach ($adminMap as $arabicName => $target) {
            DB::table('permissions')
                ->where('name', $arabicName)
                ->where('guard_name', 'admin')
                ->update([
                    'name'    => $target['name'],
                    'section' => $target['section'],
                ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Ensure all permissions are present and synced for SuperAdmin
        $superAdmin = Role::where('name', 'SuperAdmin')->where('guard_name', 'admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::where('guard_name', 'admin')->get());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Inverse mapping can be run if necessary
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
