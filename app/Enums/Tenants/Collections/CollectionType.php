<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Collections;

enum CollectionType: string
{
    case CASH = 'cash';
    case SCHEDULED = 'scheduled';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'نقدي',
            self::SCHEDULED => 'مجدول',
        };
    }
}
