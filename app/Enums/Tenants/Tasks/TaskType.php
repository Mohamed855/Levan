<?php
declare(strict_types=1);
namespace App\Enums\Tenants\Tasks;
enum TaskType: string
{
    case PROMISE='promise'; case CALLBACK='callback'; case DOCUMENT_REQUEST='document_request';
    public function label(): string { return match($this) { self::PROMISE=>'وعد بالسداد', self::CALLBACK=>'معاودة اتصال', self::DOCUMENT_REQUEST=>'طلب مستندات' }; }
}
