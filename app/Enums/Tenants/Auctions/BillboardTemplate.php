<?php
declare(strict_types=1);
namespace App\Enums\Tenants\Auctions;
enum BillboardTemplate: string
{
    case BANNER='billboard_banner'; case VERTICAL='billboard_vertical';
    public function label(): string { return match($this) { self::BANNER=>'بانر الشوارع الرئيسي', self::VERTICAL=>'لوحة موقع العقار العمودية' }; }
}
