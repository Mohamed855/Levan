<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Auctions;

enum AuctionReportContentStatus: string
{
    case INCOMPLETE = 'incomplete';
    case COMPLETE = 'complete';
    case ENDED = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::INCOMPLETE => 'غير مكتمل', self::COMPLETE => 'مكتمل', self::ENDED => 'منتهي'
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INCOMPLETE => 'warning', self::COMPLETE => 'success', self::ENDED => 'secondary'
        };
    }
}
