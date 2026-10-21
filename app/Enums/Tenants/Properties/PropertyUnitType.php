<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Properties;

enum PropertyUnitType: string
{
    case VILLA = 'villa';
    case APARTMENT = 'apartment';
    case SHOP = 'shop';
    case GARDEN = 'garden';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::VILLA => 'فيلا',
            self::APARTMENT => 'شقة',
            self::SHOP => 'محل',
            self::GARDEN => 'حديقة',
            self::OTHER => 'أخرى',
        };
    }
}
