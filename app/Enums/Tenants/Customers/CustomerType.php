<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Customers;

enum CustomerType: string
{
    case INDIVIDUAL = 'individual';
    case COMPANY = 'company';

    public function label(): string
    {
        return match ($this) {
            self::INDIVIDUAL => 'فردي',
            self::COMPANY => 'شركة',
        };
    }
}
