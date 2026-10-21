<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Settings;

enum SmsProvider: string
{
    case FOUR_JAWALY = '4jawaly';
    case TWILIO = 'twilio';
    case NEXMO = 'nexmo';
    case TAQNYAT = 'taqnyat';

    public function label(): string
    {
        return match ($this) {
            self::FOUR_JAWALY => '4Jawaly',
            self::TWILIO => 'Twilio - غير متاحة حالياً',
            self::NEXMO => 'Nexmo - غير متاحة حالياً',
            self::TAQNYAT => 'Taqnyat - غير متاحة حالياً',
        };
    }

    public function isDisabled(): bool
    {
        return $this !== self::FOUR_JAWALY;
    }
}
