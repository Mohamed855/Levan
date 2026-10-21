<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Billing;

enum BillStatus: string
{
    case PAID = 'paid';
    case UNPAID = 'unpaid';
    case PARTIALLY_PAID = 'partially_paid';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PAID => 'مدفوعة',
            self::UNPAID => 'غير مدفوعة',
            self::PARTIALLY_PAID => 'مدفوعة جزئياً',
            self::CANCELLED => 'ملغاة',
        };
    }
}
