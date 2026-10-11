<?php
declare(strict_types=1);
namespace App\Enums\Tenants\Auctions;
enum AuctionType: string
{
    case ELECTRONIC='electronic'; case IN_PERSON='in_person'; case HYBRID='hybrid';
    public function label(): string { return match($this) { self::ELECTRONIC=>'إلكتروني', self::IN_PERSON=>'حضوري', self::HYBRID=>'هجين' }; }
}
