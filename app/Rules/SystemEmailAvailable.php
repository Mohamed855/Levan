<?php

namespace App\Rules;

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Tenants\Tenant;
use App\Models\User as CentralUser;
use App\Models\Tenants\UserMangment\User\User as TenantUser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Schema;

final class SystemEmailAvailable implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $email = strtolower(trim((string) $value));

        if ($email === '') {
            return;
        }

        $existsInCentralUsers = Schema::connection('mysql')->hasTable('users')
            && CentralUser::query()->whereRaw('LOWER(email) = ?', [$email])->exists();

        if (AdminUser::query()->whereRaw('LOWER(email) = ?', [$email])->exists()
            || $existsInCentralUsers
            || Tenant::query()->whereRaw('LOWER(primary_contact_email) = ?', [$email])->exists()
            || $this->existsInTenantUsers($email)) {
            $fail('البريد الإلكتروني موجود بالفعل');
        }
    }

    private function existsInTenantUsers(string $email): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        foreach (Tenant::query()->select('id')->cursor() as $tenant) {
            try {
                $exists = $tenant->run(
                    fn (): bool => Schema::connection('tenant')->hasTable('users')
                        && TenantUser::query()->whereRaw('LOWER(email) = ?', [$email])->exists()
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
