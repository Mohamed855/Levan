<?php
declare(strict_types=1);
namespace App\Enums\Tenants\Auctions;
enum AuctionCategory: string
{
    case RESIDENTIAL='residential'; case COMMERCIAL='commercial'; case INVESTMENT='investment'; case EDUCATIONAL='educational'; case LANDS='lands'; case MIXED_USE='mixed_use';
    public function label(): string { return match($this) { self::RESIDENTIAL=>'سكني', self::COMMERCIAL=>'تجاري', self::INVESTMENT=>'استثماري', self::EDUCATIONAL=>'تعليمي', self::LANDS=>'أراضي', self::MIXED_USE=>'متعدد الاستخدام' }; }
}
