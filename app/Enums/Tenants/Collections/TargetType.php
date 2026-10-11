<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Collections;

enum TargetType: string
{
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'شهري',
            self::YEARLY => 'سنوي',
            self::CUSTOM => 'فترة مخصصة',
        };
    }
}
