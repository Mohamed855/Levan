<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Customers;

enum SmsDeliveryStatus: string
{
    case SENT = 'sent';
    case NOT_SENT = 'not_sent';

    public function label(): string
    {
        return match ($this) {
            self::SENT => 'تم الإرسال', self::NOT_SENT => 'لم يتم الإرسال'
        };
    }
}
