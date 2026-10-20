<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Company;

use App\DataTables\Tenants\BusinessCenter\Company\CompaniesDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\BusinessCenter\Company\StoreCompanyRequest;
use App\Http\Requests\Tenants\BusinessCenter\Company\UpdateCompanyRequest;
use App\Http\Requests\Tenants\UpdateResourceStatusRequest;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Company\CompanyAuthorizedPerson;
use App\Services\Tenants\BusinessCenter\Company\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CompanyController extends Controller
{
    protected CompanyService $companyService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    | Inject dependencies and apply authorization middleware.
    */
    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
        $this->authorizeResource(Company::class, 'company');
        $this->middleware('auth');
        $this->middleware('can:companies.view')->only(['index', 'show']);
        $this->middleware('can:companies.create')->only(['create', 'store']);
        $this->middleware('can:companies.edit')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('can:companies.delete')->only(['destroy']);
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    | Render companies list with statistics.
    */
    public function index(CompaniesDataTable $dataTable)
    {
        try {
            $statistics = $this->companyService->getCompanyStatistics();

            return $dataTable->render('tenant.business-center.companies.index', [
                'totalCompanies' => $statistics['total'],
                'activeCompanies' => $statistics['active'],
                'inactiveCompanies' => $statistics['inactive'],
                'archivedCompanies' => $statistics['archived'],
                'recentCompanies' => $statistics['recent'],
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Create Form
    |--------------------------------------------------------------------------
    | Display the form for creating a new company.
    */
    public function create(): View
    {
        return view('tenant.business-center.companies.create');
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    | Persist a newly created company using DTO.
    */
    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        try {
            $dto = $request->toCreateDTO();
            $this->companyService->createCompanyWithLogo($dto, $request->file('logo'));

            return redirect()->to(tenant_route_name('companies.index'))
                ->with('success', 'تم إضافة الشركة بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة الشركة');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    | Display full company details with portfolios and customers.
    */
    public function show(Company $company): View
    {
        $data = $this->companyService->getCompanyDetails($company);

        return view('tenant.business-center.companies.show', array_merge($data, [
            'company' => $company,
            'portfolios' => collect(),
            'customers' => collect(),
            'contracts' => collect(),
            'collections' => collect(),
            'authorized' => $company->authorizedPeople,
            'totalCollected' => 0,
            'pendingCollections' => 0,
            'approvedCollectionsCount' => 0,
            'pendingCollectionsCount' => 0,
            'portfoliosCount' => 0,
            'customersCount' => 0,
            'authorizedCount' => $company->authorizedPeople()->count(),
            'contractsCount' => 0,
        ]));
    }


    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    | Show the form for editing the specified company.
    */
    public function edit(Company $company): View
    {
        return view('tenant.business-center.companies.edit', compact('company'));
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    | Update the specified company using DTO.
    */
    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        try {
            $dto = $request->toUpdateDTO();

            $this->companyService->updateCompanyWithLogo($company, $dto, $request->file('logo'));

            return redirect()->to(tenant_route_name('companies.index'))
                ->with('success', 'تم تحديث الشركة بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث الشركة');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    | Remove the specified company from storage.
    */
    public function destroy(Company $company): RedirectResponse
    {
        try {
            $this->companyService->deleteCompany($company);

            return redirect()->back()->with('success', 'تم حذف الشركة بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Status
    |--------------------------------------------------------------------------
    | Toggle the company's active/inactive status.
    */
    public function toggleStatus(UpdateResourceStatusRequest $request, Company $company): JsonResponse
    {
        try {
            $newStatus = $request->validated('status');
            $this->companyService->updateStatus($company, $newStatus);

            $statusText = $newStatus === 'active' ? 'نشط' : 'غير نشط';

            return response()->json([
                'success' => true,
                'message' => 'تم تبديل الحالة بنجاح',
                'new_status' => $newStatus,
                'status_text' => $statusText,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تبديل الحالة',
            ], 500);
        }
    }
}
