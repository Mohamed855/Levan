<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Auctions;

enum AuctionStatus: string
{
    case IN_PREPARATION = 'in_preparation';
    case PENDING_APPROVAL = 'pending_approval';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::IN_PREPARATION => 'قيد الإعداد', self::PENDING_APPROVAL => 'بانتظار الاعتماد', self::ACTIVE => 'نشط', self::COMPLETED => 'منتهي', self::SUSPENDED => 'معلق'
        };
    }
}
