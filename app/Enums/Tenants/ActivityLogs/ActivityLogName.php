<?php

declare(strict_types=1);

namespace App\Enums\Tenants\ActivityLogs;

enum ActivityLogName: string
{
    case COMPANIES = 'companies';
    case USERS = 'users';
    case AUTH = 'auth';
    case ROLES = 'roles';
    case PERMISSIONS = 'permissions';
    case PROPERTIES = 'properties';
    case ASSIGNMENTS = 'assignments';
    case AUCTIONS = 'auctions';
    case TICKETS = 'tickets';
    case BILLS = 'bills';
    case SUBSCRIPTIONS = 'subscriptions';
    case SETTINGS = 'settings';
    case ACTIVITY_LOGS = 'activity_logs';
    case DEFAULT = 'default';

    public function label(): string
    {
        return match ($this) {
            self::COMPANIES => 'الشركات',
            self::USERS => 'المستخدمين',
            self::AUTH => 'تسجيل الدخول',
            self::ROLES => 'الأدوار والصلاحيات',
            self::PERMISSIONS => 'الصلاحيات',
            self::PROPERTIES => 'العقارات',
            self::ASSIGNMENTS => 'الإسنادات',
            self::AUCTIONS => 'المزادات',
            self::TICKETS => 'تذاكر الدعم',
            self::BILLS => 'الفواتير',
            self::SUBSCRIPTIONS => 'الاشتراكات',
            self::SETTINGS => 'الإعدادات',
            self::ACTIVITY_LOGS => 'سجل النشاطات',
            self::DEFAULT => 'عام',
        };
    }
}
