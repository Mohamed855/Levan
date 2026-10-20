<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Properties;

enum PropertyBoundaryType: string
{
    case STREET = 'street';
    case PARCEL = 'parcel';

    public function label(): string
    {
        return match ($this) {
            self::STREET => 'شارع',
            self::PARCEL => 'قطعة',
        };
    }
}
