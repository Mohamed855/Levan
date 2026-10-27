<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Subscriptions;

use App\DataTables\Admin\Subscriptions\SubscriptionDataTable;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Http\Requests\Admin\Subscriptions\StoreSubscriptionRequest;
use App\Models\Admin\City;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Services\Admin\Subscriptions\AdminSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly AdminSubscriptionService $subscriptionService
    ) {
        $this->authorizeResource(Subscription::class, 'subscription');
        $this->middleware('can:subscriptions.view')->only(['index']);
        $this->middleware('can:subscriptions.create')->only(['create', 'store', 'planDuration', 'companyDetails']);
        $this->middleware('can:subscriptions.delete')->only(['destroy']);
        $this->middleware('can:subscriptions.toggle_status')->only(['toggleStatus']);
    }

    public function index(SubscriptionDataTable $dataTable)
    {
        $statistics = $this->subscriptionService->getSubscriptionStatistics();
        $plans = Plan::select('id', 'name')->active()->get();
        $cities = City::select('id', 'name')->get();

        $filterOptions = [
            'statuses' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'pending', 'label' => 'معلق'],
                ['value' => 'suspended', 'label' => 'موقوف'],
                ['value' => 'expired', 'label' => 'منتهي'],
                ['value' => 'trial', 'label' => 'تجريبي'],
                ['value' => 'cancelled', 'label' => 'ملغي'],
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
                ['value' => '7', 'label' => 'تنتهي خلال 7 أيام'],
                ['value' => '30', 'label' => 'تنتهي خلال 30 يوماً'],
            ],
        ];

        return $dataTable->render('admin.subscriptions.index', [
            'totalSubscriptions' => $statistics['total'],
            'activeSubscriptions' => $statistics['active'],
            'pendingSubscriptions' => $statistics['pending'],
            'expiredSubscriptions' => $statistics['expired'],
            'expiringSubscriptions' => $statistics['expiring_soon'],
            'plans' => $plans,
            'cities' => $cities,
            'filterOptions' => $filterOptions,
        ]);
    }

    public function create(): View
    {
        // Eligible companies: those with no subscriptions or whose last subscription is expired
        $companies = Tenant::query()
            ->with(['city', 'lastSubscription.plan'])
            ->where(function ($query) {
                $query->whereDoesntHave('subscriptions')
                    ->orWhereHas('lastSubscription', function ($q) {
                        $q->where('status', SubscriptionStatus::EXPIRED);
                    });
            })
            ->get();

        $plans = Plan::active()->get();

        return view('admin.subscriptions.create', [
            'companies' => $companies,
            'plans' => $plans,
        ]);
    }

    public function store(StoreSubscriptionRequest $request): RedirectResponse
    {
        try {
            $company = Tenant::findOrFail($request->validated('company_id'));
            $subscription = $this->subscriptionService->createSubscription($company, $request->validated());

            return redirect()->route('admin.subscriptions.index')
                ->with('success', "تم إنشاء الاشتراك رقم {$subscription->subscription_number} بنجاح.");
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إنشاء الاشتراك: '.$e->getMessage());
        }
    }

    public function toggleStatus(AdminAuthenticatedRequest $request, Subscription $subscription): JsonResponse|RedirectResponse
    {
        try {
            $updated = $this->subscriptionService->toggleStatus($subscription);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'status' => $updated->status->value,
                    'label' => $updated->status->label(),
                    'color' => $updated->status->color(),
                    'message' => 'تم تغيير حالة الاشتراك بنجاح.',
                ]);
            }

            return redirect()->back()->with('success', 'تم تغيير حالة الاشتراك بنجاح.');
        } catch (\Throwable $e) {
            report($e);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تغيير حالة الاشتراك.',
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء تغيير حالة الاشتراك.');
        }
    }

    public function destroy(AdminAuthenticatedRequest $request, Subscription $subscription): JsonResponse|RedirectResponse
    {
        try {
            $subscriptionNumber = $subscription->subscription_number;
            $this->subscriptionService->deleteSubscription($subscription);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "تم حذف الاشتراك رقم {$subscriptionNumber} نهائياً.",
                ]);
            }

            return redirect()->route('admin.subscriptions.index')
                ->with('success', "تم حذف الاشتراك رقم {$subscriptionNumber} نهائياً.");
        } catch (\Throwable $e) {
            report($e);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف الاشتراك.',
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء حذف الاشتراك.');
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

    public function companyDetails(Tenant $company): JsonResponse
    {
        $company->load(['city', 'lastSubscription.plan']);
        $lastSub = $company->lastSubscription;

        return response()->json([
            'id' => $company->id,
            'company_name' => $company->company_name,
            'commercial_number' => $company->commercial_number,
            'tax_number' => $company->tax_number,
            'city_name' => $company->city?->name ?: '-',
            'address' => $company->address ?: '-',
            'last_subscription' => $lastSub ? [
                'number' => $lastSub->subscription_number,
                'plan' => $lastSub->plan?->name ?: '-',
                'status' => $lastSub->status->label(),
                'ends_at' => $lastSub->ends_at?->format('Y-m-d') ?: '-',
            ] : null,
        ]);
    }
}
