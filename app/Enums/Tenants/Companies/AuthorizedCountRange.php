<?php
declare(strict_types=1);
namespace App\Enums\Tenants\Companies;
enum AuthorizedCountRange: string
{
    case NONE='0'; case ONE='1'; case TWO_TO_FIVE='2-5'; case MORE_THAN_FIVE='6+';
    public function label(): string { return match($this) { self::NONE=>'بدون مفوضين', self::ONE=>'مفوض واحد', self::TWO_TO_FIVE=>'من 2 إلى 5 مفوضين', self::MORE_THAN_FIVE=>'أكثر من 5 مفوضين' }; }
}
