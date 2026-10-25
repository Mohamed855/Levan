<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Stancl\Tenancy\Facades\Tenancy;

final class ExistsInCentralAuctionTemplates implements ValidationRule
{
    public function __construct(
        private readonly string $type
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT);
        if ($id === false) {
            $fail('معرف القالب غير صالح.');
            return;
        }

        $exists = Tenancy::central(function () use ($id): bool {
            return AuctionTemplate::query()
                ->where('id', $id)
                ->where('type', $this->type)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->exists();
        });

        if (! $exists) {
            $fail('القالب المختار غير متاح أو غير نشط في النظام.');
        }
    }
}
