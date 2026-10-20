<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Billing;

enum BillOperationType: string
{
    case SUBSCRIPTION = 'subscription';
    case RENEWAL = 'renewal';
    case EXTRA_USERS = 'extra_users';
    case DOMAIN = 'domain';

    public function label(): string
    {
        return match ($this) {
            self::SUBSCRIPTION => 'اشتراك',
            self::RENEWAL => 'تجديد',
            self::EXTRA_USERS => 'مستخدمين إضافيين',
            self::DOMAIN => 'دومين',
        };
    }
}
