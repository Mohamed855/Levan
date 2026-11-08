<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Properties;

enum PropertyType: string
{
    case APARTMENT = 'apartment';
    case VILLA = 'villa';
    case PALACE = 'palace';
    case BUILDING = 'building';
    case WAREHOUSE = 'warehouse';
    case LAND = 'land';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::APARTMENT => 'شقة',
            self::VILLA => 'فيلا',
            self::PALACE => 'قصر',
            self::BUILDING => 'مبنى',
            self::WAREHOUSE => 'مستودع/هنجر',
            self::LAND => 'أرض',
            self::OTHER => 'أخرى',
        };
    }
}
