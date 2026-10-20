<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Notifications;

enum NotificationType: string
{
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case INFO = 'info';
    case DANGER = 'danger';

    public function label(): string
    {
        return match ($this) {
            self::SUCCESS => 'نجاح',
            self::WARNING => 'تحذير',
            self::INFO => 'معلومات',
            self::DANGER => 'خطر',
        };
    }
}
