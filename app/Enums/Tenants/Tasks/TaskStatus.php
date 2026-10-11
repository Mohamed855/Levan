<?php
declare(strict_types=1);
namespace App\Enums\Tenants\Tasks;
enum TaskStatus: string
{
    case PENDING='pending'; case COMPLETED='completed'; case OVERDUE='overdue';
    public function label(): string { return match($this) { self::PENDING=>'معلقة', self::COMPLETED=>'مكتملة', self::OVERDUE=>'متأخرة' }; }
}
