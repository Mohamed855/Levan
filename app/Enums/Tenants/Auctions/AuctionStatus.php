<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Auctions;

enum AuctionStatus: string
{
    case UPCOMING = 'upcoming';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::UPCOMING => 'قادم',
            self::ACTIVE => 'نشط',
            self::SUSPENDED => 'معلق',
            self::CLOSED => 'تم الإغلاق',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::UPCOMING => 'bg-info',
            self::ACTIVE => 'bg-success',
            self::SUSPENDED => 'bg-warning',
            self::CLOSED => 'bg-secondary',
        };
    }

    public function labelBadgeClass(): string
    {
        return match ($this) {
            self::UPCOMING => 'bg-label-info',
            self::ACTIVE => 'bg-label-success',
            self::SUSPENDED => 'bg-label-warning',
            self::CLOSED => 'bg-label-secondary',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::ACTIVE->value => self::ACTIVE->label(),
            self::UPCOMING->value => self::UPCOMING->label(),
            self::SUSPENDED->value => self::SUSPENDED->label(),
            self::CLOSED->value => self::CLOSED->label(),
        ];
    }
}
