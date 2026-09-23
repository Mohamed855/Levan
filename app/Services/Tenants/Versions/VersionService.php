<?php
// app/Services/Tenants/Versions/VersionService.php

namespace App\Services\Tenants\Versions;

use App\Enums\Admin\Version\VersionStage;
use App\Enums\Admin\Version\VersionTesterType;
use App\Models\Admin\Version\Version;

class VersionService
{
    private function allowedStagesFor(?string $testerType): array
    {
        return match ($testerType) {
            VersionTesterType::DEVELOPER->value => [VersionStage::ALPHA, VersionStage::BETA, VersionStage::STABLE],
            VersionTesterType::TESTER->value    => [VersionStage::BETA,  VersionStage::STABLE],
            default                             => [VersionStage::STABLE],
        };
    }

    public function latestActiveForTester(?string $testerType): ?Version
    {
        $stages = $this->allowedStagesFor($testerType);

        return Version::query()->where('is_active', true)->whereIn('stage', array_map(fn($e) => $e->value, $stages))->orderByDesc('id')->first();
    }

    // رقم اخر نسخة مسموح بها للعميل
    public function latestActiveForTesterId(?string $testerType): ?int
    {
        $stages = $this->allowedStagesFor($testerType);

        $id = Version::query()->where('is_active', true)->whereIn('stage', array_map(fn($e) => $e->value, $stages))->max('id');

        return $id ?: null;
    }

    //  هل توجد نسخة أحدث من نسخة العميل الحالية
    public function hasNewerThanForTester(?int $currentTenantVersionId, ?string $testerType): bool
    {
        $latestId = $this->latestActiveForTesterId($testerType);

        if (!$latestId) return false;              // لا توجد نسخ متاحة لنوعه
        if (!$currentTenantVersionId) return true; // ليس لديه نسخة محددة -> يوجد تحديث
        return $latestId > $currentTenantVersionId;
    }
}
