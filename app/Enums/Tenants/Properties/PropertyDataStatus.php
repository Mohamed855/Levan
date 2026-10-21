<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Properties;

enum PropertyDataStatus: string
{
    case COMPLETED = 'completed';
    case NEEDS_REVIEW = 'needs_review';
    case NEEDS_IMAGES = 'needs_images';
    case INCOMPLETE = 'incomplete';

    public function label(): string
    {
        return match ($this) {
            self::COMPLETED => 'مكتمل البيانات',
            self::NEEDS_REVIEW => 'تحتاج للمراجعة',
            self::NEEDS_IMAGES => 'يحتاج صور',
            self::INCOMPLETE => 'غير مكتمل',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::COMPLETED => 'bg-label-success',
            self::NEEDS_REVIEW => 'bg-label-warning',
            self::NEEDS_IMAGES => 'bg-label-info',
            self::INCOMPLETE => 'bg-label-danger',
        };
    }
}
