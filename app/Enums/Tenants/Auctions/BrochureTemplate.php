<?php

declare(strict_types=1);

namespace App\Enums\Tenants\Auctions;

enum BrochureTemplate: string
{
    case CLASSIC = 'template_classic';
    case MODERN = 'template_modern';
    case INFATH = 'template_infath';

    public function label(): string
    {
        return match ($this) {
            self::CLASSIC => 'البروشور الكلاسيكي الفاخر', self::MODERN => 'البروشور العصري', self::INFATH => 'نموذج إنفاذ الرسمي'
        };
    }
}
