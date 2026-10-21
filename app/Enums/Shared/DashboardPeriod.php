<?php

declare(strict_types=1);

namespace App\Enums\Shared;

enum DashboardPeriod: string
{
    case TODAY = 'today';
    case WEEK = 'week';
    case MONTH = 'month';
    case QUARTER = 'quarter';
    case YEAR = 'year';

    public function label(): string
    {
        return match ($this) {
            self::TODAY => 'اليوم',
            self::WEEK => 'هذا الأسبوع',
            self::MONTH => 'هذا الشهر',
            self::QUARTER => 'ربع سنوي',
            self::YEAR => 'سنوي',
        };
    }
}
