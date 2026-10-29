<?php

namespace App\Http\Controllers\Admin\SubscriptionManagement;

use App\DataTables\Admin\Clients\ClientsDataTable;
use App\DataTables\Admin\SubscriptionMangment\subscriptionMangmentDataTable;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubscriptionManagement\AcceptSubscriptionRequest;
use App\Http\Requests\Admin\SubscriptionManagement\FinalizeCompanySubscriptionRequest;
use App\Http\Requests\Admin\SubscriptionManagement\MassAcceptSubscriptionsRequest;
use App\Http\Requests\Admin\SubscriptionManagement\MassRejectSubscriptionsRequest;
use App\Http\Requests\Admin\SubscriptionManagement\RejectSubscriptionRequest;
use App\Http\Requests\Admin\SubscriptionManagement\UpdateCompanySubscriptionRequest;
use App\Models\Admin\City;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Services\Admin\SubscriptionManagement\SubscriptionManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class SubscriptionManagementController extends Controller
{
    protected const VIEW_PATH = 'admin.subscription-management';

    protected const ROUTE_NAME = 'admin.clients';

    public function __construct(private SubscriptionManagementService $subscriptionManagementService) {}

    public function index(ClientsDataTable $dataTable)
    {
        try {
            $statistics = $this->subscriptionManagementService->getSubscriptionStatistics();
            $cities = City::select('id', 'name')->get();
            $plans = Plan::select('id', 'name')->active()->get();

            $filterOptions = [
                'statuses' => [
                    ['value' => 'active', 'label' => 'نشط'],
                    ['value' => 'pending', 'label' => 'بانتظار التفعيل'],
                    ['value' => 'suspended', 'label' => 'موقوف'],
                ],
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
                'totalSubscriptions' => $statistics['total'],
                'activeSubscriptions' => $statistics['active'],
                'expiredSubscriptions' => $statistics['expired'],
                'pendingSubscriptions' => $statistics['pending'],
                'suspendedSubscriptions' => $statistics['suspended'] ?? 0,
                'expiringSubscriptions' => $statistics['expiring_soon'] ?? 0,
                'totalCompanies' => $statistics['total'],
                'activeCompanies' => $statistics['active'],
                'expiredCompanies' => $statistics['expired'],
                'pendingCompanies' => $statistics['pending'],
                'suspendedCompanies' => $statistics['suspended'] ?? 0,
                'statistics' => $statistics,
                'cities' => $cities,
                'plans' => $plans,
                'filterOptions' => $filterOptions,
            ]);
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    public function pending(subscriptionMangmentDataTable $dataTable)
    {
        try {
            $statistics = $this->subscriptionManagementService->getSubscriptionStatistics();

            return $dataTable->render(self::VIEW_PATH.'.index', [
                'pageTitle' => 'طلبات الاشتراك الجديدة',
                'isExpiringPage' => false,
                'isPendingPage' => true,
                'totalSubscriptions' => $statistics['total'],
                'activeSubscriptions' => $statistics['active'],
                'expiredSubscriptions' => $statistics['expired'],
                'pendingSubscriptions' => $statistics['pending'],
                'expiringSubscriptions' => $statistics['expiring_soon'],
            ]);
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    public function expiring(subscriptionMangmentDataTable $dataTable)
    {
        try {
            $statistics = $this->subscriptionManagementService->getSubscriptionStatistics();

            return $dataTable->render(self::VIEW_PATH.'.index', [
                'pageTitle' => 'اشتراكات تقارب على الانتهاء',
                'isExpiringPage' => true,
                'totalSubscriptions' => $statistics['total'],
                'activeSubscriptions' => $statistics['active'],
                'expiredSubscriptions' => $statistics['expired'],
                'pendingSubscriptions' => $statistics['pending'],
                'expiringSubscriptions' => $statistics['expiring_soon'],
            ]);
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    public function expired(subscriptionMangmentDataTable $dataTable)
    {
        try {
            $statistics = $this->subscriptionManagementService->getSubscriptionStatistics();

            return $dataTable->render(self::VIEW_PATH.'.index', [
                'pageTitle' => 'الاشتراكات المنتهية',
                'isExpiredPage' => true,
                'totalSubscriptions' => $statistics['total'],
                'activeSubscriptions' => $statistics['active'],
                'expiredSubscriptions' => $statistics['expired'],
                'pendingSubscriptions' => $statistics['pending'],
                'expiringSubscriptions' => $statistics['expiring_soon'],
            ]);
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    public function create()
    {
        $cities = City::orderBy('name')->get(['id', 'name']);
        $plans = Plan::where('is_active', true)->orderBy('sort_order', 'asc')->get()
            ->map(fn ($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'code' => $plan->code,
                'price_after_discount' => $plan->price_after_discount,
                'included_users' => $plan->included_users,
                'subscription_duration_type' => $plan->subscription_duration_type,
                'subscription_billing_cycle' => $plan->subscription_billing_cycle,
            ]);

        return view('admin.clients.create', compact('plans', 'cities'));
    }

    public function planDuration(Plan $plan)
    {
        return response()->json($plan->only(['id', 'code', 'subscription_duration_type', 'subscription_billing_cycle', 'included_users']));
    }

    public function store(FinalizeCompanySubscriptionRequest $request)
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('receipt_upload')) {
                $data['receipt_path'] = $request->file('receipt_upload')->store('receipts', 'public');
            }

            $this->subscriptionManagementService->create($data);

            return redirect()->route(self::ROUTE_NAME.'.index')->with('success', 'تم إضافة العميل والاشتراك بنجاح');
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->withInput()->with('error', 'حدث خطأ أثناء إضافة العميل');
        }
    }

    public function edit(Subscription $subscription)
    {
        $plan = $subscription->plan;
        $plans = Plan::get();
        $cities = City::orderBy('name')->get(['id', 'name']);
        $allStatus = SubscriptionStatus::options();

        return view('admin.clients.edit', compact('subscription', 'allStatus', 'plan', 'plans', 'cities'));
    }

    public function update(UpdateCompanySubscriptionRequest $request, Subscription $subscription)
    {
        $validated = $request->validated();

        try {
            $this->subscriptionManagementService->updateSubscription($subscription, $validated, $request->boolean('auto_renew'));

            return redirect()
                ->route(self::ROUTE_NAME.'.index')
                ->with('success', 'تم تحديث بيانات الاشتراك بنجاح');
        } catch (\Exception $e) {
            Log::error('فشل تحديث الاشتراك: '.$e->getMessage(), [
                'subscription_id' => $subscription->id,
                'request_data' => $request->all(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث الاشتراك: '.$e->getMessage());
        }
    }

    public function accept(AcceptSubscriptionRequest $request, Subscription $subscription)
    {
        try {
            $this->subscriptionManagementService->accept($subscription, $request->user('admin')?->getKey());

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم قبول طلب الاشتراك بنجاح',
                ]);
            }

            return redirect()->back()->with('success', 'تم قبول طلب الاشتراك بنجاح');
        } catch (\Exception $e) {
            report($e);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء قبول طلب الاشتراك: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء قبول طلب الاشتراك');
        }
    }

    public function reject(RejectSubscriptionRequest $request, Subscription $subscription)
    {
        $validated = $request->validated();

        try {
            $this->subscriptionManagementService->reject($subscription, $validated['reject_reason'] ?? null);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم رفض طلب الاشتراك بنجاح',
                ]);
            }

            return redirect()->back()->with('success', 'تم رفض طلب الاشتراك بنجاح');
        } catch (\Exception $e) {
            report($e);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء رفض طلب الاشتراك: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء رفض طلب الاشتراك');
        }
    }

    public function massAccept(MassAcceptSubscriptionsRequest $request)
    {
        $validated = $request->validated();

        try {
            $this->subscriptionManagementService->massAccept($validated['ids'], $request->user('admin')?->getKey());

            return response()->json([
                'success' => true,
                'message' => 'تم قبول طلبات الاشتراك المحددة بنجاح',
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء قبول الاشتراكات المحددة: '.$e->getMessage(),
            ], 500);
        }
    }

    public function massReject(MassRejectSubscriptionsRequest $request)
    {
        $validated = $request->validated();

        try {
            $this->subscriptionManagementService->massReject($validated['ids'], $validated['reject_reason'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'تم رفض طلبات الاشتراك المحددة بنجاح',
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء رفض الاشتراكات المحددة: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Subscription $subscription)
    {
        try {
            $this->subscriptionManagementService->delete($subscription);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم نقل الاشتراك إلى الأرشيف بنجاح',
                ]);
            }

            return redirect()
                ->route(self::ROUTE_NAME.'.index')
                ->with('success', 'تم نقل الاشتراك إلى الأرشيف بنجاح');
        } catch (\Exception $e) {
            Log::error('فشل حذف الاشتراك: '.$e->getMessage());

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف الاشتراك: '.$e->getMessage(),
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء حذف الاشتراك: '.$e->getMessage());
        }
    }
}
