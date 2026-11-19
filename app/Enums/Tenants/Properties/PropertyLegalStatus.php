<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Properties;

enum PropertyLegalStatus: string
{
    case CLEAR = 'clear';
    case SEIZED = 'seized';
    case MORTGAGED = 'mortgaged';
    case ENDOWED = 'endowed';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CLEAR => 'سليم',
            self::SEIZED => 'محجوز',
            self::MORTGAGED => 'مرهون',
            self::ENDOWED => 'وقف',
            self::OTHER => 'أخرى',
        };
    }

    public static function options(): array
    {
        return [
            self::CLEAR->value => 'سليم',
            self::SEIZED->value => 'محجوز',
            self::MORTGAGED->value => 'مرهون',
            self::ENDOWED->value => 'وقف',
            self::OTHER->value => 'أخرى',
        ];
    }
}
