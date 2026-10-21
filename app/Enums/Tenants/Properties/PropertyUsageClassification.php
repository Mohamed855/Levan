<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Properties;

enum PropertyUsageClassification: string
{
    case RESIDENTIAL = 'residential';
    case COMMERCIAL = 'commercial';
    case AGRICULTURAL = 'agricultural';
    case INDUSTRIAL = 'industrial';
    case MIXED_USE = 'mixed_use';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::RESIDENTIAL => 'سكني',
            self::COMMERCIAL => 'تجاري',
            self::AGRICULTURAL => 'زراعي',
            self::INDUSTRIAL => 'صناعي',
            self::MIXED_USE => 'متعدد الاستخدام',
            self::OTHER => 'أخرى',
        };
    }
}
