<?php

namespace App\View\Composers;

use App\Services\Tenants\Versions\VersionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TenantVersionBadgeComposer
{
    public function __construct(private VersionService $service) {}

    public function compose(View $view): void
    {
        if (!auth()->check()) {
            $view->with('tenantHasUpgrade', false)->with('latestVersion', null);
            return;
        }

        $tenantId = tenant('id');

        if (!$tenantId) {
            $view->with('tenantHasUpgrade', false)->with('latestVersion', null);
            return;
        }

        $testerType = DB::connection('mysql')->table('version_testers')->where('tenant_id', $tenantId)->where('is_active', 1)->value('type');

        $currentId = (int) (DB::connection('mysql')->table('tenants')->where('id', $tenantId)->value('version_id') ?? 0);


        $latest = $this->service->latestActiveForTester($testerType);
        $hasNew = $this->service->hasNewerThanForTester($currentId, $testerType);

        $view->with('tenantHasUpgrade', $hasNew)->with('latestVersion', $latest);
    }
}
