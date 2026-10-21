<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Properties;

enum PropertyAssignmentStatus: string
{
    case LINKED = 'linked';
    case UNLINKED = 'unlinked';

    public function label(): string
    {
        return match ($this) {
            self::LINKED => 'مرتبط بإسناد',
            self::UNLINKED => 'غير مرتبط بإسناد',
        };
    }
}
