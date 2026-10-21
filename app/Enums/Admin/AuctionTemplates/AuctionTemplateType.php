<?php

declare(strict_types=1);

namespace App\Enums\Admin\AuctionTemplates;

enum AuctionTemplateType: string
{
    case BROCHURE = 'brochure';
    case BILLBOARD = 'billboard';
    case SOCIAL = 'social';

    public function label(): string
    {
        return match ($this) {
            self::BROCHURE => 'قالب البروشور',
            self::BILLBOARD => 'قالب اللوحة الإعلانية',
            self::SOCIAL => 'قالب منصات التواصل الاجتماعي',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::BROCHURE => 'primary',
            self::BILLBOARD => 'warning',
            self::SOCIAL => 'success',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $t) => [
                'value' => $t->value,
                'label' => $t->label(),
                'color' => $t->color(),
            ],
            self::cases()
        );
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
