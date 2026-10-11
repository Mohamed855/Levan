<?php

declare(strict_types=1);

namespace App\Enums\Shared;

enum ActivityStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'نشط',
            self::INACTIVE => 'غير نشط',
        };
    }
}
