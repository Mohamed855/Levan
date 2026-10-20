<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Properties;

enum PropertyUnitUsage: string
{
    case RESIDENTIAL = 'residential';
    case COMMERCIAL = 'commercial';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::RESIDENTIAL => 'سكني',
            self::COMMERCIAL => 'تجاري',
            self::OTHER => 'أخرى',
        };
    }
}
