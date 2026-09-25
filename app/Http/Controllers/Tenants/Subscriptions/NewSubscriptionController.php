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

class NewSubscriptionController extends Controller
{
    private $page = "tenants.subscription.new_subscription.";

    public function __construct(
        private MoyasarService                  $moyasarService,
        private MoyasarCallbackHandler          $moyasarCallbackHandler,
        private SessionManagerService           $sessionManagerService,
        private CouponService                   $couponService
    ) {

        // اذا كان هناك اشتراك
        $this->middleware(function (Request $request, Closure $next) {

            $hasSubscription = tenant()->lastSubscription()->exists();

            if ($hasSubscription) {
                return redirect()->to(tenant_route_name('dashboard'));
            }

            return $next($request);
        });
    }


    // لا يوجد اشتراك
    public function index()
    {
        $plans = Tenancy::central(fn() => Plan::where('is_active', true)->get());
        return view($this->page . '.index', compact('plans'));
    }


    public function checkout(Plan $plan)
    {
        $this->sessionManagerService->storeSubscriptionData($plan);

        $taxRate         = 0.15;
        $taxAmount       = round($plan->price_after_discount * $taxRate, 2);
        $priceWithoutTax = round($plan->price_after_discount - $taxAmount, 2);

        return view($this->page . 'checkout', compact('plan', 'priceWithoutTax', 'taxAmount'));
    }


    public function checkoutStore(PaymentRequest $request)
    {
        try {
            $sessionData = $this->sessionManagerService->validateSession('subscription_data');

            if (!$sessionData) {
                return $this->redirectWithError('subscribe.index', 'انتهت صلاحية الجلسة، يرجى المحاولة مرة أخرى');
            }

            $plan       = $this->getCentralPlan($sessionData['plan_id']);
            $couponData = $this->couponService->processCoupon($request->input('coupon_code'), $plan->price_after_discount);

            if (isset($couponData['error'])) {
                return $this->redirectWithError('subscribe.index', $couponData['error']);
            }

            $this->saveCouponData($plan->price_after_discount, $couponData);

            //   الدفع
            $paymentUrl = $this->initiatePayment($request, $sessionData, $couponData['final_total']);

            if ($paymentUrl instanceof \Illuminate\Http\RedirectResponse) {
                return $paymentUrl;
            }

            return redirect($paymentUrl);
            //
        } catch (\Throwable $e) {
            report($e);
            return $this->redirectWithError('subscribe.index', 'حدث خطأ أثناء المعالجة ');
        }
    }


    public function handleNewSubscriptionCallback(Request $request)
    {
        try {
            //
            $result = $this->moyasarCallbackHandler->handleSubscriptionToTenant($request);

            if ($result['success']) {

                // تطبيق الكوبون بعد نجاح الدفع
                $this->applyCouponAfterPayment();

                // مسح بيانات الجلسة
                $this->sessionManagerService->clear();

                return redirect()->to(tenant_route_name('subscriptions.index'))->with('success', $result['message'] ?? 'تم الاشتراك بنجاح');
            }

            return redirect()->route('site.index')->with('error', $result['message']);
        } catch (\Throwable $e) {
            //
            report($e);
            return redirect()->route('site.index')->with('error', 'حدث خطأ أثناء معالجة نتيجة الدفع');
        }
    }

    /*
    |============================================================================
    |============================================================================
    |                       Project Methods
    |============================================================================
    |============================================================================
    */
    private function saveCouponData($total, array $couponData): void
    {
        $this->sessionManagerService->update('subscription_data', [
            'original_total'  => $total,
            'discount_amount' => $couponData['discount_amount'],
            'final_total'     => $couponData['final_total'],

            'coupon_id'       => $couponData['coupon_id'],
            'coupon_code'     => $couponData['coupon_code'],
            'coupon_value'    => $couponData['coupon_value'],
            'coupon_type'     => $couponData['coupon_type'],
        ]);
    }

    // الدفع
    private function initiatePayment(Request $request, array $sessionData, $final_total)
    {
        $plan = Plan::findOrfail($sessionData['plan_id']);

        $request->merge([
            'amount'            => $final_total,
            'description'       => 'اشتراك في ' . $plan->name,
        ]);

        // دفع عبر مسير
        $payment = $this->moyasarService->createNewSubscriptionToTenant($request);

        if (isset($payment['source']['transaction_url'])) {

            return $payment['source']['transaction_url'];
        }

        return $this->redirectWithError('subscribe.index', 'حدث خطأ أثناء إنشاء عملية الدفع.');
    }


    private function applyCouponAfterPayment(): void
    {
        $pendingPayment = session('subscription_data');

        if (!$pendingPayment || empty($pendingPayment['coupon_code'])) {
            return;
        }

        $coupon = $this->couponService->findActiveCoupon($pendingPayment['coupon_code']);

        if (!$coupon) {
            return;
        }

        $this->couponService->applyCoupon($coupon);
    }


    private function getCentralPlan(int $planId): Plan
    {
        return Tenancy::central(fn() => Plan::findOrFail($planId));
    }


    private function redirectWithError(string $route, string $message)
    {
        return redirect()->to(tenant_route_name($route))->with('error', $message);
    }
}
