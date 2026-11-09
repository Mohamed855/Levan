<?php

declare(strict_types=1);

namespace App\Enums\Admin\PublicRequests;

enum TrialRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case ALREADY_SUBSCRIBED = 'already_subscribed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'قيد الانتظار',
            self::APPROVED => 'تمت الموافقة',
            self::REJECTED => 'مرفوض',
            self::ALREADY_SUBSCRIBED => 'مشترك بالفعل',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::ALREADY_SUBSCRIBED => 'info',
        };
    }
}
