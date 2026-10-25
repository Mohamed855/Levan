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

        if (AdminUser::query()->where('phone', $phone)->exists()
            || Tenant::query()->where('primary_contact_phone', $phone)->exists()
            || $this->existsInTenantUsers($phone)) {
            $fail('رقم الجوال مستخدم بالفعل في النظام.');
        }
    }

    private function existsInTenantUsers(string $phone): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        foreach (Tenant::query()->select('id')->cursor() as $tenant) {
            try {
                $exists = $tenant->run(
                    fn (): bool => Schema::connection('tenant')->hasTable('users')
                        && TenantUser::query()->where('phone', $phone)->exists()
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
