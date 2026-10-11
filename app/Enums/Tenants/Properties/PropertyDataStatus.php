<?php
declare(strict_types=1);
namespace App\Enums\Tenants\Properties;
enum PropertyDataStatus: string
{
    case COMPLETED='completed'; case NEEDS_IMAGES='needs_images'; case INCOMPLETE='incomplete';
    public function label(): string { return match($this) { self::COMPLETED=>'مكتمل', self::NEEDS_IMAGES=>'يحتاج صور', self::INCOMPLETE=>'ناقص بيانات' }; }
}
