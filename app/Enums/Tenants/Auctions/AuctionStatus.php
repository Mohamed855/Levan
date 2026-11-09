<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Auctions;

enum AuctionStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'قيد الانتظار',
            self::APPROVED => 'معتمد',
            self::CLOSED => 'تم الإقفال',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-warning',
            self::APPROVED => 'bg-success',
            self::CLOSED => 'bg-secondary',
        };
    }

    public function labelBadgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'bg-label-warning',
            self::APPROVED => 'bg-label-success',
            self::CLOSED => 'bg-label-secondary',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::PENDING->value => self::PENDING->label(),
            self::APPROVED->value => self::APPROVED->label(),
            self::CLOSED->value => self::CLOSED->label(),
        ];
    }
}
