<?php

namespace App\Http\Controllers\Admin\SubscriptionManagement;

use App\DataTables\Admin\SubscriptionMangment\subscriptionMangmentDataTable;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubscriptionManagement\StoreSubscriptionRequest;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Admin\SubscriptionManagement\SubscriptionManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionManagementController extends Controller
{
    protected const VIEW_PATH  = "admin.subscription_manage";
    protected const ROUTE_NAME =  "admin.subscriptionManage";

    public function __construct(private SubscriptionManagementService $subscriptionManagementService) {}

    public function index(subscriptionMangmentDataTable $dataTable)
    {
        try {
            $statistics = $this->subscriptionManagementService->getSubscriptionStatistics();

            return $dataTable->render(self::VIEW_PATH . '.index', [
                'totalSubscriptions'     => $statistics['total'],
                'activeSubscriptions'    => $statistics['active'],
                'expiredSubscriptions'   => $statistics['expired'],
                'suspendedSubscriptions' => $statistics['suspended'],
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    public function create()
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order', 'asc')->get()
            ->map(fn($plan) => [
                'id'                   => $plan->id,
                'name'                 => $plan->name,
                'price_after_discount' => $plan->price_after_discount,
            ]);

        return view(self::VIEW_PATH . '.create', compact('plans'));
    }

    public function store(StoreSubscriptionRequest $request)
    {
        try {
            $data = $request->validated();

            // dd($data);
            if ($request->hasFile('receipt_upload')) {
                $data['receipt_path'] = $request->file('receipt_upload')->store('receipts', 'public');
            }

            $this->subscriptionManagementService->create($data);

            return redirect()->route(self::ROUTE_NAME . '.index')->with('success', 'تم إضافة العميل والاشتراك بنجاح');
        } catch (\Throwable $e) {
            Log::error($e);
            return redirect()->back()->withInput()->with('error', 'حدث خطأ أثناء إضافة العميل');
        }
    }

    public function access(Tenant $tenant): RedirectResponse
    {
        tenancy()->initialize($tenant);

        try {
            $user = User::role('Admin')->orderBy('id')->first() ?? User::orderBy('id')->first();

            abort_if(!$user, 404, 'لا يوجد مستخدم إداري لهذا العميل.');
        } finally {
            tenancy()->end();
        }

        $handoff = Crypt::encryptString(json_encode([
            'tenant_id' => $tenant->id,
            'user_id' => $user->getKey(),
            'expires_at' => now()->addMinute()->timestamp,
        ], JSON_THROW_ON_ERROR));

        return redirect()->to(url($tenant->id . '/impersonate?token=' . urlencode($handoff)));
    }


    public function edit(Subscription $subscription)
    {
        // return $subscription;
        $plan = $subscription->plan;
        $plans = Plan::get();
        $allStatus  = SubscriptionStatus::options();
        return view(self::VIEW_PATH . '.edit', compact('subscription', 'allStatus', 'plan', 'plans'));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'tenant_id'         => 'required|exists:tenants,id',
            'plan_id'           => 'required|exists:plans,id',
            'price'             => 'required|numeric|min:0',
            'status'            => 'required|string',
            'total_users'       => 'required|integer|min:1',
            'licenses_quota'    => 'nullable|integer|min:0',
            'starts_at'         => 'required|date',
            'next_payment_date' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $tenant = Tenant::where('id', $validated['tenant_id'])->firstOrFail();

            $subscription->update([
                'plan_id'           => $validated['plan_id'],
                'price'             => $validated['price'],
                'status'            => $validated['status'],
                'total_users'       => $validated['total_users'],
                'used_users'        => $validated['licenses_quota'] ?? 0,
                'starts_at'         => $validated['starts_at'],
                'next_payment_date' => $validated['next_payment_date'],
                'auto_renew'        => $request->has('auto_renew'),
            ]);

            if ($subscription->wasChanged('plan_id')) {
                $newPlan = Plan::findOrFail($validated['plan_id']);
                $tenant->branches_limit = $newPlan->unlimited_branches ? null : $newPlan->branches_limit;
                $tenant->save();
            }

            DB::commit();

            return redirect()
                ->route(self::ROUTE_NAME . '.index')
                ->with('success', 'تم تحديث بيانات الاشتراك بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('فشل تحديث الاشتراك: ' . $e->getMessage(), [
                'subscription_id' => $subscription->id,
                'request_data'    => $request->all()
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث الاشتراك: ' . $e->getMessage());
        }
    }

    public function destroy(Subscription $subscription)
    {
        try {
            $subscription->delete();

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم نقل الاشتراك إلى الأرشيف بنجاح'
                ]);
            }

            return redirect()
                ->route(self::ROUTE_NAME . '.index')
                ->with('success', 'تم نقل الاشتراك إلى الأرشيف بنجاح');
        } catch (\Exception $e) {
            Log::error('فشل حذف الاشتراك: ' . $e->getMessage());

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف الاشتراك: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء حذف الاشتراك: ' . $e->getMessage());
        }
    }
}
