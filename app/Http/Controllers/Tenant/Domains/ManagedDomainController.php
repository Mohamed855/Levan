<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Domains;

use App\DataTables\Tenant\Domains\ManagedDomainDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Domains\CreatePlatformSubdomainRequest;
use App\Http\Requests\Tenant\Domains\LinkManagedDomainRequest;
use App\Models\Admin\ManagedDomain;
use App\Services\Tenant\Shared\CentralManagedDomainGateway;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

final class ManagedDomainController extends Controller
{
    public function __construct(private readonly CentralManagedDomainGateway $domains)
    {
        $this->middleware('can:domains.view')->only('index');
        $this->middleware('can:domains.manage')->only(['link', 'createSubdomain', 'verify', 'destroy']);
    }

    public function index(ManagedDomainDataTable $dataTable)
    {
        return $dataTable->render('tenant.domains.index', [
            'stats' => $this->domains->stats(),
            'platformDomains' => $this->domains->activePlatformDomains(),
        ]);
    }

    public function link(LinkManagedDomainRequest $request): RedirectResponse
    {
        try {
            $result = $this->domains->linkExternalDomain((string) $request->validated('domain'));
            return to_route('tenant.domains.index')->with('domain_verification', [
                'domain' => $result['domain']->domain, 'token' => $result['verification_token'],
            ])->with('success', 'أُضيف طلب الربط. أضف سجل DNS ثم تحقق منه.');
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }
    }

    public function createSubdomain(CreatePlatformSubdomainRequest $request): RedirectResponse
    {
        try {
            $domain = $this->domains->createPlatformSubdomain((string) $request->validated('label'), (int) $request->validated('control_domain_id'));
            return to_route('tenant.domains.index')->with('success', 'تم إرسال طلب تهيئة '.$domain->domain.'.');
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }
    }

    public function verify(int|string $domain): RedirectResponse
    {
        try {
            $model = $this->domains->findForCurrentTenant($domain);
            $this->authorize('update', $model);
            $this->domains->verifyExternalDomain($model);
            return to_route('tenant.domains.index')->with('success', 'تم التحقق من DNS وربط النطاق بنجاح.');
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }

    public function destroy(int|string $domain): RedirectResponse
    {
        $model = $this->domains->findForCurrentTenant($domain);
        $this->authorize('delete', $model);
        try {
            $this->domains->delete($model);
            return to_route('tenant.domains.index')->with('success', 'تمت إزالة النطاق من سجل المنشأة.');
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }
}
