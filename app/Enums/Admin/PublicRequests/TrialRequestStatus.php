<?php

declare(strict_types=1);

namespace App\Enums\Admin\PublicRequests;

enum TrialRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'قيد الانتظار',
            self::APPROVED => 'تمت الموافقة',
            self::REJECTED => 'مرفوض',
        };
    }
}
