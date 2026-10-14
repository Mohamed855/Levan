<?php

namespace App\Http\Controllers\Tenants\Versions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Versions\UpgradeVersionRequest;
use App\Jobs\Tenants\Versions\UpgradeVersionJob;
use App\Services\Tenants\Versions\VersionService;
use Illuminate\Support\Facades\Cache;

class VersionController extends Controller
{
    private string $page = 'tenants.versions';

    public function __construct()
    {
        $this->middleware('can:subscriptions.upgrade');
    }

    public function latest(VersionService $service)
    {
        $tenantId = tenant('id');
        ['current_version_id' => $currentVersion, 'tester_type' => $testerType] = $service->currentTenantState($tenantId);
        $latestVersion = $service->latestActiveForTester($testerType);

        if (! $latestVersion || $currentVersion >= $latestVersion->id) {
            return redirect(tenant_route_name('dashboard'))->with('info', 'النظام يعمل على آخر إصدار متاح.');
        }

        return view($this->page.'.latest', compact('latestVersion'));
    }

    public function upgrade(UpgradeVersionRequest $request, VersionService $service)
    {
        $tenantId = tenant('id');
        ['current_version_id' => $currentVersion, 'tester_type' => $testerType] = $service->currentTenantState($tenantId);
        $targetVersion = $service->latestActiveForTesterId($testerType);

        if (! $targetVersion || $currentVersion >= $targetVersion || (int) $request->validated('target_id') !== $targetVersion) {
            return redirect(tenant_route_name('dashboard'))->with('info', 'النظام يعمل على آخر إصدار متاح.');
        }

        if (! Cache::add("tenant:{$tenantId}:upgrading", true, now()->addMinutes(30))) {
            return redirect(tenant_route_name('dashboard'))->with('warning', 'هناك عملية ترقية قيد التنفيذ بالفعل.');
        }

        UpgradeVersionJob::dispatch($tenantId, $currentVersion, $targetVersion);

        return redirect(tenant_route_name('dashboard'))->with([
            'success' => 'تم بدء عملية الترقية بنجاح!',
            'info' => 'العملية قد تستغرق بضع دقائق. سيتم تفعيل وضع الصيانة مؤقتاً.',
        ]);
    }
}
