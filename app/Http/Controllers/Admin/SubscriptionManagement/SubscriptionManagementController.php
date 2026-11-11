<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\SubscriptionManagement;

use App\DataTables\Admin\SubscriptionMangment\subscriptionMangmentDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubscriptionManagement\AcceptSubscriptionRequest;
use App\Http\Requests\Admin\SubscriptionManagement\MassAcceptSubscriptionsRequest;
use App\Http\Requests\Admin\SubscriptionManagement\MassRejectSubscriptionsRequest;
use App\Http\Requests\Admin\SubscriptionManagement\RejectSubscriptionRequest;
use App\Models\Admin\City;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Services\Admin\SubscriptionManagement\SubscriptionManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class SubscriptionManagementController extends Controller
{
    protected const VIEW_PATH = 'admin.subscription-management';

    public function __construct(private readonly SubscriptionManagementService $subscriptionManagementService) {}

    public function expiring(subscriptionMangmentDataTable $dataTable)
    {
        try {
            $statistics = $this->subscriptionManagementService->getSubscriptionStatistics();
            $plans = Plan::select('id', 'name')->active()->get();
            $cities = City::select('id', 'name')->get();

            $filterOptions = [
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

            return $dataTable->render(self::VIEW_PATH.'.index', [
                'pageTitle' => 'اشتراكات تقارب على الانتهاء',
                'isExpiringPage' => true,
                'totalSubscriptions' => $statistics['total'],
                'activeSubscriptions' => $statistics['active'],
                'expiredSubscriptions' => $statistics['expired'],
                'expiringSubscriptions' => $statistics['expiring_soon'],
                'plans' => $plans,
                'cities' => $cities,
                'filterOptions' => $filterOptions,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    public function expired(subscriptionMangmentDataTable $dataTable)
    {
        try {
            $statistics = $this->subscriptionManagementService->getSubscriptionStatistics();
            $plans = Plan::select('id', 'name')->active()->get();
            $cities = City::select('id', 'name')->get();

            $filterOptions = [
                'statuses' => [
                    ['value' => 'expired', 'label' => 'منتهي'],
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
            ];

            return $dataTable->render(self::VIEW_PATH.'.index', [
                'pageTitle' => 'الاشتراكات المنتهية',
                'isExpiredPage' => true,
                'totalSubscriptions' => $statistics['total'],
                'activeSubscriptions' => $statistics['active'],
                'expiredSubscriptions' => $statistics['expired'],
                'expiringSubscriptions' => $statistics['expiring_soon'],
                'plans' => $plans,
                'cities' => $cities,
                'filterOptions' => $filterOptions,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    public function accept(AcceptSubscriptionRequest $request, Subscription $subscription): JsonResponse|RedirectResponse
    {
        try {
            $this->subscriptionManagementService->accept($subscription, $request->user('admin')?->getKey());

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم قبول طلب الاشتراك وتفعيله بنجاح',
                ]);
            }

            return redirect()->back()->with('success', 'تم قبول طلب الاشتراك وتفعيله بنجاح');
        } catch (\Throwable $e) {
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

    public function reject(RejectSubscriptionRequest $request, Subscription $subscription): JsonResponse|RedirectResponse
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
        } catch (\Throwable $e) {
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

    public function massAccept(MassAcceptSubscriptionsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $this->subscriptionManagementService->massAccept($validated['ids'], $request->user('admin')?->getKey());

            return response()->json([
                'success' => true,
                'message' => 'تم قبول طلبات الاشتراك المحددة بنجاح',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء قبول الاشتراكات المحددة: '.$e->getMessage(),
            ], 500);
        }
    }

    public function massReject(MassRejectSubscriptionsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $this->subscriptionManagementService->massReject($validated['ids'], $validated['reject_reason'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'تم رفض طلبات الاشتراك المحددة بنجاح',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء رفض الاشتراكات المحددة: '.$e->getMessage(),
            ], 500);
        }
    }
}
