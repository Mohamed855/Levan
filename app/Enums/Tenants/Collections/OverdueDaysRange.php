<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Collections;

enum OverdueDaysRange: string
{
    case YESTERDAY = '1';
    case THREE_DAYS = '3';
    case SEVEN_DAYS = '7';
    case THIRTY_DAYS = '30';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::YESTERDAY => 'أمس فقط',
            self::THREE_DAYS => 'آخر 3 أيام',
            self::SEVEN_DAYS => 'آخر أسبوع',
            self::THIRTY_DAYS => 'آخر شهر',
            self::CUSTOM => 'نطاق مخصص',
        };
    }
}
