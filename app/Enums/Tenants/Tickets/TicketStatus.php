<?php

namespace App\Enums\Tenants\Tickets;

enum TicketStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case RESOLVED_PENDING_SURVEY = 'resolved_pending_survey';
    case RESOLVED = 'resolved';
    case REOPENED = 'reopened';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'جديدة',
            self::IN_PROGRESS => 'قيد المعالجة',
            self::RESOLVED_PENDING_SURVEY => 'تم المعالجة وفي انتظار التقييم',
            self::RESOLVED => 'تم المعالجة',
            self::REOPENED => 'معادة',
            self::CLOSED => 'مغلقة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'primary',
            self::IN_PROGRESS => 'warning',
            self::RESOLVED_PENDING_SURVEY => 'info',
            self::RESOLVED => 'success',
            self::REOPENED => 'secondary',
            self::CLOSED => 'dark',
        };
    }

    public function badge(): string
    {
        return sprintf(
            '<span class="badge bg-%s">%s</span>',
            $this->color(),
            $this->label()
        );
    }

    public static function toBadge(?string $status): string
    {
        if (empty($status)) {
            return '<span class="badge bg-secondary">غير محدد</span>';
        }

        $statusEnum = self::tryFrom($status);

        if ($statusEnum === null) {
            return sprintf(
                '<span class="badge bg-secondary">%s</span>',
                ucfirst(str_replace('_', ' ', $status))
            );
        }

        return $statusEnum->badge();
    }
}
