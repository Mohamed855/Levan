<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Notifications;

enum ReadStatus: string
{
    case READ = 'read';
    case UNREAD = 'unread';

    public function label(): string
    {
        return match ($this) {
            self::READ => 'مقروء',
            self::UNREAD => 'غير مقروء',
        };
    }
}
