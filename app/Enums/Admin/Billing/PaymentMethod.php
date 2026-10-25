<?php

namespace App\Enums\Admin\Billing;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case ONLINE = 'online';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'نقداً',
            self::BANK_TRANSFER => 'تحويل بنكي',
            self::ONLINE => 'دفع إلكتروني',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CASH => 'success',
            self::BANK_TRANSFER => 'info',
            self::ONLINE => 'primary',
        };
    }

    public function badge(): string
    {
        $color = $this->color();
        $label = $this->label();

        return "<span class='badge bg-{$color} '>{$label} </span>";
    }

    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
