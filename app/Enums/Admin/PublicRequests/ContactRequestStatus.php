<?php

declare(strict_types=1);

namespace App\Enums\Admin\PublicRequests;

enum ContactRequestStatus: string
{
    case PENDING = 'pending';
    case REPLIED = 'replied';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'قيد الانتظار',
            self::REPLIED => 'تم الرد',
        };
    }
}
