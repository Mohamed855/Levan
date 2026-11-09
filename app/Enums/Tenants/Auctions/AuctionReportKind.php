<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Auctions;

enum AuctionReportKind: string
{
    case OUTPUTS = 'outputs';
    case REPORT = 'report';

    public function label(): string
    {
        return match ($this) {
            self::OUTPUTS => 'مخرجات المزاد',
            self::REPORT => 'التقرير النهائي',
        };
    }
}
