<?php

namespace App\Http\Controllers\Tenants\Versions;

use App\Http\Controllers\Controller;
use App\Services\Tenants\Versions\VersionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Jobs\Tenants\Versions\UpgradeVersionJob;

class VersionController extends Controller
{
    private string $page = 'tenants.versions';

    public function __construct()
    {
        $this->middleware('can:subscriptions.upgrade');
    }

    public function latest(VersionService $service)
    {
        $tenantId                       = tenant('id');
        [$currentVersion, $testerType]  = $this->getTenantVersionInfo($tenantId);
        $latestVersion                  = $service->latestActiveForTester($testerType);

        if (!$latestVersion || $currentVersion >= $latestVersion->id) {
            return redirect(tenant_route_name('dashboard'))->with('info', 'النظام يعمل على آخر إصدار متاح.');
        }

        return view($this->page . '.latest', compact('latestVersion'));
    }

    public function upgrade(VersionService $service)
    {
        $tenantId                      = tenant('id');
        [$currentVersion, $testerType] = $this->getTenantVersionInfo($tenantId);
        $targetVersion                 = $service->latestActiveForTesterId($testerType);

        if (!$targetVersion || $currentVersion >= $targetVersion) {
            return redirect(tenant_route_name('dashboard'))->with('info', 'النظام يعمل على آخر إصدار متاح.');
        }

        if (Cache::has("tenant:{$tenantId}:upgrading")) {
            return redirect(tenant_route_name('dashboard'))->with('warning', 'هناك عملية ترقية قيد التنفيذ بالفعل.');
        }

        // وضع علامة أن عملية الترقية بدأت
        Cache::put("tenant:{$tenantId}:upgrading", true, now()->addMinutes(30));

        UpgradeVersionJob::dispatch($tenantId, $currentVersion, $targetVersion);

        return redirect(tenant_route_name('dashboard'))->with([
            'success' => 'تم بدء عملية الترقية بنجاح!',
            'info'    => 'العملية قد تستغرق بضع دقائق. سيتم تفعيل وضع الصيانة مؤقتاً.'
        ]);
    }

    /*
    |============================================================================
    |============================================================================
    |                          Private Methods
    |============================================================================
    |============================================================================
    */
    private function getTenantVersionInfo(string $tenantId): array
    {
        $currentVersion = DB::connection('mysql')->table('tenants')->where('id', $tenantId)->value('version_id') ?? 0;
        $testerType     = DB::connection('mysql')->table('version_testers')->where('tenant_id', $tenantId)->where('is_active', 1)->value('type');

        return [$currentVersion, $testerType];
    }
}
