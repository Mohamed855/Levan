<?php

namespace App\Http\Controllers\Admin\Settings\Domain;

use App\DataTables\Admin\Setting\SettingDomainDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Http\Requests\Admin\Settings\Domain\StoreControlDomainRequest;
use App\Http\Requests\Admin\Settings\Domain\UpdateControlDomainRequest;
use App\Models\Admin\ControlDomain\ControlDomain;
use Illuminate\Http\JsonResponse;

class SettingsDomainController extends Controller
{
    private $page  = "admin.settings.domains";
    private $route = "admin.settings.domains";

    public function __construct()
    {
        $this->middleware('can:domain_extension_prices.settings');
    }

    public function index(SettingDomainDataTable $dataTable)
    {
        try {
            $filterOptions = [
                'statuses' => [
                    ['value' => 'active', 'label' => 'نشط'],
                    ['value' => 'inactive', 'label' => 'غير نشط'],
                ],
            ];

            return $dataTable->render($this->page . '.index', compact('filterOptions'));
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    public function create()
    {
        return view($this->page . '.create');
    }

    // Store extension
    public function store(StoreControlDomainRequest $request)
    {
        ControlDomain::create($request->validated());

        return redirect()->route('admin.settings.domains.index')->with('success', 'تم إضافة الامتداد بنجاح');
    }

    // Show edit form
    public function edit(ControlDomain $domain)
    {
        return view($this->page . '.edit', compact('domain'));
    }

    // Update
    public function update(UpdateControlDomainRequest $request, ControlDomain $domain)
    {
        $domain->update($request->validated());

        return redirect()->route('admin.settings.domains.index')->with('success', 'تم التعديل بنجاح');
    }

    public function toggleStatus(AdminAuthenticatedRequest $request, ControlDomain $domain): JsonResponse
    {
        $domain->update(['is_active' => ! $domain->is_active]);

        return response()->json([
            'success' => true,
            'message' => $domain->is_active ? 'تم تفعيل امتداد الدومين بنجاح' : 'تم تعطيل امتداد الدومين بنجاح',
        ]);
    }

    // Delete
    public function destroy(AdminAuthenticatedRequest $request, ControlDomain $domain)
    {
        $domain->delete();
        return redirect()->route('admin.settings.domains.index')->with('success', 'تم حذف الامتداد بنجاح');
    }
}
