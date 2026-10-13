<?php

namespace App\Http\Controllers\Admin\SubscriptionManagement;

use App\DataTables\Admin\Companies\CompanyDataTable;
use App\DataTables\Admin\SubscriptionMangment\subscriptionMangmentDataTable;
use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Http\Requests\Admin\SubscriptionManagement\FinalizeCompanySubscriptionRequest;
use App\Http\Requests\Admin\SubscriptionManagement\MassAcceptSubscriptionsRequest;
use App\Http\Requests\Admin\SubscriptionManagement\MassRejectSubscriptionsRequest;
use App\Http\Requests\Admin\SubscriptionManagement\RejectSubscriptionRequest;
use App\Http\Requests\Admin\SubscriptionManagement\UpdateCompanySubscriptionRequest;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\City;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Admin\SubscriptionManagement\SubscriptionManagementService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionManagementController extends Controller
{
    protected const VIEW_PATH = 'admin.subscription_manage';

    protected const ROUTE_NAME = 'admin.companies';

    public function __construct(private SubscriptionManagementService $subscriptionManagementService) {}

    public function index(CompanyDataTable $dataTable)
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

            return $dataTable->render('admin.companies.index', [
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
                'price_after_discount' => $plan->price_after_discount,
                'included_users' => $plan->included_users,
                'subscription_duration_type' => $plan->subscription_duration_type,
                'subscription_billing_cycle' => $plan->subscription_billing_cycle,
            ]);

        return view('admin.companies.create', compact('plans', 'cities'));
    }

    public function planDuration(Plan $plan)
    {
        return response()->json($plan->only(['id', 'subscription_duration_type', 'subscription_billing_cycle', 'included_users']));
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

    public function access(Tenant $tenant): RedirectResponse
    {
        tenancy()->initialize($tenant);

        try {
            $user = User::role('Admin')->orderBy('id')->first() ?? User::orderBy('id')->first();

            abort_if(! $user, 404, 'لا يوجد مستخدم إداري لهذا العميل.');
        } finally {
            tenancy()->end();
        }

        $handoff = Crypt::encryptString(json_encode([
            'tenant_id' => $tenant->id,
            'user_id' => $user->getKey(),
            'expires_at' => now()->addMinute()->timestamp,
        ], JSON_THROW_ON_ERROR));

        return redirect()->to(url($tenant->id.'/impersonate?token='.urlencode($handoff)));
    }

    public function edit(Subscription $subscription)
    {
        $plan = $subscription->plan;
        $plans = Plan::get();
        $cities = City::orderBy('name')->get(['id', 'name']);
        $allStatus = SubscriptionStatus::options();

        return view('admin.companies.edit', compact('subscription', 'allStatus', 'plan', 'plans', 'cities'));
    }

    public function update(UpdateCompanySubscriptionRequest $request, Subscription $subscription)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $tenant = Tenant::where('id', $validated['tenant_id'])->firstOrFail();
            $plan = Plan::findOrFail($validated['plan_id']);
            if ($validated['total_users'] < $plan->included_users) {
                throw new InvalidArgumentException('لا يمكن أن يقل عدد المستخدمين عن المستخدمين المشمولين في الباقة.');
            }

            if ($plan->subscription_duration_type === 'limited') {
                $startsAt = Carbon::parse($validated['starts_at']);
                $endsAt = $plan->subscription_billing_cycle === 'monthly'
                    ? $startsAt->copy()->addMonth()
                    : $startsAt->copy()->addYear();
            } else {
                if (empty($validated['ends_at'])) {
                    return redirect()->back()->withInput()->with('error', 'تاريخ نهاية الاشتراك مطلوب للباقات غير محددة المدة');
                }
                $endsAt = Carbon::parse($validated['ends_at']);
            }

            $tenant->update([
                'tax_number' => $validated['tax_number'],
                'city_id' => $validated['city_id'],
                'address' => $validated['address'],
                'status' => $validated['status'] === SubscriptionStatus::ACTIVE->value ? 'active' : 'pending',
            ]);

            $subscription->update([
                'plan_id' => $validated['plan_id'],
                'price' => $validated['price'],
                'status' => $validated['status'],
                'total_users' => $validated['total_users'],
                'used_users' => $validated['licenses_quota'] ?? $subscription->used_users,
                'starts_at' => $validated['starts_at'],
                'ends_at' => $endsAt,
                'next_payment_date' => $validated['next_payment_date'] ?? $subscription->next_payment_date,
                'auto_renew' => $request->has('auto_renew'),
            ]);

            // مزامنة فترة الاشتراك
            $period = $subscription->currentPeriod ?? $subscription->periods()->latest('period_end')->first();
            if ($period) {
                $period->update([
                    'period_start' => $validated['starts_at'],
                    'period_end' => $endsAt,
                ]);
            }

            DB::commit();

            return redirect()
                ->route(self::ROUTE_NAME.'.index')
                ->with('success', 'تم تحديث بيانات الاشتراك بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

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

    public function accept(AdminAuthenticatedRequest $request, Subscription $subscription)
    {
        try {
            DB::beginTransaction();

            $subscription->update([
                'status' => SubscriptionStatus::ACTIVE,
            ]);
            Tenant::whereKey($subscription->tenant_id)->update(['status' => 'active']);

            $subscription->periods()->update([
                'status' => SubscriptionPeriodStatus::ACTIVE,
            ]);

            Bill::where('subscription_id', $subscription->id)
                ->whereIn('status', [BillStatus::PENDING, BillStatus::PENDING_VERIFICATION, BillStatus::UNPAID])
                ->update([
                    'status' => BillStatus::PAID,
                    'paid_at' => now(),
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                ]);

            DB::commit();

            cache()->forget('admin:pending_subscriptions_count');

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم قبول طلب الاشتراك بنجاح',
                ]);
            }

            return redirect()->back()->with('success', 'تم قبول طلب الاشتراك بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
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
            DB::beginTransaction();

            $subscription->update([
                'status' => SubscriptionStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['reject_reason'] ?? null,
            ]);

            $subscription->periods()->update([
                'status' => SubscriptionPeriodStatus::CANCELLED,
            ]);

            Bill::where('subscription_id', $subscription->id)
                ->whereIn('status', [BillStatus::PENDING, BillStatus::PENDING_VERIFICATION, BillStatus::UNPAID])
                ->update([
                    'status' => BillStatus::CANCELLED,
                    'cancelled_at' => now(),
                    'reject_reason' => $validated['reject_reason'] ?? null,
                ]);

            DB::commit();

            cache()->forget('admin:pending_subscriptions_count');

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم رفض طلب الاشتراك بنجاح',
                ]);
            }

            return redirect()->back()->with('success', 'تم رفض طلب الاشتراك بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
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
            DB::beginTransaction();

            foreach ($validated['ids'] as $id) {
                $subscription = Subscription::find($id);
                if ($subscription) {
                    $subscription->update([
                        'status' => SubscriptionStatus::ACTIVE,
                    ]);
                    Tenant::whereKey($subscription->tenant_id)->update(['status' => 'active']);

                    $subscription->periods()->update([
                        'status' => SubscriptionPeriodStatus::ACTIVE,
                    ]);

                    Bill::where('subscription_id', $subscription->id)
                        ->whereIn('status', [BillStatus::PENDING, BillStatus::PENDING_VERIFICATION, BillStatus::UNPAID])
                        ->update([
                            'status' => BillStatus::PAID,
                            'paid_at' => now(),
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);
                }
            }

            DB::commit();

            cache()->forget('admin:pending_subscriptions_count');

            return response()->json([
                'success' => true,
                'message' => 'تم قبول طلبات الاشتراك المحددة بنجاح',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
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
            DB::beginTransaction();

            foreach ($validated['ids'] as $id) {
                $subscription = Subscription::find($id);
                if ($subscription) {
                    $subscription->update([
                        'status' => SubscriptionStatus::CANCELLED,
                        'cancelled_at' => now(),
                        'cancellation_reason' => $validated['reject_reason'] ?? null,
                    ]);

                    $subscription->periods()->update([
                        'status' => SubscriptionPeriodStatus::CANCELLED,
                    ]);

                    Bill::where('subscription_id', $subscription->id)
                        ->whereIn('status', [BillStatus::PENDING, BillStatus::PENDING_VERIFICATION, BillStatus::UNPAID])
                        ->update([
                            'status' => BillStatus::CANCELLED,
                            'cancelled_at' => now(),
                            'reject_reason' => $validated['reject_reason'] ?? null,
                        ]);
                }
            }

            DB::commit();

            cache()->forget('admin:pending_subscriptions_count');

            return response()->json([
                'success' => true,
                'message' => 'تم رفض طلبات الاشتراك المحددة بنجاح',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
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
            $subscription->delete();

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
