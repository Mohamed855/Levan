<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Communication;

enum MessageType: string
{
    case SMS = 'sms';
    case EMAIL = 'email';
    case WHATSAPP = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::SMS => 'رسالة نصية',
            self::EMAIL => 'بريد إلكتروني',
            self::WHATSAPP => 'واتساب',
        };
    }
}
