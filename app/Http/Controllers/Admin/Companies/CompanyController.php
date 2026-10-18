<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Companies;

use App\DataTables\Admin\Companies\CompanyDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Http\Requests\Admin\Companies\CheckAdminEmailUniqueRequest;
use App\Http\Requests\Admin\Companies\CheckAdminPhoneUniqueRequest;
use App\Http\Requests\Admin\Companies\CheckCommercialNumberUniqueRequest;
use App\Http\Requests\Admin\Companies\CheckCompanyNameUniqueRequest;
use App\Http\Requests\Admin\Companies\CheckTaxNumberUniqueRequest;
use App\Http\Requests\Admin\Companies\StoreCompanyRequest;
use App\Http\Requests\Admin\Companies\UpdateCompanyRequest;
use App\Models\Admin\City;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Admin\Companies\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function __construct(
        private readonly CompanyService $companyService
    ) {
        $this->authorizeResource(Tenant::class, 'company');
        $this->middleware('can:companies.view')->only(['index']);
        $this->middleware('can:companies.create')->only(['create', 'store', 'planDuration']);
        $this->middleware('can:companies.show')->only(['show']);
        $this->middleware('can:companies.edit')->only(['edit', 'update']);
        $this->middleware('can:companies.delete')->only(['destroy']);
        $this->middleware(function ($request, $next) {
            if (! auth()->user()?->can('companies.create') && ! auth()->user()?->can('companies.edit')) {
                abort(403);
            }

            return $next($request);
        })->only([
            'checkCommercialNumberUnique',
            'checkCompanyNameUnique',
            'checkTaxNumberUnique',
            'checkAdminEmailUnique',
            'checkAdminPhoneUnique',
        ]);
    }

    public function index(CompanyDataTable $dataTable)
    {
        $statistics = $this->companyService->getCompanyStatistics();
        $cities = City::select('id', 'name')->get();
        $plans = Plan::select('id', 'name')->active()->get();

        $filterOptions = [
            'subscriptionStatuses' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'pending', 'label' => 'معلق'],
                ['value' => 'suspended', 'label' => 'موقوف'],
                ['value' => 'expired', 'label' => 'منتهي'],
                ['value' => 'trial', 'label' => 'تجريبي'],
            ],
            'plans' => $plans->map(fn ($plan): array => [
                'value' => $plan->id,
                'label' => $plan->name,
            ]),
            'cities' => $cities->map(fn ($city): array => [
                'value' => $city->id,
                'label' => $city->name,
            ]),
            'expiring' => [
                ['value' => '7', 'label' => 'خلال 7 أيام'],
                ['value' => '30', 'label' => 'خلال 30 يوماً'],
            ],
        ];

        return $dataTable->render('admin.companies.index', [
            'totalCompanies' => $statistics['total'],
            'activeCompanies' => $statistics['active'],
            'suspendedCompanies' => $statistics['suspended'],
            'pendingCompanies' => $statistics['pending'],
            'withoutSubscriptionCompanies' => $statistics['withoutSubscription'],
            'cities' => $cities,
            'plans' => $plans,
            'filterOptions' => $filterOptions,
        ]);
    }

    public function create(): View
    {
        $cities = City::all();
        $plans = Plan::active()->get();
        $trialRequest = request()->filled('trial_request')
            ? TrialRequest::query()->whereKey(request()->integer('trial_request'))->where('status', 'approved')->first()
            : null;
        $trialPrefill = $trialRequest ? [
            'name' => $trialRequest->customer_name,
            'email' => $trialRequest->customer_email,
            'phone' => $trialRequest->customer_phone,
        ] : [];

        return view('admin.companies.create', [
            'cities' => $cities,
            'plans' => $plans,
            'trialPrefill' => $trialPrefill,
        ]);
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        try {
            $company = $this->companyService->createCompany($request->validated());

            return redirect()->route('admin.companies.index')
                ->with('success', "تم إنشاء الشركة {$company->company_name} بنجاح.");
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إنشاء الشركة. يرجى المحاولة مرة أخرى.');
        }
    }

    public function show(Tenant $company): View
    {
        $data = $this->companyService->getShowData($company);

        return view('admin.companies.show', $data);
    }

    public function edit(Tenant $company): View
    {
        $cities = City::all();
        $company->load(['companySettings', 'city']);

        return view('admin.companies.edit', [
            'company' => $company,
            'settings' => $company->companySettings,
            'cities' => $cities,
        ]);
    }

    public function update(UpdateCompanyRequest $request, Tenant $company): RedirectResponse
    {
        try {
            $this->companyService->updateCompany($company, $request->validated());

            return redirect()->route('admin.companies.index')
                ->with('success', "تم تحديث بيانات الشركة {$company->company_name} بنجاح.");
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث بيانات الشركة.');
        }
    }

    public function destroy(AdminAuthenticatedRequest $request, Tenant $company): RedirectResponse|JsonResponse
    {
        try {
            $this->companyService->deleteCompany($company);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف الشركة بنجاح ونقلها إلى الأرشيف.',
                ]);
            }

            return redirect()->route('admin.companies.index')
                ->with('success', 'تم حذف الشركة بنجاح ونقلها إلى الأرشيف.');
        } catch (\Throwable $e) {
            report($e);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف الشركة.',
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء حذف الشركة.');
        }
    }

    public function planDuration(Plan $plan): JsonResponse
    {
        return response()->json([
            'duration_type' => $plan->subscription_duration_type,
            'billing_cycle' => $plan->subscription_billing_cycle,
            'price' => $plan->price_after_discount,
            'total_users' => $plan->included_users,
            'limits' => [
                'users' => $plan->included_users,
                'properties' => $plan->properties_limit,
                'storage_gb' => $plan->storage_quota_gb,
                'auctions' => $plan->active_auctions_limit,
            ],
            'features' => $plan->features ?? [],
        ]);
    }

    /**
     * التحقق من تفرد رقم السجل التجاري
     */
    public function checkCommercialNumberUnique(CheckCommercialNumberUniqueRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Tenant::where('commercial_number', $validated['commercial_number']);
        if (! empty($validated['company_id'])) {
            $query->where('id', '!=', $validated['company_id']);
        }

        $exists = $query->exists();

        return response()->json([
            'available' => ! $exists,
            'message' => $exists ? 'رقم السجل التجاري مسجل بالفعل' : 'رقم السجل التجاري متاح',
        ]);
    }

    /**
     * التحقق من تفرد اسم الشركة
     */
    public function checkCompanyNameUnique(CheckCompanyNameUniqueRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Tenant::where('company_name', $validated['company_name']);
        if (! empty($validated['company_id'])) {
            $query->where('id', '!=', $validated['company_id']);
        }

        $exists = $query->exists();

        return response()->json([
            'available' => ! $exists,
            'message' => $exists ? 'اسم الشركة مسجل بالفعل' : 'اسم الشركة متاح',
        ]);
    }

    /**
     * التحقق من تفرد الرقم الضريبي
     */
    public function checkTaxNumberUnique(CheckTaxNumberUniqueRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Tenant::where('tax_number', $validated['tax_number']);
        if (! empty($validated['company_id'])) {
            $query->where('id', '!=', $validated['company_id']);
        }

        $exists = $query->exists();

        return response()->json([
            'available' => ! $exists,
            'message' => $exists ? 'الرقم الضريبي مسجل بالفعل' : 'الرقم الضريبي متاح',
        ]);
    }

    /**
     * التحقق من تفرد البريد الإلكتروني للمسؤول
     */
    public function checkAdminEmailUnique(CheckAdminEmailUniqueRequest $request): JsonResponse
    {
        $email = $request->validated('email');

        $existsInTenants = Tenant::where('primary_contact_email', $email)->exists();
        $existsInUsers = User::where('email', $email)->exists();

        $exists = $existsInTenants || $existsInUsers;

        return response()->json([
            'available' => ! $exists,
            'message' => $exists ? 'البريد الإلكتروني مستخدم من قبل' : 'البريد الإلكتروني متاح',
        ]);
    }

    /**
     * التحقق من تفرد رقم هاتف المسؤول
     */
    public function checkAdminPhoneUnique(CheckAdminPhoneUniqueRequest $request): JsonResponse
    {
        $phone = $request->validated('phone');

        $existsInTenants = Tenant::where('primary_contact_phone', $phone)->exists();
        $existsInUsers = User::where('phone', $phone)->exists();

        $exists = $existsInTenants || $existsInUsers;

        return response()->json([
            'available' => ! $exists,
            'message' => $exists ? 'رقم الهاتف مستخدم من قبل' : 'رقم الهاتف متاح',
        ]);
    }
}
