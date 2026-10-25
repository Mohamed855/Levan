<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\Tenants\Shared\CentralAuctionTemplatesGateway;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class PublishedTemplateVersionRule implements ValidationRule
{
    public function __construct(private readonly string $type) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_numeric($value) || ! app(CentralAuctionTemplatesGateway::class)->findPublished((int) $value, $this->type)) {
            $fail('نسخة القالب المحددة غير منشورة أو غير متاحة.');
        }
    }
}
