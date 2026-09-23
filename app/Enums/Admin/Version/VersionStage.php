<?php

namespace App\Enums\Admin\Version;

enum VersionStage: string
{
    case ALPHA   = 'alpha';
    case TESTING    = 'testing';
    case BETA    = 'beta';
    case STABLE  = 'stable';

    public function label(): string
    {
        return match ($this) {
            self::ALPHA   => 'تحت التطوير',
            self::TESTING    => 'قيد الاختبار',
            self::BETA    => 'تجريبية',
            self::STABLE  => 'مستقرة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ALPHA   => 'warning',
            self::TESTING    => 'secondary',
            self::BETA    => 'info',
            self::STABLE  => 'success',
        };
    }



    public function description(): string
    {
        return match ($this) {
            self::ALPHA   => 'نسخة تحت التطوير وقد تكون غير مستقرة وتُعرض للمطورين فقط',
            self::TESTING    => 'نسخة قيد الاختبار من قبل فريق الـ QA للتأكد من الجودة',
            self::BETA    => 'نسخة تجريبية للاختبار قبل الإصدار  النهائي  وتُعرض للعملاء محددين',
            self::STABLE  => 'نسخة جاهزة للإصدار  وتُعرض لكل العملاء',
        };
    }

    public static function values(): array
    {
        return array_map(fn(self $t) => $t->value, self::cases());
    }
}
