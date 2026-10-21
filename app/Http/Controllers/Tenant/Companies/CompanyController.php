<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Companies;

use App\Actions\Tenant\Companies\SaveCompanyAction;
use App\DataTables\Tenant\Companies\CompanyDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Companies\StoreCompanyRequest;
use App\Http\Requests\Tenant\Companies\UpdateCompanyRequest;
use App\Models\Tenant\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:companies.view')->only(['index', 'show']);
        $this->middleware('can:companies.create')->only(['create', 'store']);
        $this->middleware('can:companies.edit')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('can:companies.delete')->only('destroy');
    }

    public function index(CompanyDataTable $dataTable)
    {
        $statistics = [
            'total'            => Company::query()->count(),
            'active'           => Company::query()->where('status', 'active')->count(),
            'inactive'         => Company::query()->where('status', 'inactive')->count(),
            'added_this_month' => Company::query()->where('created_at', '>=', now()->startOfMonth())->count(),
            'archived'         => Company::onlyTrashed()->count(),
        ];

        $filterOptions = [
            'status' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'inactive', 'label' => 'معطل'],
            ],
        ];

        return $dataTable->render('tenant.companies.index', compact('statistics', 'filterOptions'));
    }

    public function create(): View
    {
        return view('tenant.companies.create', ['company' => new Company()]);
    }

    public function store(StoreCompanyRequest $request, SaveCompanyAction $action): RedirectResponse
    {
        $company = $action->create($request->validated(), $request->file('logo'));

        return to_route('tenant.companies.show', $company)->with('success', 'تم إضافة الشركة بنجاح.');
    }

    public function show(Company $company): View
    {
        return view('tenant.companies.show', [
            'company' => $company->load('authorizedPeople'),
        ]);
    }

    public function edit(Company $company): View
    {
        return view('tenant.companies.edit', [
            'company' => $company->load('authorizedPeople'),
        ]);
    }

    public function update(
        UpdateCompanyRequest $request,
        Company $company,
        SaveCompanyAction $action
    ): RedirectResponse {
        $action->update($company, $request->validated(), $request->file('logo'));

        return to_route('tenant.companies.show', $company)->with('success', 'تم حفظ بيانات الشركة بنجاح.');
    }

    public function toggleStatus(Company $company, SaveCompanyAction $action): JsonResponse
    {
        $newStatus = $action->toggleStatus($company);

        return response()->json([
            'success'   => true,
            'status'    => $newStatus,
            'is_active' => $newStatus === 'active',
            'message'   => $newStatus === 'active' ? 'تم تفعيل الشركة بنجاح.' : 'تم تعطيل الشركة بنجاح.',
        ]);
    }

    public function destroy(
        Request $request,
        Company $company,
        SaveCompanyAction $action
    ): RedirectResponse|JsonResponse {
        $action->delete($company);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الشركة بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تمت أرشفة الشركة بنجاح.');
    }
}
