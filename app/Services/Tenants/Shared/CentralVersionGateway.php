<?php

declare(strict_types=1);

namespace App\Services\Tenants\Shared;

use App\Data\Tenants\Versions\VersionReleaseData;
use App\Enums\Admin\Version\VersionStage;
use App\Models\Admin\Version\Version;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Facades\Tenancy;

final class CentralVersionGateway
{
    /** @param list<VersionStage> $stages */
    public function latestActive(array $stages): ?VersionReleaseData
    {
        return Tenancy::central(function () use ($stages): ?VersionReleaseData {
            $version = Version::query()
                ->where('is_active', true)
                ->whereIn('stage', array_map(fn (VersionStage $stage) => $stage->value, $stages))
                ->latest('id')
                ->first();

            return $version === null ? null : new VersionReleaseData(
                id: (int) $version->id,
                version: (string) $version->version,
                changelog: $version->changelog,
                stage: $version->stage,
                released_at: $version->released_at,
            );
        });
    }

    /** @return array{current_version_id:int,tester_type:?string} */
    public function currentTenantState(string $tenantId): array
    {
        return Tenancy::central(fn (): array => [
            'current_version_id' => (int) (DB::table('tenants')->where('id', $tenantId)->value('version_id') ?? 0),
            'tester_type' => DB::table('version_testers')->where('tenant_id', $tenantId)->where('is_active', true)->value('type'),
        ]);
    }

    public function updateTenantVersion(string $tenantId, int $versionId): void
    {
        Tenancy::central(fn (): int => DB::table('tenants')->where('id', $tenantId)->update([
            'version_id' => $versionId,
            'updated_at' => now(),
        ]));
    }
}
