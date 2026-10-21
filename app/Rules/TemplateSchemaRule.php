<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\Auctions\TemplateSchema;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;

final class TemplateSchemaRule implements ValidationRule
{
    public function __construct(private readonly string $templateType) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            app(TemplateSchema::class)->validate($value, $this->templateType);
        } catch (ValidationException $exception) {
            $fail($exception->validator->errors()->first() ?: 'بنية القالب غير صحيحة.');
        }
    }
}
