<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Properties;

enum PropertyUnitStatus: string
{
    case AVAILABLE = 'available';
    case RENTED = 'rented';
    case SOLD = 'sold';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'متاح',
            self::RENTED => 'مؤجر',
            self::SOLD => 'مباع',
        };
    }
}
