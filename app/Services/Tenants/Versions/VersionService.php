<?php

// app/Services/Tenants/Versions/VersionService.php

namespace App\Services\Tenants\Versions;

use App\Data\Tenants\Versions\VersionReleaseData;
use App\Enums\Admin\Version\VersionStage;
use App\Enums\Admin\Version\VersionTesterType;
use App\Services\Tenants\Shared\CentralVersionGateway;

class VersionService
{
    public function __construct(private readonly CentralVersionGateway $gateway) {}

    private function allowedStagesFor(?string $testerType): array
    {
        return match ($testerType) {
            VersionTesterType::DEVELOPER->value => [VersionStage::ALPHA, VersionStage::BETA, VersionStage::STABLE],
            VersionTesterType::TESTER->value => [VersionStage::BETA,  VersionStage::STABLE],
            default => [VersionStage::STABLE],
        };
    }

    public function latestActiveForTester(?string $testerType): ?VersionReleaseData
    {
        $stages = $this->allowedStagesFor($testerType);

        return $this->gateway->latestActive($stages);
    }

    // رقم اخر نسخة مسموح بها للعميل
    public function latestActiveForTesterId(?string $testerType): ?int
    {
        return $this->latestActiveForTester($testerType)?->id;
    }

    //  هل توجد نسخة أحدث من نسخة العميل الحالية
    public function hasNewerThanForTester(?int $currentTenantVersionId, ?string $testerType): bool
    {
        $latestId = $this->latestActiveForTesterId($testerType);

        if (! $latestId) {
            return false;
        }              // لا توجد نسخ متاحة لنوعه
        if (! $currentTenantVersionId) {
            return true;
        } // ليس لديه نسخة محددة -> يوجد تحديث

        return $latestId > $currentTenantVersionId;
    }

    /** @return array{current_version_id:int,tester_type:?string} */
    public function currentTenantState(string $tenantId): array
    {
        return $this->gateway->currentTenantState($tenantId);
    }
}
