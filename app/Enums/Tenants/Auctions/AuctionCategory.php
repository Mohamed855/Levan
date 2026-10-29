<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Auctions;

enum AuctionCategory: string
{
    case LANDS = 'lands';
    case VILLAS = 'villas';
    case COMMERCIAL_BUILDINGS = 'commercial_buildings';
    case EDUCATIONAL = 'educational';
    case INDUSTRIAL = 'industrial';
    case MIXED_USE = 'mixed_use';

    public function label(): string
    {
        return match ($this) {
            self::LANDS => 'أراضي',
            self::VILLAS => 'فلل سكنية',
            self::COMMERCIAL_BUILDINGS => 'عمائر تجارية',
            self::EDUCATIONAL => 'مباني تعليمية',
            self::INDUSTRIAL => 'أراضي صناعية',
            self::MIXED_USE => 'أصول متنوعة',
        };
    }
}
