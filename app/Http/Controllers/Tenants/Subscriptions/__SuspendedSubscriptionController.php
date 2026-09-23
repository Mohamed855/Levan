<?php

namespace App\Http\Controllers\Tenants\Subscriptions;

use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Payment\PaymentRequest;
use App\Models\Admin\Plans\Plan;
use App\Services\shared\Coupons\CouponService;
use App\Services\shared\Payment\Moyasar\MoyasarCallbackHandler;
use App\Services\shared\Payment\Moyasar\MoyasarService;
use App\Services\shared\Session\SessionManagerService;
use App\Services\shared\Subscription\SubscriptionCalculatorService;
use App\Services\shared\Subscription\SubscriptionManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;

class SuspendedSubscriptionController extends Controller
{
    private $page = "tenants.subscription.suspended_subscription.";

    public function __construct(
        private SubscriptionCalculatorService   $subscriptionCalculatorService,
        private MoyasarService                  $moyasarService,
        private MoyasarCallbackHandler          $moyasarCallbackHandler,
        private SessionManagerService           $sessionManagerService,
        private CouponService                   $couponService
    ) {

        // اذا كان هناك اشتراك
        $this->middleware(function (Request $request, Closure $next) {

            $hasSubscription = tenant()->subscriptions()->exists();

            if ($hasSubscription) {
                return redirect()->to(tenant_route_name('dashboard'));
            }

            return $next($request);
        });
    }


    // لا يوجد اشتراك
    public function index()
    {
        $plans = Tenancy::central(fn() => Plan::all());
        return view($this->page . '.index', compact('plans'));
    }



