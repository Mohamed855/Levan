<?php

declare(strict_types=1);

namespace App\Support\Access;

use App\Models\Access\Permission;

final class AdminPermissionCatalog
{
    private const MODULE_LABELS = [
        'dashboard' => 'لوحة التحكم',
        'plans' => 'الباقات',
        'auction_templates' => 'قوالب المزادات',
        'tenants' => 'العملاء',
        'subscriptions' => 'الاشتراكات',
        'coupons' => 'الكوبونات',
        'admin_users' => 'مستخدمو الإدارة',
        'roles' => 'الأدوار والصلاحيات',
        'public_requests' => 'الطلبات العامة',
        'bills' => 'الفواتير',
        'tickets' => 'الدعم',
        'settings' => 'الإعدادات',
        'banks' => 'الحسابات البنكية',
        'control_domains' => 'نطاقات التحكم',
        'faq' => 'الأسئلة الشائعة',
        'activity_log' => 'سجل النشاط',
        'archives' => 'الأرشيف',
    ];

    private const ACTION_LABELS = [
        'view' => 'عرض', 'create' => 'إنشاء', 'update' => 'تعديل', 'edit' => 'تعديل',
        'delete' => 'حذف', 'publish' => 'نشر', 'reply' => 'رد', 'process' => 'معالجة',
        'restore' => 'استعادة', 'force_delete' => 'حذف نهائي',
    ];

    public static function group(Permission $permission): string
    {
        return self::MODULE_LABELS[explode('.', $permission->name, 2)[0]] ?? 'صلاحيات أخرى';
    }

    public static function label(Permission $permission): string
    {
        [$module, $action] = array_pad(explode('.', $permission->name, 2), 2, null);

        return (self::MODULE_LABELS[$module] ?? $module)
            .' — '.(self::ACTION_LABELS[$action] ?? $action);
    }
}
