<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Dashboard;

enum DashboardActivityRange: string
{
    case ONE_MONTH = '1month';
    case THREE_MONTHS = '3months';
    case SIX_MONTHS = '6months';
    case ONE_YEAR = '1year';

    public function label(): string
    {
        return match ($this) {
            self::ONE_MONTH => 'آخر شهر',
            self::THREE_MONTHS => 'آخر 3 أشهر',
            self::SIX_MONTHS => 'آخر 6 أشهر',
            self::ONE_YEAR => 'آخر سنة',
        };
    }
}
