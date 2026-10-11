<?php
declare(strict_types=1);
namespace App\Enums\Tenants\Assignments;
enum AssignmentStatus: string
{
    case ACTIVE='active'; case UNDER_REVIEW='under_review'; case SUSPENDED='suspended'; case AUCTIONED='auctioned';
    public function label(): string { return match($this) { self::ACTIVE=>'نشط', self::UNDER_REVIEW=>'قيد المراجعة', self::SUSPENDED=>'متوقف', self::AUCTIONED=>'محول لمزاد' }; }
}
