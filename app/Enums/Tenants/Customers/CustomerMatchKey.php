<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Customers;

enum CustomerMatchKey: string
{
    case ACCOUNT_NUMBER = 'account_number';
    case IDENTITY_NUMBER = 'identity_number';

    public function label(): string
    {
        return match ($this) {
            self::ACCOUNT_NUMBER => 'رقم الحساب',
            self::IDENTITY_NUMBER => 'رقم الهوية',
        };
    }
}
