<?php

declare(strict_types=1);

namespace App\Enums\Shared;

enum ApprovalStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'معلق',
            self::APPROVED => 'معتمد',
            self::REJECTED => 'مرفوض',
        };
    }
}
