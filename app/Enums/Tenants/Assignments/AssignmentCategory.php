<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Assignments;

use App\Enums\Tenants\Properties\PropertyType;
use App\Enums\Tenants\Properties\PropertyUsageClassification;

enum AssignmentCategory: string
{
    case RESIDENTIAL = 'residential';
    case COMMERCIAL = 'commercial';
    case AGRICULTURAL = 'agricultural';
    case INDUSTRIAL = 'industrial';
    case MIXED_USE = 'mixed_use';

    public function label(): string
    {
        return match ($this) {
            self::RESIDENTIAL => 'سكنية',
            self::COMMERCIAL => 'تجارية',
            self::AGRICULTURAL => 'زراعية',
            self::INDUSTRIAL => 'صناعية',
            self::MIXED_USE => 'متعدد الأستخدامات',
        };
    }

    /**
     * @return array<int, string>
     */
    public function allowedUsageClassifications(): array
    {
        return [
            $this->value,
            PropertyUsageClassification::OTHER->value,
        ];
    }

    public function allowsUsageClassification(PropertyUsageClassification|string $classification): bool
    {
        $val = $classification instanceof PropertyUsageClassification ? $classification->value : (string) $classification;

        return in_array($val, $this->allowedUsageClassifications(), true);
    }

    /**
     * Backward-compatible helper for property types.
     *
     * @return array<int, string>
     */
    public function allowedPropertyTypes(): array
    {
        return array_map(fn (PropertyType $type) => $type->value, PropertyType::cases());
    }

    public function allowsPropertyType(PropertyType|string $propertyType): bool
    {
        return true;
    }
}
