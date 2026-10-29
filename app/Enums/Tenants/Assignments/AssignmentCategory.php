<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Assignments;

use App\Enums\Tenants\Properties\PropertyType;

enum AssignmentCategory: string
{
    case LANDS = 'lands';
    case VILLAS = 'villas';
    case COMMERCIAL_BUILDINGS = 'commercial_buildings';
    case EDUCATIONAL = 'educational';
    case INDUSTRIAL = 'industrial';
    case MIXED_USE = 'mixed_use';

    public function label(): string
    {
        return match ($this) {
            self::LANDS => 'أراضي',
            self::VILLAS => 'فلل سكنية',
            self::COMMERCIAL_BUILDINGS => 'عمائر تجارية',
            self::EDUCATIONAL => 'مباني تعليمية',
            self::INDUSTRIAL => 'أراضي صناعية',
            self::MIXED_USE => 'أصول متنوعة',
        };
    }

    /**
     * @return array<int, string>
     */
    public function allowedPropertyTypes(): array
    {
        return match ($this) {
            self::LANDS => [
                PropertyType::RESIDENTIAL_LAND->value,
                PropertyType::COMMERCIAL_LAND->value,
                PropertyType::AGRICULTURAL_LAND->value,
                PropertyType::INDUSTRIAL_LAND->value,
            ],
            self::VILLAS => [
                PropertyType::VILLA->value,
                PropertyType::PALACE->value,
            ],
            self::COMMERCIAL_BUILDINGS => [
                PropertyType::COMMERCIAL_BUILDING->value,
                PropertyType::RESIDENTIAL_BUILDING->value,
                PropertyType::APARTMENT->value,
                PropertyType::MIXED_USE->value,
            ],
            self::EDUCATIONAL => [
                PropertyType::COMMERCIAL_BUILDING->value,
                PropertyType::MIXED_USE->value,
                PropertyType::OTHER->value,
            ],
            self::INDUSTRIAL => [
                PropertyType::INDUSTRIAL_LAND->value,
                PropertyType::WAREHOUSE->value,
            ],
            self::MIXED_USE => array_map(fn (PropertyType $type) => $type->value, PropertyType::cases()),
        };
    }

    public function allowsPropertyType(PropertyType|string $propertyType): bool
    {
        $val = $propertyType instanceof PropertyType ? $propertyType->value : (string) $propertyType;

        return in_array($val, $this->allowedPropertyTypes(), true);
    }
}

