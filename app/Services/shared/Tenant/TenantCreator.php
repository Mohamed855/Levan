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
        $user                = $this->tenantInitializer->setupTenantEnvironment($tenant, $metadata);

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
            'company_name'      => $metadata['billing_company_name'] ?? 'غير محدد',
            'commercial_number' => $metadata['billing_commercial_number'] ?? null,
            'db_host'           => $connection['host'] ?? env('DB_HOST', '127.0.0.1'),
            'db_port'           => env('DB_PORT', '3306'),
            'version_id'        => $settings?->default_landing_version_id,
        ];

        return Tenant::create($data);
    }
}
