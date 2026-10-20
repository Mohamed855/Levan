<?php

declare(strict_types=1);

namespace App\Enums\Tenants\ActivityLogs;

enum ActivityLogName: string
{
    case COMPANIES = 'companies';
    case USERS = 'users';
    case AUTH = 'auth';

    public function label(): string
    {
        return match ($this) {
            self::COMPANIES => 'الشركات',
            self::USERS => 'المستخدمين',
            self::AUTH => 'تسجيل الدخول',
        };
    }
}
