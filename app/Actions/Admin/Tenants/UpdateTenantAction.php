<?php

declare(strict_types=1);

namespace App\Actions\Admin\Tenants;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenant\Setting;
use App\Models\Tenant\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/** Keeps the central tenant profile and its tenant-local owner/security setting coherent. */
final class UpdateTenantAction
{
    /** @param array<string, mixed> $data */
    public function execute(Tenant $tenant, array $data): Tenant
    {
        $ownerSnapshot = null;
        $settingSnapshot = null;
        $settingExisted = false;

        try {
            DB::transaction(function () use ($tenant, $data, &$ownerSnapshot, &$settingSnapshot, &$settingExisted): void {
                $tenant = Tenant::query()->lockForUpdate()->findOrFail($tenant->getKey());
                $tenant->fill(Arr::only($data, [
                    'company_name', 'commercial_number', 'tax_number', 'city_id', 'address',
                    'primary_contact_name', 'primary_contact_email', 'primary_contact_phone',
                ]));
                $tenant->save();

                tenancy()->initialize($tenant);
                try {
                    $owner = User::role('tenant_owner')->orderBy('id')->first()
                        ?? User::query()->where('email', $tenant->getOriginal('primary_contact_email'))->first();
                    if (! $owner instanceof User) {
                        throw new RuntimeException('تعذر العثور على مالك المنشأة لتحديث بياناته.');
                    }

                    if ($owner->email !== $tenant->primary_contact_email
                        && User::query()->where('email', $tenant->primary_contact_email)->exists()) {
                        throw ValidationException::withMessages(['primary_contact_email' => 'بريد المسؤول مستخدم داخل هذه المنشأة.']);
                    }

                    $ownerSnapshot = $owner->only(['name', 'email', 'phone']);
                    $owner->update([
                        'name' => $tenant->primary_contact_name,
                        'email' => $tenant->primary_contact_email,
                        'phone' => $tenant->primary_contact_phone,
                    ]);

                    if (array_key_exists('force_two_factor_auth', $data)) {
                        $setting = Setting::query()->first();
                        $settingExisted = $setting !== null;
                        $settingSnapshot = $setting?->data;
                        $setting ??= new Setting();
                        $settingsData = $setting->data ?? [];
                        $settingsData['force_two_factor_auth'] = (bool) $data['force_two_factor_auth'];
                        $setting->data = $settingsData;
                        $setting->save();
                    }
                } finally {
                    if (tenancy()->initialized) {
                        tenancy()->end();
                    }
                }
            });
        } catch (Throwable $exception) {
            $this->restoreTenantLocalState($tenant, $ownerSnapshot, $settingSnapshot, $settingExisted);
            throw $exception;
        }

        return $tenant->fresh();
    }

    /** @param array<string, mixed>|null $ownerSnapshot @param array<string, mixed>|null $settingSnapshot */
    private function restoreTenantLocalState(Tenant $tenant, ?array $ownerSnapshot, ?array $settingSnapshot, bool $settingExisted): void
    {
        if ($ownerSnapshot === null) {
            return;
        }

        try {
            tenancy()->initialize($tenant->fresh() ?? $tenant);
            $owner = User::role('tenant_owner')->orderBy('id')->first();
            $owner?->update($ownerSnapshot);

            if ($settingExisted) {
                $setting = Setting::query()->first();
                if ($setting !== null) {
                    $setting->update(['data' => $settingSnapshot ?? []]);
                }
            } elseif ($settingSnapshot === null) {
                Setting::query()->first()?->delete();
            }
        } catch (Throwable $restoreException) {
            Log::critical('Unable to compensate tenant-local profile update.', [
                'tenant_id' => $tenant->getKey(),
                'exception' => $restoreException,
            ]);
        } finally {
            if (tenancy()->initialized) {
                tenancy()->end();
            }
        }
    }
}
