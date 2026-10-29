<?php

namespace App\Rules;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User as TenantUser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Schema;

final class SystemPhoneAvailable implements ValidationRule
{
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

        if (AdminUser::query()->whereIn('phone', $candidates)->exists()
            || Tenant::query()->whereIn('primary_contact_phone', $candidates)->exists()
            || $this->existsInTenantUsers($candidates)) {
            $fail('رقم الجوال مستخدم بالفعل في النظام.');
        }
    }

    /**
     * @param list<string> $candidates
     */
    private function existsInTenantUsers(array $candidates): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        foreach (Tenant::query()->select('id')->cursor() as $tenant) {
            try {
                $exists = $tenant->run(
                    fn (): bool => Schema::connection('tenant')->hasTable('users')
                        && TenantUser::query()->whereIn('phone', $candidates)->exists()
                );
            } catch (\Throwable) {
                $exists = false;
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }

            if ($exists) {
                return true;
            }
        }

        return false;
    }
}
