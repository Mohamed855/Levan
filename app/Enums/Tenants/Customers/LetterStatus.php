<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Customers;

enum LetterStatus: string
{
    case READY_TO_SEND = 'ready_to_send';
    case APPROVED_READY_TO_SEND = 'approved_ready_to_send';
    case SENT = 'sent';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::READY_TO_SEND => 'جاهز للإرسال', self::APPROVED_READY_TO_SEND => 'معتمد - جاهز للإرسال', self::SENT => 'تم الإرسال', self::FAILED => 'فشل الإرسال'
        };
    }
}
