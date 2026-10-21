<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Communication;

enum MessageStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SENT = 'sent';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'في الانتظار',
            self::PROCESSING => 'جاري المعالجة',
            self::SENT => 'تم الإرسال',
            self::FAILED => 'فشل الإرسال',
            self::CANCELLED => 'ملغاة',
        };
    }
}
