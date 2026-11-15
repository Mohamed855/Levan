<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tenants\Tenant;
use App\Models\User as CentralUser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Schema;

final class SystemEmailAvailable implements ValidationRule
{
    public function __construct(
        private readonly ?string $ignoreTenantId = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = strtolower(trim((string) $value));

        if ($email === '') {
            return;
        }

        $existsInCentralUsers = Schema::connection('mysql')->hasTable('users')
            && CentralUser::query()->whereRaw('LOWER(email) = ?', [$email])->exists();

        $tenantQuery = Tenant::query()->whereRaw('LOWER(primary_contact_email) = ?', [$email]);
        if ($this->ignoreTenantId !== null) {
            $tenantQuery->where('id', '!=', $this->ignoreTenantId);
        }

        if (AdminUser::query()->whereRaw('LOWER(email) = ?', [$email])->exists()
            || $existsInCentralUsers
            || $tenantQuery->exists()) {
            $fail('البريد الإلكتروني موجود بالفعل');
        }
    }
}
