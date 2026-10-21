<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Properties;

enum PropertyOwnershipType: string
{
    case SOLE_OWNERSHIP = 'sole_ownership';
    case SHARED_OWNERSHIP = 'shared_ownership';
    case CORPORATE = 'corporate';
    case WAQF = 'waqf';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SOLE_OWNERSHIP => 'ملك',
            self::SHARED_OWNERSHIP => 'مشاع / ملك مشترك',
            self::CORPORATE => 'شركة / جهة اعتبارية',
            self::WAQF => 'وقف',
            self::OTHER => 'أخرى',
        };
    }
}
