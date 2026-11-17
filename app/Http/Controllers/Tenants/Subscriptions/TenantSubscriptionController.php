<?php

namespace App\Http\Controllers\Tenants\Subscriptions;

use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Exceptions\Payment\PaymentInitiationException;
use App\Exceptions\Payment\PaymentVerificationException;
use App\Exceptions\Subscription\SubscriptionCreationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Payment\PaymentRequest;
use App\Http\Requests\Tenants\Subscriptions\LicenseCheckoutRequest;
use App\Http\Requests\Tenants\Subscriptions\ProcessLicensePaymentRequest;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Services\Shared\Bills\BillService;
use App\Services\Shared\Coupons\CouponService;
use App\Services\Shared\Payment\Moyasar\MoyasarCallbackHandler;
use App\Services\Shared\Payment\Moyasar\MoyasarFormConfiguration;
use App\Services\Shared\Subscription\SubscriptionFormatter;
use App\Services\Shared\Subscription\SubscriptionManager;
use App\Services\Shared\Subscription\SubscriptionService;
use App\Services\Tenants\Shared\CentralBillingGateway;
use App\Services\Tenants\Shared\CentralTrialAccessGateway;
use Illuminate\Http\Request;
use Stancl\Tenancy\Facades\Tenancy;

class TenantSubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionManager $subscriptionManager,
        private SubscriptionFormatter $subscriptionFormatter,
        private MoyasarCallbackHandler $moyasarCallbackHandler,
        private MoyasarFormConfiguration $moyasarFormConfiguration,
        private CouponService $couponService,
        private BillService $billService,
        private SubscriptionService $subscriptionService,
        private CentralBillingGateway $billingGateway,
    ) {
        $this->middleware('can:subscriptions.view')->only(['index']);
        $this->middleware('can:subscriptions.renew')->only(['renewCheckout', 'processRenew', 'renewCallback']);
        $this->middleware('can:subscriptions.add_licenses')->only(['licenseCheckout', 'processLicense', 'licenseCallback']);
    }

    public function index()
    {
        $tenant = tenant();
        $subscription = $this->subscriptionManager->getTenantSubscription($tenant->id);
        $formatted = $this->subscriptionFormatter->format($subscription);
        $plans = $this->billingGateway->activePlans();

        $periods = Tenancy::central(function () use ($tenant) {
            return SubscriptionPeriod::query()
                ->whereHas('subscription', fn ($q) => $q->where('tenant_id', $tenant->id))
                ->with(['subscription.plan'])
                ->orderByDesc('period_start')
                ->get();
        });

        return view('tenant.subscriptions.index', [
            'subscription_data' => $subscription,
            'subscription_formatted' => $formatted,
            'plans' => $plans,
            'periods' => $periods,
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
        if (app(CentralTrialAccessGateway::class)->isSingleUserTrial()) {
            return redirect()->to(tenant_route_name('subscriptions.index'))->with('info', 'التجربة بدون باقة. اختر اشتراكًا مدفوعًا للمتابعة بعد انتهاء التجربة.');
        }
        try {
            $plan = $this->getCentralPlan();
            $renewalPrice = $this->getRenewalPrice($plan);
            $taxRate = 0.15;
            $taxAmount = round($renewalPrice * $taxRate, 2);
            $priceWithoutTax = round($renewalPrice - $taxAmount, 2);

            return view('tenant.subscriptions.renew.checkout', compact('plan', 'renewalPrice', 'priceWithoutTax', 'taxAmount'));
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
            $renewalPrice = $this->getRenewalPrice($plan);

            $couponData = $this->couponService->processCoupon($request->input('coupon_code'), $renewalPrice);

            if (isset($couponData['error'])) {
                return redirect()->back()->with('error', $couponData['error'])->withInput();
            }

            $this->moyasarFormConfiguration->getPublishableKey();
            $this->moyasarFormConfiguration->amountInHalala((float) $couponData['final_total']);

            $pendingBill = $this->createPendingBill($plan, $couponData, BillType::RENEWAL, $renewalPrice);

            return $this->showMoyasarCheckout(
                $pendingBill,
                'تجديد الاشتراك في '.$plan->name,
                tenant_route_name('subscriptions.renew.callback', ['bill' => $pendingBill->bill_number]),
                tenant_route_name('subscriptions.renew.checkout'),
            );
        } catch (PaymentInitiationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء معالجة التجديد');
        }
    }

    private function getRenewalPrice(Plan $plan): float
    {
        return (float) ($plan->price_after_discount > 0 ? $plan->price_after_discount : $plan->price);
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
    |                           Upgrade
    |============================================================================
    |============================================================================
    */
    public function licenseCheckout(LicenseCheckoutRequest $request)
    {
        app(CentralTrialAccessGateway::class)->assertUserCreationAllowed();
        try {
            $plan = $this->getCentralPlan();
            if ($plan->hasUnlimitedUsers()) {
                return redirect()->to(tenant_route_name('subscriptions.index'))
                    ->with('error', 'باقات المستخدمين غير المحدودين لا تتطلب شراء تراخيص إضافية.');
            }

            $newLicensesCount = (int) $request->validated('new_licenses_count');

            $total = (float) ($plan->extra_user_license_price ?? 0) * $newLicensesCount;
            $taxRate = 0.15;
            $taxAmount = round($total * $taxRate, 2);
            $priceWithoutTax = round($total - $taxAmount, 2);

            return view('tenant.subscriptions.upgrade.checkout', compact('newLicensesCount', 'plan', 'total', 'priceWithoutTax', 'taxAmount'));
            //
        } catch (\Exception $e) {
            report($e);

            return redirect()->to(tenant_route_name('subscriptions.index'))->with('error', 'حدث خطأ أثناء تحميل صفحة شراء مستخدمين إضافيين');
        }
    }

    public function processLicense(ProcessLicensePaymentRequest $request)
    {
        app(CentralTrialAccessGateway::class)->assertUserCreationAllowed();
        try {
            $plan = $this->getCentralPlan();
            if ($plan->hasUnlimitedUsers()) {
                return redirect()->to(tenant_route_name('subscriptions.index'))
                    ->with('error', 'باقات المستخدمين غير المحدودين لا تتطلب شراء تراخيص إضافية.');
            }

            $validated = $request->validated();
            $newLicensesCount = (int) $validated['new_licenses_count'];

            $total = (float) ($plan->extra_user_license_price ?? 0) * $newLicensesCount;

            $couponData = $this->couponService->processCoupon($validated['coupon_code'] ?? null, $total);

            if (isset($couponData['error'])) {
                return redirect()->back()->with('error', $couponData['error'])->withInput();
            }

            $this->moyasarFormConfiguration->getPublishableKey();
            $this->moyasarFormConfiguration->amountInHalala((float) $couponData['final_total']);

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

            return $this->showMoyasarCheckout(
                $pendingBill,
                'شراء مستخدمين إضافيين',
                tenant_route_name('subscriptions.licenses.callback', ['bill' => $pendingBill->bill_number]),
                tenant_route_name('subscriptions.index'),
            );
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
    private function createPendingBill($plan, array $couponData, BillType $type, float $originalAmount, array $extraMetadata = [])
    {
        return $this->billService->createPendingBill(
            amount: $couponData['final_total'],
            paymentMethod: PaymentMethod::ONLINE,
            originalAmount: $originalAmount,
            type: $type,
            couponData: $couponData,
            metadata: array_merge([
                'tenant_id' => tenant()->id,
                'billing_name' => auth()->user()->name,
                'billing_email' => auth()->user()->email,
                'billing_phone' => auth()->user()->phone ?? null,
                'billing_company_name' => tenant()->company_name,
                'billing_commercial_number' => tenant()->commercial_number,
            ], $extraMetadata),
        );
    }

    private function showMoyasarCheckout($bill, string $description, string $callbackUrl, string $backUrl)
    {
        return view('tenant.payment.moyasar-checkout', [
            'amountInHalala' => $this->moyasarFormConfiguration->amountInHalala((float) $bill->amount),
            'backUrl' => $backUrl,
            'bill' => $bill,
            'callbackUrl' => $callbackUrl,
            'description' => $description,
            'mode' => $this->moyasarFormConfiguration->getMode(),
            'publishableKey' => $this->moyasarFormConfiguration->getPublishableKey(),
        ]);
    }

    private function getCentralPlan()
    {
        return $this->billingGateway->currentTenantPlan();
    }
}
