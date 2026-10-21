<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Properties;

enum PropertyType: string
{
    case RESIDENTIAL_LAND = 'residential_land';
    case VILLA = 'villa';
    case RESIDENTIAL_BUILDING = 'residential_building';
    case COMMERCIAL_BUILDING = 'commercial_building';
    case COMMERCIAL_LAND = 'commercial_land';
    case MIXED_USE = 'mixed_use';
    case AGRICULTURAL_LAND = 'agricultural_land';
    case INDUSTRIAL_LAND = 'industrial_land';
    case WAREHOUSE = 'warehouse';
    case APARTMENT = 'apartment';
    case PALACE = 'palace';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::RESIDENTIAL_LAND => 'أرض سكنية',
            self::VILLA => 'فيلا',
            self::RESIDENTIAL_BUILDING => 'عمارة سكنية',
            self::COMMERCIAL_BUILDING => 'مبنى تجاري',
            self::COMMERCIAL_LAND => 'أرض تجارية',
            self::MIXED_USE => 'متعدد الاستخدام',
            self::AGRICULTURAL_LAND => 'أرض زراعية',
            self::INDUSTRIAL_LAND => 'أرض صناعية',
            self::WAREHOUSE => 'مستودع / هنجر',
            self::APARTMENT => 'شقة سكنية',
            self::PALACE => 'قصر',
            self::OTHER => 'أخرى',
        };
    }
}
