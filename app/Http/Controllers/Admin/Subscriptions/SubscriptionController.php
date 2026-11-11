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
use App\Services\Shared\Coupons\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly AdminSubscriptionService $subscriptionService
    ) {
        $this->authorizeResource(Subscription::class, 'subscription');
        $this->middleware('can:subscriptions.view')->only(['index']);
        $this->middleware('can:subscriptions.create')->only(['create', 'store', 'planDuration', 'companyDetails', 'validateCoupon']);
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

        $companiesPayload = $companies->map(fn (Tenant $company): array => [
            'id' => $company->id,
            'company_name' => $company->company_name,
            'commercial_number' => $company->commercial_number,
            'tax_number' => $company->tax_number,
            'address' => $company->address,
            'city' => $company->city ? [
                'id' => $company->city->id,
                'name' => $company->city->name,
            ] : null,
            'last_subscription' => $company->lastSubscription ? [
                'id' => $company->lastSubscription->id,
                'status' => $company->lastSubscription->status?->label() ?? (string) $company->lastSubscription->status,
                'ends_at' => $company->lastSubscription->ends_at?->toDateString(),
                'plan' => $company->lastSubscription->plan ? [
                    'id' => $company->lastSubscription->plan->id,
                    'name' => $company->lastSubscription->plan->name,
                ] : null,
            ] : null,
        ])->values()->all();

        $plansPayload = $plans->map(fn (Plan $plan): array => [
            'id' => $plan->id,
            'name' => $plan->name,
            'code' => $plan->code,
            'is_trial' => $plan->isTrial(),
            'price' => (float) ($plan->price ?? 0),
            'price_after_discount' => (float) ($plan->price_after_discount ?? $plan->price ?? 0),
            'subscription_billing_cycle' => $plan->subscription_billing_cycle?->value ?? (string) $plan->subscription_billing_cycle,
            'included_users' => (int) ($plan->included_users ?? 1),
            'properties_limit' => $plan->properties_limit,
            'storage_quota_gb' => $plan->storage_quota_gb,
            'active_auctions_limit' => $plan->active_auctions_limit,
            'features' => is_array($plan->features) ? $plan->features : [],
        ])->values()->all();

        return view('admin.subscriptions.create', [
            'companies' => $companies,
            'plans' => $plans,
            'companiesPayload' => $companiesPayload,
            'plansPayload' => $plansPayload,
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
                    'is_active' => $updated->status === SubscriptionStatus::ACTIVE,
                    'label' => $updated->status->label(),
                    'color' => $updated->status->color(),
                    'message' => 'تم تغيير حالة الاشتراك بنجاح.',
                ]);
            }

            return redirect()->back()->with('success', 'تم تغيير حالة الاشتراك بنجاح.');
        } catch (\DomainException $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
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
                'ends_at' => system_date($lastSub->ends_at),
            ] : null,
        ]);
    }

    public function validateCoupon(Request $request, CouponService $couponService): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'period_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'coupon_code' => ['required', 'string', 'max:50'],
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);
        $periodMonths = isset($validated['period_months']) ? (int) $validated['period_months'] : null;
        $basePrice = $this->subscriptionService->calculatePeriodBasePrice($plan, $periodMonths);

        if ($plan->isTrial() || $basePrice <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تطبيق كوبون خصم على الباقة المجانية أو التجريبية.',
            ], 422);
        }

        $couponCode = trim((string) $validated['coupon_code']);
        $result = $couponService->validateAndCalculate($couponCode, $basePrice);

        if (empty($result['success'])) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'كود الخصم غير صالح.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'تم تطبيق كود الخصم بنجاح.',
            'coupon_code' => $result['coupon']['code'],
            'discount_type' => $result['coupon']['discount_type'],
            'discount_value' => (float) $result['coupon']['discount_value'],
            'original_total' => round($basePrice, 2),
            'discount_amount' => round((float) $result['raw_discount'], 2),
            'final_total' => round((float) $result['raw_total_after_discount'], 2),
        ]);
    }
}
