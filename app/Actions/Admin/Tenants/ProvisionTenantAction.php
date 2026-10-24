<?php

declare(strict_types=1);

namespace App\Actions\Admin\Tenants;

use App\Actions\Admin\Subscriptions\CreateSubscriptionAction;
use App\Models\Access\Role;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenant\User;
use App\Services\Tenant\Shared\CentralLicenseGateway;
use Closure;
use LogicException;
use Throwable;

final class ProvisionTenantAction
{
    public function __construct(private readonly CentralLicenseGateway $licenses) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data, ?Closure $onTenantCreated = null): Tenant
    {
        $plan = Plan::query()->findOrFail($data['plan_id']);
        $tenant = Tenant::query()->where('commercial_number', $data['commercial_number'])->first();
        $createdTenant = false;

        if ($tenant && $tenant->primary_contact_email !== $data['primary_contact_email']) {
            throw new LogicException('بيانات المنشأة تتعارض مع منشأة مسجلة بالفعل.');
        }

        try {
            if (! $tenant) {
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
                $createdTenant = true;
                if ($onTenantCreated !== null) {
                    $onTenantCreated($tenant);
                }
            }
            try {
                tenancy()->initialize($tenant);

                $owner = User::query()->firstOrCreate(['email' => $data['primary_contact_email']], [
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

            $subscription = $tenant->subscriptions()->where('plan_id', $plan->getKey())->latest('id')->first();
            if ($subscription === null) {
                $subscription = app(CreateSubscriptionAction::class)->execute(
                    $tenant,
                    $plan,
                    (float) $data['price'],
                    $data['subscription_status'] ?? 'active',
                );
            }
            $this->licenses->ensureCoreSeat((string) $tenant->getKey(), (int) $subscription->getKey(), (int) $owner->getKey());

            return $tenant->fresh('subscriptions');
        } catch (Throwable $exception) {
            if ($createdTenant && $tenant !== null) {
                // Provisioning never completed, so a soft-deleted central row would
                // block a retry by commercial number. Force deletion also invokes the
                // tenant database cleanup listener introduced for this exact boundary.
                $tenant->forceDelete();
            }
            throw $exception;
        }
    }
}