    public function showCheckout(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'users'   => 'required|integer|min:0|max:1000',
            'billing' => 'required|in:monthly,yearly',
        ]);

        $users   = (int) $validated['users'];
        $billing = $validated['billing'];

        // حساب الأسعار
        $calculation = $this->subscriptionCalculatorService->calculateTotal($plan, $users, $billing);

        // حفظ البيانات في الجلسة
        $this->sessionManagerService->storeSubscriptionData([
            'plan'      => $plan,
            'users'     => $users,
            'billing'   => $billing,
            ...$calculation
        ]);


        return view($this->page . 'checkout', [
            'users'      => $users,
            'billing'    => $billing,
            ...$calculation
        ]);
    }

    public function processPayment(PaymentRequest $request)
    {
        try {
            $sessionData = $this->sessionManagerService->validateSession('subscription_data');

            if (!$sessionData) {
                return $this->redirectWithError('subscribe.index', 'انتهت صلاحية الجلسة، يرجى المحاولة مرة أخرى');
            }

            $plan           = $this->getCentralPlan($sessionData['plan_id']);
            $recalculation  = $this->recalculateAndValidate($plan, $sessionData, 'subscription_data');

            if (!$recalculation) {
                return $this->redirectWithError('subscribe.index', 'حدث تغيير في المبلغ، يرجى إعادة المحاولة');
            }

            $couponData = $this->processCoupon($request->input('coupon_code'), $recalculation);

            if (isset($couponData['error'])) {
                return $this->redirectWithError('subscribe.index', $couponData['error']);
            }

            $this->saveCouponData('subscription_data', $recalculation, $couponData);

            return $this->initiatePayment($request, $couponData, " الاشتراك في الباقة ", 'createNewSubscriptionToTenantPayment');
            //
        } catch (\Throwable $e) {

            return $this->handleError($e, 'حدث خطأ أثناء المعالجة ');
        }
    }

    public function handleNewSubscriptionCallback(Request $request)
    {
        $this->handleCallback(fn() => $this->moyasarCallbackHandler->handleSubscriptionToTenant($request), 'تم  الاشتراك بنجاح');
    }



    /*
    |============================================================================
    |============================================================================
    |                       Project Methods
    |============================================================================
    |============================================================================
    */
    private function recalculateAndValidate(Plan $plan, array $sessionData, string $sessionKey): ?array
    {
        $recalculation = $this->subscriptionCalculatorService->calculateTotal(
            $plan,
            $sessionData['users'],
            $sessionData['billing']
        );

        if (!$this->subscriptionCalculatorService->validatePricing($recalculation, $sessionData)) {
            $this->sessionManagerService->clear($sessionKey);
            return null;
        }

        return $recalculation;
    }

    private function processCoupon(?string $couponCode, array $recalculation): array
    {
        $couponData = $this->couponService->processCoupon($couponCode, $recalculation);

        if (isset($couponData['error'])) {
            Log::info('⚠️ فشل تطبيق الكوبون', ['error' => $couponData['error']]);
        }

        return $couponData;
    }

    private function saveCouponData(string $sessionKey, array $recalculation, array $couponData): void
    {
        $this->sessionManagerService->update($sessionKey, [
            'original_total'  => $recalculation['total'],
            'discount_amount' => $couponData['discount_amount'],
            'final_total'     => $couponData['final_total'],
            'coupon_id'       => $couponData['coupon_id'],
            'coupon_code'     => $couponData['coupon_code'],
            'coupon_type'     => $couponData['coupon_type'] ?? null,
            'coupon_value'    => $couponData['coupon_value'] ?? null,
        ]);
    }

    private function initiatePayment(Request $request, array $couponData, $description, string $paymentMethod)
    {
        $request->merge([
            'amount'            => $couponData['final_total'],
            'description'       => $description ?? "عملية دفع",
        ]);

        $payment = $this->moyasarService->$paymentMethod($request);

        if (isset($payment['source']['transaction_url'])) {
            // حفظ معلومات الدفع في Session
            $this->sessionManagerService->storePendingPaymentData($couponData);

            return redirect($payment['source']['transaction_url']);
        }

        return redirect()->to(tenant_route_name('subscribe.index'))->with('error', 'حدث خطأ أثناء إنشاء عملية الدفع.');
    }

    private function handleCallback(callable $handler, string $successMessage)
    {
        try {
            $result = $handler();

            if ($result['success']) {

                $this->applyCouponAfterPayment();

                $this->sessionManagerService->clear();

                Log::error(tenant_route_name('subscriptions.index'));
                // بعد النجاح
                return redirect()->to(tenant_route_name('subscriptions.index'))->with('success', $result['message'] ?? 'تم الاشتراك بنجاح');
            }

            return $this->redirectWithError('subscribe.index', $result['message']);
        } catch (\Throwable $e) {
            Log::error('❌ خطأ في callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->redirectWithError('subscribe.index', 'حدث خطأ أثناء معالجة نتيجة الدفع');
        }
    }

    private function applyCouponAfterPayment(): void
    {
        $pendingPayment = session('pending_payment');

        if (!$pendingPayment || empty($pendingPayment['coupon_code'])) {
            return;
        }

        $coupon = $this->couponService->findActiveCoupon($pendingPayment['coupon_code']);

        if (!$coupon) {
            Log::warning('⚠️ محاولة تطبيق كوبون غير موجود', [
                'coupon_code' => $pendingPayment['coupon_code']
            ]);
            return;
        }

        $applied = $this->couponService->applyCoupon($coupon);

        if ($applied) {
            Log::info('✅ تم تطبيق الكوبون بنجاح', [
                'coupon_code' => $coupon->code,
                'coupon_id'   => $coupon->id
            ]);
        }
    }

    private function getCentralPlan(int $planId): Plan
    {
        return Tenancy::central(fn() => Plan::findOrFail($planId));
    }

    private function handleError(\Throwable $e, string $message)
    {
        Log::error('🚨 ' . $message, [
            'error' => $e->getMessage(),
            'line'  => $e->getLine(),
            'file'  => $e->getFile(),
            'trace' => $e->getTraceAsString(),
        ]);

        return $this->redirectWithError('subscribe.index', $message);
    }

    private function redirectWithError(string $route, string $message)
    {
        return redirect()->to(tenant_route_name($route))->with('error', $message);
    }
}
