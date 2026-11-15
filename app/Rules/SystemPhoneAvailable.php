<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tenants\Tenant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class SystemPhoneAvailable implements ValidationRule
{
    public function __construct(
        private readonly ?string $ignoreTenantId = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phone = (string) $value;

        if ($phone === '') {
            return;
        }

        $candidates = [$phone];
        if (str_starts_with($phone, '05') && strlen($phone) === 10) {
            $candidates[] = '966'.substr($phone, 1);
        } elseif (str_starts_with($phone, '9665') && strlen($phone) === 12) {
            $candidates[] = '0'.substr($phone, 3);
        }

        $tenantQuery = Tenant::query()->whereIn('primary_contact_phone', $candidates);
        if ($this->ignoreTenantId !== null) {
            $tenantQuery->where('id', '!=', $this->ignoreTenantId);
        }

        if (AdminUser::query()->whereIn('phone', $candidates)->exists()
            || $tenantQuery->exists()) {
            $fail('رقم الجوال مستخدم بالفعل في النظام.');
        }
    }
}
