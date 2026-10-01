<?php

namespace App\Services\shared\Tenant;

use App\Models\Admin\Settings\Settings;
use App\Models\Admin\Tenants\Tenant;
use App\Services\shared\Cpanel\CpanelService;
use Illuminate\Support\Facades\DB;

class TenantCreator
{
    public function __construct(private CpanelService $cpanelService, private TenantInitializer $tenantInitializer) {}

    public function createTenantEnvironment(array $metadata): array
    {
        [$tenantId, $dbUser] = $this->generateIdentifiers();
        $connection          = $this->cpanelService->createDatabase($tenantId, $dbUser);
        $tenant              = $this->registerTenantRecord($tenantId, $connection, $metadata);

        $tenant->companySettings()->create([
            'email_notifications_enabled'   => (bool) ($metadata['email_notifications_enabled'] ?? true),
            'two_factor_enabled'            => (bool) ($metadata['two_factor_enabled'] ?? false),
            'external_integrations_enabled' => (bool) ($metadata['external_integrations_enabled'] ?? false),
        ]);

        $user = $this->tenantInitializer->setupTenantEnvironment($tenant, $metadata);

        if ($user && isset($user->global_uid)) {
            $tenant->update(['primary_user_global_uid' => $user->global_uid]);
        }

        return [$tenant, $user];
    }


    // Create tenant without subscription
    // public function initializeTenantEnvironmentWithoutSubscription($data)
    // {
    //     return DB::transaction(function () use ($data) {
    //         [$tenantId, $dbUser]    = $this->generateIdentifiers();
    //         $billingData            = $this->mapBillingFromRequest($data);
    //         $connection             = $this->cpanelService->createDatabase($tenantId, $dbUser);
    //         $tenant                 = $this->registerTenantRecord($tenantId, $connection, $billingData);

    //         $this->tenantInitializer->initializeWithoutCoreSeat($tenant, $billingData);

    //         return $tenant;
    //     } );
    // }

    /*
    |============================================================================
    |============================================================================
    |                             Private methods
    |============================================================================
    |============================================================================
    */
    private function generateIdentifiers(): array
    {
        do {
            $random = random_int(10000, 99999);
            $randomId = 'etmam_levan_' . $random;
        } while (Tenant::whereKey($randomId)->exists());

        return [$randomId, 'u' . $random];
    }

    private function registerTenantRecord(string $tenantId, array $connection, array $metadata): Tenant
    {
        $settings = Settings::first();

        $data = [
            'id'                => $tenantId,
            'folderName'        => $tenantId,
            'db_database'       => $connection['database'],
            'db_username'       => $connection['username'],
            'db_password'       => $connection['password'],
            'company_name'           => $metadata['billing_company_name'] ?? 'غير محدد',
            'tax_number'             => $metadata['billing_tax_number'] ?? null,
            'city_id'                => $metadata['billing_city_id'] ?? null,
            'address'                => $metadata['billing_address'] ?? null,
            'commercial_number'      => $metadata['billing_commercial_number'] ?? null,
            'primary_contact_name'   => $metadata['billing_name'] ?? null,
            'primary_contact_email'  => $metadata['billing_email'] ?? null,
            'primary_contact_phone'  => $metadata['billing_phone'] ?? null,
            'db_host'                => $connection['host'] ?? env('DB_HOST', '127.0.0.1'),
            'db_port'           => env('DB_PORT', '3306'),
            'version_id'        => $settings?->default_landing_version_id,
        ];

        return Tenant::create($data);
    }
}
