<?php

declare(strict_types=1);

namespace App\Actions\Admin\Tenants;

use App\Actions\Admin\Subscriptions\CreateSubscriptionAction;
use App\Models\Access\Role;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenant\User;
use Throwable;

final class ProvisionTenantAction
{
    /** @param array<string, mixed> $data */
    public function execute(array $data): Tenant
    {
        $plan = Plan::query()->findOrFail($data['plan_id']);
        $tenant = null;

        try {
            $tenant = Tenant::query()->create([
                'company_name' => $data['company_name'],
                'commercial_number' => $data['commercial_number'],
                'tax_number' => $data['tax_number'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'address' => $data['address'] ?? null,
                'primary_contact_name' => $data['primary_contact_name'],
                'primary_contact_email' => $data['primary_contact_email'],
                'primary_contact_phone' => $data['primary_contact_phone'],
                'access_type' => 'path',
            ]);
            try {
                tenancy()->initialize($tenant);

                $owner = User::query()->create([
                    'name' => $data['primary_contact_name'],
                    'email' => $data['primary_contact_email'],
                    'phone' => $data['primary_contact_phone'],
                    'password' => $data['password'],
                    'status' => 'active',
                ]);
                $ownerRole = Role::query()->where('guard', 'tenant')->where('name', 'tenant_owner')->firstOrFail();
                $owner->assignRole($ownerRole);
            } finally {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }
            }

            app(CreateSubscriptionAction::class)->execute(
                $tenant,
                $plan,
                (float) $data['price'],
                $data['subscription_status'] ?? 'active',
            );
            return $tenant->fresh('subscriptions');
        } catch (Throwable $exception) {
            if ($tenant !== null) {
                $tenant->delete();
            }
            throw $exception;
        }
    }
}
