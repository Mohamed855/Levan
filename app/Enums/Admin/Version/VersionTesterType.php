<?php

namespace App\Enums\Admin\Version;

enum VersionTesterType: string
{
    case DEVELOPER = 'developer';
    case TESTER    = 'tester';

    public function label(): string
    {
        return match ($this) {
            self::DEVELOPER => 'مطور',
            self::TESTER    => 'مختبر',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DEVELOPER => 'primary',
            self::TESTER    => 'info',
        };
    }


    public function description(): string
    {
        return match ($this) {
            self::DEVELOPER => 'يمكنه رؤية جميع النسخ (تحت التطوير، تجريبية، ومستقرة)',
            self::TESTER    => 'يمكنه رؤية النسخ التجريبية والمستقرة فقط',
        };
    }
}
