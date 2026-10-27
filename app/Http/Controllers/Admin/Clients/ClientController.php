<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Clients;

use App\DataTables\Admin\Clients\ClientsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Http\Requests\Admin\Clients\StoreClientRequest;
use App\Http\Requests\Admin\Clients\UpdateClientRequest;
use App\Http\Requests\Admin\Clients\CheckAdminEmailUniqueRequest;
use App\Http\Requests\Admin\Clients\CheckAdminPhoneUniqueRequest;
use App\Http\Requests\Admin\Clients\CheckCommercialNumberUniqueRequest;
use App\Http\Requests\Admin\Clients\CheckCompanyNameUniqueRequest;
use App\Http\Requests\Admin\Clients\CheckTaxNumberUniqueRequest;
use App\Models\Admin\City;
use App\Models\Admin\Client;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Admin\Clients\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function __construct(
        private readonly ClientService $clientService
    ) {
        $this->authorizeResource(Client::class, 'client');
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (! $user?->can('clients.create') && ! $user?->can('companies.create') && ! $user?->can('clients.edit') && ! $user?->can('companies.edit')) {
                abort(403);
            }

            return $next($request);
        })->only([
            'planDuration',
            'checkCommercialNumberUnique',
            'checkCompanyNameUnique',
            'checkTaxNumberUnique',
            'checkAdminEmailUnique',
            'checkAdminPhoneUnique',
        ]);
    }

    public function index(ClientsDataTable $dataTable)
    {
        $statistics = $this->clientService->getCompanyStatistics();
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

        return $dataTable->render('admin.clients.index', [
            'totalClients' => $statistics['total'],
            'activeClients' => $statistics['active'],
            'suspendedClients' => $statistics['suspended'],
            'pendingClients' => $statistics['pending'],
            'withoutSubscriptionClients' => $statistics['withoutSubscription'],
            'trialClients' => $statistics['trial'] ?? 0,
            // Backwards compatibility variables
            'totalCompanies' => $statistics['total'],
            'activeCompanies' => $statistics['active'],
            'suspendedCompanies' => $statistics['suspended'],
            'pendingCompanies' => $statistics['pending'],
            'withoutSubscriptionCompanies' => $statistics['withoutSubscription'],
            'trialCompanies' => $statistics['trial'] ?? 0,
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

        return view('admin.clients.create', [
            'cities' => $cities,
            'plans' => $plans,
            'trialPrefill' => $trialPrefill,
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        try {
            $client = $this->clientService->createCompany($request->validated());

            $redirectRoute = request()->is('admin/companies*') ? 'admin.companies.index' : 'admin.clients.index';

            return redirect()->route($redirectRoute)
                ->with('success', "تم إنشاء العميل {$client->company_name} بنجاح.");
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إنشاء العميل. يرجى المحاولة مرة أخرى.');
        }
    }

    public function show(Client $client): View
    {
        $data = $this->clientService->getShowData($client);
        $data['client'] = $client;

        return view('admin.clients.show', $data);
    }

    public function edit(Client $client): View
    {
        $cities = City::all();
        $client->load(['companySettings', 'city']);

        return view('admin.clients.edit', [
            'client' => $client,
            'company' => $client,
            'settings' => $client->companySettings,
            'cities' => $cities,
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        try {
            $this->clientService->updateCompany($client, $request->validated());

            $redirectRoute = request()->is('admin/companies*') ? 'admin.companies.index' : 'admin.clients.index';

            return redirect()->route($redirectRoute)
                ->with('success', "تم تحديث بيانات العميل {$client->company_name} بنجاح.");
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث بيانات العميل.');
        }
    }

    public function destroy(AdminAuthenticatedRequest $request, Client $client): RedirectResponse|JsonResponse
    {
        try {
            $this->clientService->deleteCompany($client);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف العميل بنجاح ونقله إلى الأرشيف.',
                ]);
            }

            $redirectRoute = request()->is('admin/companies*') ? 'admin.companies.index' : 'admin.clients.index';

            return redirect()->route($redirectRoute)
                ->with('success', 'تم حذف العميل بنجاح ونقله إلى الأرشيف.');
        } catch (\Throwable $e) {
            report($e);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف العميل.',
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء حذف العميل.');
        }
    }

    public function planDuration(Plan $plan): JsonResponse
    {
        return response()->json([
            'id' => $plan->id,
            'code' => $plan->code,
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
     * التحقق من تفرد اسم الشركة / العميل
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
            'message' => $exists ? 'اسم الشركة / العميل مسجل بالفعل' : 'اسم الشركة / العميل متاح',
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
