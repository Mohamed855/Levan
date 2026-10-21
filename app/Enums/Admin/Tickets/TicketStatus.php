<?php

declare(strict_types=1);

namespace App\Enums\Admin\Tickets;

enum TicketStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
    case REOPENED = 'reopened';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'جديدة',
            self::IN_PROGRESS => 'قيد المعالجة',
            self::RESOLVED => 'تم الحل',
            self::CLOSED => 'مغلقة',
            self::REOPENED => 'معادة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'primary',
            self::IN_PROGRESS => 'warning',
            self::RESOLVED => 'success',
            self::CLOSED => 'dark',
            self::REOPENED => 'info',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'color' => $case->color(),
            ],
            self::cases()
        );
    }
}
