<?php

namespace App\Http\Controllers\Tenants\Subscriptions;

use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Exceptions\Payment\PaymentInitiationException;
use App\Exceptions\Payment\PaymentVerificationException;
use App\Exceptions\Subscription\SubscriptionCreationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Payment\PaymentRequest;
use App\Services\shared\Bills\BillService;
use App\Services\shared\Coupons\CouponService;
use App\Services\shared\Payment\Moyasar\MoyasarCallbackHandler;
use App\Services\shared\Payment\Moyasar\MoyasarFormConfiguration;
use App\Services\shared\Session\SessionManagerService;
use App\Services\shared\Subscription\SubscriptionService;
use App\Services\Tenants\Shared\CentralBillingGateway;
use Closure;
use Illuminate\Http\Request;

class NewSubscriptionController extends Controller
{
    private $page = 'tenant.subscriptions.new_subscription.';

    public function __construct(
        private MoyasarCallbackHandler $moyasarCallbackHandler,
        private MoyasarFormConfiguration $moyasarFormConfiguration,
        private SessionManagerService $sessionManagerService,
        private CouponService $couponService,
        private BillService $billService,
        private SubscriptionService $subscriptionService,
        private CentralBillingGateway $billingGateway,
    ) {

        // اذا كان هناك اشتراك
        $this->middleware(function (Request $request, Closure $next) {

            $hasSubscription = $this->billingGateway->currentTenantHasSubscription();

            if ($hasSubscription) {
                return redirect()->to(tenant_route_name('dashboard'));
            }

            return $next($request);
        });
    }

    // لا يوجد اشتراك
    public function index()
    {
        $plans = $this->billingGateway->activePlans();

        return view($this->page.'.index', compact('plans'));
    }

    public function checkout(int|string $plan)
    {
        $plan = $this->billingGateway->plan($plan);
        $this->sessionManagerService->storeSubscriptionData($plan);

        $taxRate = 0.15;
        $taxAmount = round($plan->price_after_discount * $taxRate, 2);
        $priceWithoutTax = round($plan->price_after_discount - $taxAmount, 2);

        return view($this->page.'checkout', compact('plan', 'priceWithoutTax', 'taxAmount'));
    }

    public function checkoutStore(PaymentRequest $request)
    {
        try {
            $sessionData = $this->sessionManagerService->validateSession('subscription_data');

            if (! $sessionData) {
                return $this->redirectWithError('subscribe.index', 'انتهت صلاحية الجلسة، يرجى المحاولة مرة أخرى');
            }

            $plan = $this->getCentralPlan($sessionData['plan_id']);
            $couponData = $this->couponService->processCoupon($request->input('coupon_code'), $plan->price_after_discount);

            if (isset($couponData['error'])) {
                return $this->redirectWithError('subscribe.index', $couponData['error']);
            }

            $this->moyasarFormConfiguration->getPublishableKey();
            $this->moyasarFormConfiguration->amountInHalala((float) $couponData['final_total']);

            $pendingBill = $this->createPendingBill($plan, $couponData);

            return $this->showMoyasarCheckout($pendingBill, $plan);
        } catch (PaymentInitiationException $e) {
            return $this->redirectWithError('subscribe.index', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return $this->redirectWithError('subscribe.index', 'حدث خطأ أثناء المعالجة ');
        }
    }

    public function handleNewSubscriptionCallback(Request $request)
    {
        try {
            $result = $this->moyasarCallbackHandler->verify($request);
            $this->subscriptionService->completeExistingTenantSubscription($result['bill'], $result['payment']);
            $this->sessionManagerService->clear('subscription_data');

            return redirect()->to(tenant_route_name('subscriptions.index'))->with('success', 'تم الاشتراك بنجاح.');
        } catch (PaymentVerificationException|SubscriptionCreationException $e) {
            return redirect()->to(tenant_route_name('subscribe.index'))->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->to(tenant_route_name('subscribe.index'))->with('error', 'حدث خطأ أثناء معالجة نتيجة الدفع');
        }
    }

    /*
    |============================================================================
    |============================================================================
    |                       Project Methods
    |============================================================================
    |============================================================================
    */
    private function createPendingBill($plan, array $couponData)
    {
        $tenant = tenant();
        $user = auth('tenant')->user();

        return $this->billService->createPendingBill(
            amount: $couponData['final_total'],
            paymentMethod: PaymentMethod::ONLINE,
            originalAmount: $plan->price_after_discount,
            type: BillType::NEW_SUBSCRIPTION,
            couponData: $couponData,
            metadata: [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'billing_name' => $user->name ?? $tenant->primary_contact_name,
                'billing_email' => $user->email ?? $tenant->primary_contact_email,
                'billing_phone' => $user->phone ?? $tenant->primary_contact_phone,
                'billing_company_name' => $tenant->company_name,
                'billing_commercial_number' => $tenant->commercial_number,
            ],
        );
    }

    private function showMoyasarCheckout($bill, $plan)
    {
        return view('tenant.payment.moyasar-checkout', [
            'amountInHalala' => $this->moyasarFormConfiguration->amountInHalala((float) $bill->amount),
            'backUrl' => tenant_route_name('subscribe.checkout', ['plan' => $plan->id]),
            'bill' => $bill,
            'callbackUrl' => tenant_route_name('subscribe.callback', ['bill' => $bill->bill_number]),
            'description' => 'اشتراك في '.$plan->name,
            'mode' => $this->moyasarFormConfiguration->getMode(),
            'publishableKey' => $this->moyasarFormConfiguration->getPublishableKey(),
        ]);
    }

    private function getCentralPlan(int $planId)
    {
        return $this->billingGateway->plan($planId);
    }

    private function redirectWithError(string $route, string $message)
    {
        return redirect()->to(tenant_route_name($route))->with('error', $message);
    }
}
