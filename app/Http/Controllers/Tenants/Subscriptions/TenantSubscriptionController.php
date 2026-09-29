<?php

namespace App\Http\Controllers\Tenants\Subscriptions;

use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Exceptions\Payment\PaymentInitiationException;
use App\Exceptions\Payment\PaymentVerificationException;
use App\Exceptions\Subscription\SubscriptionCreationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Payment\PaymentRequest;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Plans\Plan;
use App\Services\shared\Bills\BillService;
use App\Services\shared\Coupons\CouponService;
use App\Services\shared\Payment\Moyasar\MoyasarCallbackHandler;
use App\Services\shared\Payment\PaymentData;
use App\Services\shared\Payment\PaymentService;
use App\Services\shared\Subscription\SubscriptionFormatter;
use App\Services\shared\Subscription\SubscriptionManager;
use App\Services\shared\Subscription\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;

class TenantSubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionManager     $subscriptionManager,
        private SubscriptionFormatter   $subscriptionFormatter,
        private MoyasarCallbackHandler  $moyasarCallbackHandler,
        private CouponService           $couponService,
        private BillService             $billService,
        private PaymentService          $paymentService,
        private SubscriptionService     $subscriptionService,
    ) {
        $this->middleware('can:subscriptions.view')->only(['index']);
        $this->middleware('can:subscriptions.renew')->only(['renewCheckout', 'processRenew', 'renewCallback']);
        $this->middleware('can:subscriptions.add_licenses')->only(['licenseCheckout', 'processLicense', 'licenseCallback']);
    }

    public function index()
    {
        $tenant        = tenant();
        $subscription  = $this->subscriptionManager->getTenantSubscription($tenant->id);
        $formatted     = $this->subscriptionFormatter->format($subscription);
        $plans         = Plan::where('is_active', true)->get();

        //
        return view('tenants.subscription.index', [
            'subscription_data'      => $subscription,
            'subscription_formatted' => $formatted,
            'plans'                  => $plans
        ]);
    }

    /*
    |============================================================================
    |============================================================================
    |                            Renew
    |============================================================================
    |============================================================================
    */
    public function renewCheckout()
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

    public function processRenew(PaymentRequest $request)
    {
        try {
            $plan = $this->getCentralPlan();

            $couponData = $this->couponService->processCoupon($request->input('coupon_code'), $plan->yearly_renewal_price);

            if (isset($couponData['error'])) {
                return redirect()->back()->with('error', $couponData['error'])->withInput();
            }

            $pendingBill = $this->createPendingBill($plan, $couponData, BillType::RENEWAL, $plan->yearly_renewal_price);
            $url         = $this->handlePaymentInitiation($request, $couponData, $pendingBill, 'تجديد الإشتراك في ' . $plan->name, 'renewSubscriptionPayment');

            return redirect($url);
            //
        } catch (PaymentInitiationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء معالجة التجديد');
        }
    }

    //  Callback
    public function renewCallback(Request $request)
    {
        try {
            $result = $this->moyasarCallbackHandler->verify($request);
            $this->subscriptionService->completeRenewal($result['bill'], $result['payment']);

            return redirect()->to(tenant_route_name('subscriptions.index'))->with('success', 'تم تجديد الاشتراك بنجاح');
            //
        } catch (PaymentVerificationException $e) {
            //
            return redirect()->route('site.index')->with('error', $e->getMessage());
        } catch (SubscriptionCreationException $e) {
            //
            return redirect()->route('site.index')->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            //
            report($e);
            return redirect()->route('site.index')->with('error', 'حدث خطأ أثناء معالجة نتيجة الدفع');
        }
    }

    /*
    |============================================================================
    |============================================================================
    |                           Upgrade
    |============================================================================
    |============================================================================
    */
    public function licenseCheckout(Request $request)
    {
        try {
            $newLicensesCount = (int) $request->input('new_licenses_count', 0);

            if ($newLicensesCount <= 0) {
                return redirect()->to(tenant_route_name('subscriptions.index'))->with('error', 'يرجى تحديد عدد التراخيص المطلوبة');
            }

            $plan            = $this->getCentralPlan();
            $total           = $plan->extra_user_license_price * $newLicensesCount;
            $taxRate         = 0.15;
            $taxAmount       = round($total * $taxRate, 2);
            $priceWithoutTax = round($total - $taxAmount, 2);

            return view('tenants.subscription.upgrade.checkout', compact('newLicensesCount', 'plan', 'total', 'priceWithoutTax', 'taxAmount'));
            //
        } catch (\Exception $e) {
            report($e);
            return redirect()->to(tenant_route_name('subscriptions.index'))->with('error', 'حدث خطأ أثناء تحميل صفحة شراء تراخيص اضافية ');
        }
    }

    public function processLicense(PaymentRequest $request)
    {
        try {
            $newLicensesCount = (int) $request->input('new_licenses_count', 0);

            if ($newLicensesCount <= 0) {
                return redirect()->back()->with('error', 'عدد التراخيص غير صحيح');
            }

            $plan   =  $this->getCentralPlan();
            $total  = $plan->extra_user_license_price * $newLicensesCount;

            $couponData = $this->couponService->processCoupon($request->input('coupon_code'), $total);

            if (isset($couponData['error'])) {
                return redirect()->back()->with('error', $couponData['error'])->withInput();
            }

            $pendingBill = $this->createPendingBill(
                $plan,
                $couponData,
                BillType::LICENSE_PURCHASE,
                $total,
                [
                    'new_licenses_count' => $newLicensesCount,
                    'license_unit_price' => $plan->extra_user_license_price,
                ]
            );
            $url         = $this->handlePaymentInitiation($request, $couponData, $pendingBill, "شراء تراخيص إضافية ", 'licensePayment');

            return redirect($url);
            //
        } catch (PaymentInitiationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء معالجة شراء التراخيص الإضافية ');
        }
    }

    public function licenseCallback(Request $request)
    {
        try {
            $result = $this->moyasarCallbackHandler->verify($request);
            $this->subscriptionService->completeLicensePurchase($result['bill'], $result['payment']);

            return redirect()->to(tenant_route_name('subscriptions.index'))->with('success', 'تم شراء التراخيص الإضافية بنجاح');
            //
        } catch (PaymentVerificationException $e) {
            //
            return redirect()->to(tenant_route_name('subscriptions.index'))->with('error', $e->getMessage());
        } catch (SubscriptionCreationException $e) {
            //
            return redirect()->to(tenant_route_name('subscriptions.index'))->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            //
            report($e);
            return redirect()->to(tenant_route_name('subscriptions.index'))->with('error', 'حدث خطأ أثناء معالجة نتيجة الدفع');
        }
    }

    /*
    |============================================================================
    |============================================================================
    |                       Project Methods
    |============================================================================
    |============================================================================
    */
    private function createPendingBill(Plan $plan, array $couponData, BillType $type, float $originalAmount, array $extraMetadata = []): Bill
    {
        return $this->billService->createPendingBill(
            amount: $couponData['final_total'],
            paymentMethod: PaymentMethod::ONLINE,
            originalAmount: $originalAmount,
            type: $type,
            couponData: $couponData,
            metadata: array_merge([
                'tenant_id'                 => tenant()->id,
                'billing_name'              => auth()->user()->name,
                'billing_email'             => auth()->user()->email,
                'billing_phone'             => auth()->user()->phone ?? null,
                'billing_company_name'      => tenant()->company_name,
                'billing_commercial_number' => tenant()->commercial_number,
            ], $extraMetadata),
        );
    }

    private function handlePaymentInitiation(Request $request, array $couponData, Bill $bill, $description, $functionName)
    {
        return $this->paymentService->initiatePayment(
            PaymentData::fromRequest($request, $couponData['final_total'], $description),
            $bill,
            $functionName
        );
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
