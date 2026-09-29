<?php

namespace App\Http\Controllers\Tenants\Subscriptions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Payment\PaymentRequest;
use App\Models\Admin\Plans\Plan;
use App\Services\shared\Coupons\CouponService;
use App\Services\shared\Payment\Moyasar\MoyasarCallbackHandler;
use App\Services\shared\Payment\Moyasar\MoyasarService;
use App\Services\shared\Session\SessionManagerService;
use App\Services\shared\Subscription\SubscriptionFormatter;
use App\Services\shared\Subscription\SubscriptionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;

class __SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionManager             $subscriptionManager,
        private SubscriptionFormatter           $subscriptionFormatter,
        // private MoyasarService                  $moyasarService,
        private MoyasarCallbackHandler          $moyasarCallbackHandler,
        private SessionManagerService           $sessionManagerService,
        private CouponService                   $couponService
    ) {

        $this->middleware('can:subscriptions.view')->only(['index', 'store']);
        $this->middleware('can:subscriptions.renew')->only(['renew', 'checkout_renew', 'callback_renew_subscription_payment']);
        $this->middleware('can:subscriptions.add_licenses')->only(['upgrade', 'checkout_upgrade', 'upgrade_callback']);
    }


    public function index()
    {
        $tenant        = tenant();
        $subscription  = $this->subscriptionManager->getTenantSubscription($tenant->id);
        $formatted     = $this->subscriptionFormatter->format($subscription);
        $plans         = Plan::where('is_active', true)->get();

        //
        return view('tenants.subscription.index', [
            'subscription_data'         => $subscription,
            'subscription_formatted'    => $formatted,
            'plans'                     => $plans
        ]);
    }

    /*
    |============================================================================
    |============================================================================
    |                            Renew
    |============================================================================
    |============================================================================
    */
    public function renew()
    {
        try {
            $plan            = $this->getCentralPlan();
            $taxRate         = 0.15;
            $taxAmount       = round($plan->yearly_renewal_price * $taxRate, 2);
            $priceWithoutTax = round($plan->yearly_renewal_price - $taxAmount, 2);

            return view('tenants.subscription.renew.checkout', compact('plan', 'priceWithoutTax', 'taxAmount'));
            //
        } catch (\Exception $e) {
            report($e);
            return redirect()->to(tenant_route_name('subscriptions.index'))->with('error', 'حدث خطأ أثناء تحميل صفحة التجديد');
        }
    }


    public function checkout_renew(PaymentRequest $request)
    {
        try {
            $plan       = $this->getCentralPlan();
            $couponData = $this->couponService->processCoupon($request->input('coupon_code'), $plan->yearly_renewal_price);

            if (isset($couponData['error'])) {
                return redirect()->to(tenant_route_name('subscriptions.index'))->with('error',  $couponData['error']);
            }

            $this->saveCouponData('subscription_renew_data', $plan->yearly_renewal_price, $couponData);

            return $this->initiatePayment($request, $couponData, "تجديد الاشتراك في الباقة ", 'createRenewSubscriptionPayment');
            //
        } catch (\Throwable $e) {
            report($e);
            return redirect()->to(tenant_route_name('subscriptions.index'))->with('error', 'حدث خطأ أثناء معالجة التجديد');
        }
    }


    public function callback_renew_subscription_payment(Request $request)
    {
        return $this->handleCallback(fn() => $this->moyasarCallbackHandler->handleRenewSubscription($request), 'تم تجديد الاشتراك بنجاح', 'subscription_renew_data');
    }


    /*
    |============================================================================
    |============================================================================
    |                           Upgrade
    |============================================================================
    |============================================================================
    */
    public function upgrade(Request $request)
    {
        if ($request->isMethod('get')) {
            return redirect()->to(tenant_route_name('subscriptions.index'))->with('error', 'يرجى تحديد عدد المستخدم المطلوب شراءه');
        }
        try {
            $tenant             = tenant();
            $plan               = $this->getCentralPlan();
            $new_licenses_count = (int) $request->input('new_licenses_count', 0);
            $subscription       = $this->subscriptionManager->getTenantSubscription($tenant->id);

            // حفظ البيانات في Session
            $this->sessionManagerService->storeNewLicensesData($new_licenses_count, $subscription->total_users, $plan->extra_user_license_price);

            $taxRate         = 0.15;
            $total           =  $plan->extra_user_license_price * $new_licenses_count;
            $taxAmount       = round($total * $taxRate, 2);
            $priceWithoutTax = round($total - $taxAmount, 2);

            return view('tenants.subscription.upgrade.checkout', compact('new_licenses_count', 'plan', 'total', 'priceWithoutTax', 'taxAmount'));
        } catch (\Exception $e) {
            report($e);
            return redirect()->to(tenant_route_name('subscriptions.index'))->with('error', 'حدث خطأ أثناء تحميل صفحة شراء تراخيص اضافية ');
        }
    }


    public function checkout_upgrade(PaymentRequest $request)
    {
        try {
            $sessionData = $this->sessionManagerService->validateSession("new_licenses_data");

            if (!$sessionData) {
                return redirect()->to(tenant_route_name('subscriptions.index'))->with('error', 'انتهت صلاحية الجلسة، يرجى المحاولة مرة أخرى');
            }

            $plan   =  $this->getCentralPlan();
            $total  =  $plan->extra_user_license_price * $sessionData['new_user_license'];

            $couponData = $this->couponService->processCoupon($request->input('coupon_code'), $total);

            if (isset($couponData['error'])) {
                return redirect()->to(tenant_route_name('subscriptions.index'))->with('error',  $couponData['error']);
            }

            $this->saveCouponData('new_licenses_data', $total, $couponData);

            return $this->initiatePayment($request, $couponData, "شراء تراخيص", 'createUpgradePayment');
            //
        } catch (\Throwable $e) {
            report($e);
            return redirect()->to(tenant_route_name('subscriptions.index'))->with('error', 'حدث خطأ أثناء معالجة شراء التراخيص الاضافية');
        }
    }


    public function upgrade_callback(Request $request)
    {
        return $this->handleCallback(fn() => $this->moyasarCallbackHandler->handleUpgradeSubscription($request), 'تم شراء التراخيص  بنجاح', 'new_licenses_data');
    }


    /*
    |============================================================================
    |============================================================================
    |                       Project Methods
    |============================================================================
    |============================================================================
    */
    private function saveCouponData(string $sessionKey, $originalTotal, array $couponData): void
    {
        $this->sessionManagerService->update($sessionKey, [
            'original_total'  => $originalTotal, //  قبل الخصم
            'discount_amount' => $couponData['discount_amount'],
            'final_total'     => $couponData['final_total'], // بعد الخصم - السعر الذي سيدفع

            'coupon_id'       => $couponData['coupon_id'],
            'coupon_code'     => $couponData['coupon_code'],
            'coupon_value'    => $couponData['coupon_value'],
            'coupon_type'     => $couponData['coupon_type'],
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


            return redirect($payment['source']['transaction_url']);
        }

        return redirect()->to(tenant_route_name('subscriptions.index'))->with('error', 'حدث خطأ أثناء إنشاء عملية الدفع.');
    }

    private function handleCallback(callable $handler, string $successMessage, $sessionKey)
    {
        try {
            $result = $handler();

            if ($result['success']) {

                $this->applyCouponAfterPayment($sessionKey);

                $this->sessionManagerService->clear();

                return redirect()->to(tenant_route_name('subscriptions.index'))->with('success', $result['message'] ?? $successMessage);
            }


            return redirect()->to(tenant_route_name('subscriptions.index'))->with('error',  $result['message']);

            //
        } catch (\Throwable $e) {
            Log::error('❌ خطأ في callback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->to(tenant_route_name('subscriptions.index'))->with('error', 'حدث خطأ أثناء معالجة نتيجة الدفع');
        }
    }

    private function applyCouponAfterPayment($sessionKey): void
    {
        $pendingPayment = session($sessionKey);

        if (!$pendingPayment || empty($pendingPayment['coupon_code'])) {
            return;
        }

        $coupon = $this->couponService->findActiveCoupon($pendingPayment['coupon_code']);

        if (!$coupon) {
            return;
        }

        $this->couponService->applyCoupon($coupon);
    }

    private function getCentralPlan()
    {
        $tenant = tenant();
        $plan   = Tenancy::central(fn() => Plan::find($tenant->lastSubscription->plan?->id));

        if (!$plan) {
            return redirect()->to(tenant_route_name('subscriptions.index'))->with('error', 'لا يوجد اشتراك .');
        }

        return $plan;
    }
}