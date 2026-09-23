<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Company;

use App\Http\Controllers\Controller;
use App\DataTables\Tenants\BusinessCenter\Company\CompaniesDataTable;
use App\DataTables\Tenants\BusinessCenter\Company\CompanyCollectionsDataTable;
use App\DataTables\Tenants\BusinessCenter\Company\CompanyCustomersDataTable;
use App\Http\Requests\Tenants\BusinessCenter\Company\StoreCompanyRequest;
use App\Http\Requests\Tenants\BusinessCenter\Company\UpdateCompanyRequest;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Company\CompanyAuthorizedPerson;
use App\Models\Tenants\BusinessCenter\Contract\Contract;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Services\Tenants\BusinessCenter\Company\CompanyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Services\DataTable;

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
        $this->middleware('auth');
        $this->middleware('can:الشركات')->only(['index', 'show']);
        $this->middleware('can:إضافة شركة')->only(['create', 'store']);
        $this->middleware('can:تعديل شركة')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('can:حذف شركة')->only(['destroy']);
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

            return $dataTable->render('tenants.business_center.companies.index', [
                'totalCompanies'       => $statistics['total'],
                'activeCompanies'      => $statistics['active'],
                'inactiveCompanies'    => $statistics['inactive'],
                'archivedCompanies'    => $statistics['archived'],
                'recentCompanies'      => $statistics['recent'],
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
        return view('tenants.business_center.companies.create');
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

            if ($request->hasFile('logo')) {
                $path = $request->file('logo')->store('companies/logos', 'tenant_public');
                $dto->withLogoPath($path);
            }

            $this->companyService->createCompany($dto);

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
   public function show(
    Company $company, 
    CompanyCustomersDataTable $customerTable, 
    CompanyCollectionsDataTable $collectionTable
) {
    // ✅ Set company IDs
    $customerTable->setCompanyId($company->id);
    $collectionTable->setCompanyId($company->id);
    

    if (request()->ajax()) {

    // if (request()->segment(3) === 'customers') {
    //         return $customerTable->ajax();
    //     }
     
        
        return $collectionTable->ajax();
    }

    // Get company data
    $data = $this->companyService->getCompanyDetails($company);

    // Get portfolios
    $portfolios = $company->portfolios()
        ->withCount('customers')
        ->orderBy('created_at', 'desc')
        ->get();

    // Get customers
    $customers = $company->customers()
        ->with('portfolio:id,name')
        ->orderBy('created_at', 'desc')
        ->get();

    // Get contracts
    $contracts = $company->hasMany(Contract::class, 'company_id')
        ->orderBy('created_at', 'desc')
        ->get();

    // Get authorized people
    $authorized = CompanyAuthorizedPerson::where('company_id', $company->id)->get();

    // Get collections
    $collections = Collection::whereHas('customer', function($query) use ($company) {
        $query->where('company_id', $company->id);
    })
    ->with(['customer:id,name', 'paymentMethod:id,name'])
    ->orderBy('collection_date', 'desc')
    ->get();

    // Calculate stats
    $totalCollected = $collections->where('status', 'approved')->sum('collected_amount');
    $pendingCollections = $collections->where('status', 'pending')->sum('collected_amount');
    $approvedCollectionsCount = $collections->where('status', 'approved')->count();
    $pendingCollectionsCount = $collections->where('status', 'pending')->count();

    $portfoliosCount = $portfolios->count();
    $customersCount = $customers->count();
    $authorizedCount = $company->authorizedPeople()->count();
    $contractsCount = $contracts->count();

    return view('tenants.business_center.companies.show', array_merge($data, [
        'company' => $company,
        'portfolios' => $portfolios,
        'customers' => $customers,
        'contracts' => $contracts,
        'collections' => $collections,
        'authorized' => $authorized,
        
        // ✅ DataTables HTML
        'customerTable' => $customerTable->html(),
        'collectionTable' => $collectionTable->html(),
        
        // Stats
        'portfoliosCount' => $portfoliosCount,
        'customersCount' => $customersCount,
        'authorizedCount' => $authorizedCount,
        'contractsCount' => $contractsCount,
        'totalCollected' => $totalCollected,
        'pendingCollections' => $pendingCollections,
        'approvedCollectionsCount' => $approvedCollectionsCount,
        'pendingCollectionsCount' => $pendingCollectionsCount,
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
        return view('tenants.business_center.companies.edit', compact('company'));
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

            if ($request->hasFile('logo')) {
                if ($company->logo_path) {
                    Storage::disk('tenant_public')->delete($company->logo_path);
                }
                $path = $request->file('logo')->store('companies/logos', 'tenant_public');
                $dto->withLogoPath($path);
            }

            $this->companyService->updateCompany($company, $dto);

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
    public function toggleStatus(Company $company): JsonResponse
    {
        try {
            $newStatus = $company->status === 'active' ? 'inactive' : 'active';
            $company->update(['status' => $newStatus]);

            $statusText = $newStatus === 'active' ? 'نشط' : 'غير نشط';

            return response()->json([
                'success' => true,
                'message' => 'تم تبديل الحالة بنجاح',
                'new_status' => $newStatus,
                'status_text' => $statusText
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تبديل الحالة'
            ], 500);
        }
    }
}
