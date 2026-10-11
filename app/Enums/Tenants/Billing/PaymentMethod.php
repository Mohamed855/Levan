<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Billing;

enum PaymentMethod: string
{
    case BANK_TRANSFER = 'bank_transfer';
    case CREDIT_CARD = 'credit_card';
    case CASH = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::BANK_TRANSFER => 'تحويل بنكي',
            self::CREDIT_CARD => 'بطاقة ائتمانية',
            self::CASH => 'نقدي',
        };
    }
}
