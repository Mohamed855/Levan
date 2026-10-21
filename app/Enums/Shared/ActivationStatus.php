<?php

declare(strict_types=1);

namespace App\Enums\Shared;

enum ActivationStatus: int
{
    case ACTIVE = 1;
    case INACTIVE = 0;

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'نشط',
            self::INACTIVE => 'غير نشط',
        };
    }
}
