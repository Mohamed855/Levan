<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Customers;

enum PhoneType: string
{
    case MOBILE = 'mobile';
    case HOME = 'home';
    case WORK = 'work';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MOBILE => 'جوال',
            self::HOME => 'منزل',
            self::WORK => 'عمل',
            self::OTHER => 'أخرى',
        };
    }
}
