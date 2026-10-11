<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Customers;

enum ContactedPerson: string
{
    case CUSTOMER = 'customer';
    case RELATIVE = 'relative';

    public function label(): string
    {
        return match ($this) {
            self::CUSTOMER => 'العميل نفسه',
            self::RELATIVE => 'أحد أقارب العميل',
        };
    }
}
