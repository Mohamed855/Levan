<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Auctions;

enum AuctionClosingStatus: string
{
    case NOT_STARTED = 'not_started';
    case SAVED = 'saved';
    case ENDED = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'لم يبدأ',
            self::SAVED => 'محفوظ',
            self::ENDED => 'منتهي',
        };
    }
}
