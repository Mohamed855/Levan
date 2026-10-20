<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\Admin\AuctionTemplates\AuctionTemplateFieldDefinitionService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

final class AuctionTemplateFieldDefinitionsRule implements ValidationRule
{
    /** @param list<string> $requiredKeys */
    public function __construct(
        private readonly array $requiredKeys,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('يجب تحديد مواضع الحقول المطلوبة داخل تصميم القالب.');

            return;
        }

        try {
            $definitions = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($definitions)) {
                throw new \UnexpectedValueException('Definitions must be an array.');
            }

            $definitions = app(AuctionTemplateFieldDefinitionService::class)->normalize($definitions);
        } catch (Throwable) {
            $fail('تعذر قراءة مواضع الحقول داخل تصميم القالب.');

            return;
        }

        $keys = array_column($definitions, 'key');
        if (count($keys) !== count(array_unique($keys))) {
            $fail('لا يمكن تحديد المفتاح نفسه أكثر من مرة في القالب.');

            return;
        }

        $missing = array_diff($this->requiredKeys, $keys);
        if ($missing !== []) {
            $fail('يجب تحديد مواضع جميع الحقول الإلزامية داخل التصميم.');
        }
    }
}
