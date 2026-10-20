<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Settings\Domain;

use App\Actions\Admin\Settings\ManageControlDomainAction;
use App\DataTables\Admin\Setting\SettingDomainDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Http\Requests\Admin\Settings\Domain\StoreControlDomainRequest;
use App\Http\Requests\Admin\Settings\Domain\UpdateControlDomainRequest;
use App\Models\Admin\ControlDomain\ControlDomain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class SettingsDomainController extends Controller
{
    private string $page = 'admin.settings.domains';

    public function __construct(private readonly ManageControlDomainAction $domains)
    {
        $this->authorizeResource(ControlDomain::class, 'domain');
        $this->middleware('can:domain_extension_prices.settings');
    }

    public function index(SettingDomainDataTable $dataTable): mixed
    {
        $filterOptions = ['statuses' => [
            ['value' => 'active', 'label' => 'نشط'],
            ['value' => 'inactive', 'label' => 'غير نشط'],
        ]];

        return $dataTable->render($this->page.'.index', compact('filterOptions'));
    }

    public function create(): View
    {
        return view($this->page.'.create');
    }

    public function store(StoreControlDomainRequest $request): RedirectResponse
    {
        $this->domains->create($request->validated());

        return redirect()->route('admin.settings.domains.index')->with('success', 'تم إضافة الامتداد بنجاح');
    }

    public function edit(ControlDomain $domain): View
    {
        return view($this->page.'.edit', compact('domain'));
    }

    public function update(UpdateControlDomainRequest $request, ControlDomain $domain): RedirectResponse
    {
        $this->domains->update($domain, $request->validated());

        return redirect()->route('admin.settings.domains.index')->with('success', 'تم التعديل بنجاح');
    }

    public function toggleStatus(AdminAuthenticatedRequest $request, ControlDomain $domain): JsonResponse
    {
        $status = $this->domains->toggleStatus($domain);

        return response()->json([
            'success' => true,
            'message' => $status ? 'تم تفعيل امتداد الدومين بنجاح' : 'تم تعطيل امتداد الدومين بنجاح',
            'status' => $status,
        ]);
    }

    public function destroy(AdminAuthenticatedRequest $request, ControlDomain $domain): RedirectResponse|JsonResponse
    {
        $this->domains->delete($domain);
        $message = 'تم حذف الامتداد بنجاح';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('admin.settings.domains.index')->with('success', $message);
    }
}
