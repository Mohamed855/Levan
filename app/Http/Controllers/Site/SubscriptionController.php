<?php

namespace App\Http\Controllers\Site;

use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Exceptions\Payment\PaymentInitiationException;
use App\Exceptions\Payment\PaymentVerificationException;
use App\Exceptions\Subscription\SubscriptionCreationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\SubscriptionCheckoutRequest;
use App\Http\Requests\Site\CompleteCheckoutPaymentRequest;
use App\Http\Requests\Site\VerifyCheckoutOtpRequest;
use App\Models\Admin\Bank\Bank;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\City;
use App\Models\Admin\Plans\Plan;
use App\Services\shared\Bills\BillService;
use App\Services\shared\Coupons\CouponService;
use App\Services\shared\Payment\Moyasar\MoyasarCallbackHandler;
use App\Services\shared\Payment\Moyasar\MoyasarFormConfiguration;
use App\Services\shared\Subscription\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(
        private CouponService $couponService,
        private BillService $billingService,
        private MoyasarCallbackHandler $moyasarCallbackHandler,
        private MoyasarFormConfiguration $moyasarFormConfiguration,
        private SubscriptionService $subscriptionService
    ) {}

    public function checkout(Plan $plan)
    {
        if (! Plan::publiclyAvailable()->whereKey($plan)->exists()) {
            abort(404);
        }

        $verification = request()->session()->get('checkout_verification');
        if (is_array($verification)
            && ! isset($verification['verified_at'])
            && (int) ($verification['expires_at'] ?? 0) < now()->timestamp) {
            request()->session()->forget('checkout_verification');
            $verification = null;
        }

        $otpStep = is_array($verification) && ! isset($verification['verified_at']) && (int) ($verification['plan_id'] ?? 0) === (int) $plan->getKey() && isset($verification['otp_hash']);
        $paymentStep = is_array($verification) && isset($verification['verified_at']) && (int) ($verification['plan_id'] ?? 0) === (int) $plan->getKey();
        $checkoutData = is_array($verification)
            ? collect($verification)->except(['plan_id', 'expires_at', 'otp_hash', 'verified_at'])->all()
            : [];
        $banks = Bank::where('is_active', true)->get();
        $cities = City::all();
        $taxRate = 0.15;

        // استرجاع الكوبون المطبق من الجلسة وحساب الخصم تلقائياً
        $couponCode = $verification['coupon_code'] ?? request()->session()->get('checkout_coupon_code') ?? old('coupon_code');
        $appliedCoupon = null;
        $finalTotal = (float) $plan->price_after_discount;

        if (! empty($couponCode)) {
            $couponResult = $this->couponService->processCoupon($couponCode, (float) $plan->price_after_discount);
            if (! isset($couponResult['error']) && ! empty($couponResult['coupon_id'])) {
                $appliedCoupon = $couponResult;
                $finalTotal = (float) $couponResult['final_total'];
            }
        }

        $taxAmount = round($finalTotal * $taxRate, 2);
        $priceWithoutTax = round($finalTotal - $taxAmount, 2);

        return view('site.checkout', compact('plan', 'priceWithoutTax', 'taxAmount', 'banks', 'cities', 'otpStep', 'paymentStep', 'checkoutData', 'verification', 'appliedCoupon', 'finalTotal'));
    }

    //  Checkout store
    public function processCheckout(SubscriptionCheckoutRequest $request, Plan $plan)
    {
        if (! $this->isPublicPlan($plan)) {
            abort(404);
        }

        $checkout = $request->validated();
        $checkout['plan_id'] = $plan->getKey();
        $checkout['expires_at'] = now()->addMinutes(2)->timestamp;
        if (empty($checkout['coupon_code'])) {
            $checkout['coupon_code'] = $request->session()->get('checkout_coupon_code');
        }
        $otp = $this->checkoutOtp();
        $checkout['otp_hash'] = hash('sha256', $otp);
        $request->session()->put('checkout_verification', $checkout);
        $this->sendCheckoutOtp($checkout['billing_email'], $otp);

        return redirect()->route('site.plans.checkout', $plan);
    }

    public function editCheckout(Plan $plan)
    {
        abort_unless($this->isPublicPlan($plan), 404);
        $checkout = request()->session()->get('checkout_verification', []);
        $safeInput = collect($checkout)->except(['plan_id', 'expires_at', 'otp_hash', 'billing_password', 'billing_password_confirmation'])->all();
        // إزالة تأكيد OTP حتى يعود لخطوة تعديل البيانات مع الحفاظ على البيانات في الجلسة عند عمل Reload
        unset($checkout['verified_at'], $checkout['otp_hash']);
        request()->session()->put('checkout_verification', $checkout);

        return redirect()->route('site.plans.checkout', $plan)->withInput($safeInput);
    }

    public function verifyOtp(VerifyCheckoutOtpRequest $request, Plan $plan)
    {
        if (! $this->hasCheckoutVerification($plan)) {
            $request->session()->forget('checkout_verification');

            return redirect()->route('site.plans.checkout', $plan)->withErrors(['otp' => 'انتهت صلاحية رمز التحقق، يرجى البدء مرة أخرى.']);
        }

        $verification = $request->session()->get('checkout_verification');
        if (! hash_equals($verification['otp_hash'], hash('sha256', (string) $request->validated('otp')))) {
            return back()->withErrors(['otp' => 'رمز التحقق غير صحيح أو منتهي الصلاحية.']);
        }

        $request->session()->forget('checkout_verification');
        $request->session()->put('checkout_verification', [...$verification, 'verified_at' => now()->timestamp]);

        return redirect()->route('site.plans.checkout', $plan);
    }

    public function completeCheckout(CompleteCheckoutPaymentRequest $request, Plan $plan)
    {
        abort_unless($this->isPublicPlan($plan), 404);

        $verification = $request->session()->get('checkout_verification');
        abort_unless(is_array($verification) && isset($verification['verified_at']), 404);

        $validated = $request->validated();
        if (empty($validated['coupon_code'])) {
            $validated['coupon_code'] = $verification['coupon_code'] ?? $request->session()->get('checkout_coupon_code');
        }

        $checkout = array_merge($verification, $validated);

        return $this->createPaymentFromVerifiedCheckout($checkout, $plan);
    }

    //  Callback
    public function handlePaymentCallback(Request $request)
    {
        try {
            $result = $this->moyasarCallbackHandler->verify($request);
            $outcome = $this->subscriptionService->completeOnlineSubscription($result['bill'], $result['payment']);
            $request->session()->forget('checkout_verification');

            return redirect()->to(config('app.url').'/'.$outcome['tenant_id'].'/login');
            //
        } catch (PaymentVerificationException $e) {
            $bill = $this->findCheckoutBill($request->string('bill')->toString());

            if ($bill?->status === BillStatus::PAID && $bill->tenant_id) {
                return redirect()->to(config('app.url').'/'.$bill->tenant_id.'/login');
            }

            if ($bill) {
                return redirect()->route('site.plans.checkout', ['plan' => $bill->metadata['plan_id']])
                    ->with('error', $e->getMessage());
            }

            return redirect()->route('site.index')->with('error', $e->getMessage());
        } catch (SubscriptionCreationException $e) {
            //
            return redirect()->route('site.index')->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            //
            report($e);

            $bill = $this->findCheckoutBill($request->string('bill')->toString());
            if ($bill?->status === BillStatus::PAID && $bill->tenant_id) {
                return redirect()->to(config('app.url').'/'.$bill->tenant_id.'/login');
            }

            if ($bill) {
                return redirect()->route('site.plans.checkout', ['plan' => $bill->metadata['plan_id']])
                    ->with('error', 'تعذر التحقق من الدفع مؤقتًا، يرجى المحاولة مرة أخرى.');
            }

            return redirect()->route('site.index')->with('error', 'حدث خطأ أثناء معالجة نتيجة الدفع');
        }
    }

    /*
    |============================================================================
    |============================================================================
    |                             Private methods
    |============================================================================
    |============================================================================
    */
    private function createPendingBill(Request $request, Plan $plan, array $couponData)
    {
        return $this->billingService->createPendingBill(
            amount: $couponData['final_total'],
            paymentMethod: $request->payment_method === 'bank_transfer' ? PaymentMethod::BANK_TRANSFER : PaymentMethod::ONLINE,
            originalAmount: $plan->price_after_discount,
            type: BillType::NEW_SUBSCRIPTION,
            couponData: $couponData,
            metadata: [
                'plan_id' => $plan->id,
                'billing_name' => $request->billing_name,
                'billing_email' => $request->billing_email,
                'billing_phone' => $request->billing_phone,
                'billing_password' => $request->billing_password,

                'billing_company_name' => $request->billing_company_name,
                'billing_commercial_number' => $request->billing_commercial_number,
                'billing_tax_number' => $request->billing_tax_number,
                'billing_city_id' => $request->billing_city_id,
                'billing_address' => $request->billing_address,
                'checkout_source' => 'site_checkout',
            ]
        );
    }

    private function handleBankTransfer(Request $request, Bill $bill): void
    {
        $this->billingService->submitBankTransfer(
            $bill,
            $request->integer('bank_id'),
            $request->file('transfer_receipt'),
        );
    }

    private function createPaymentFromVerifiedCheckout(array $checkout, Plan $plan)
    {
        try {
            $couponData = $this->couponService->processCoupon($checkout['coupon_code'] ?? null, $plan->price_after_discount);
            if (isset($couponData['error'])) {
                return redirect()->route('site.plans.checkout', $plan)->with('error', $couponData['error']);
            }

            $request = Request::create('/', 'POST', $checkout);
            $pendingBill = $this->createPendingBill($request, $plan, $couponData);

            if (($checkout['payment_method'] ?? null) !== 'bank_transfer') {
                $pendingBill->update(['due_date' => now()->addMinutes(60)]);
            }

            if (($checkout['payment_method'] ?? null) === 'bank_transfer') {
                $this->handleBankTransfer($request, $pendingBill);
                request()->session()->forget('checkout_verification');

                return redirect()->route('site.index')->with('success', 'تم استلام طلبك بنجاح.');
            }

            $moyasarView = $this->showMoyasarCheckout($pendingBill, $plan);

            return $moyasarView;
        } catch (PaymentInitiationException $e) {
            return redirect()->route('site.plans.checkout', $plan)->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('site.plans.checkout', $plan)->with('error', 'حدث خطأ غير متوقع.');
        }
    }

    private function isPublicPlan(Plan $plan): bool
    {
        return Plan::publiclyAvailable()->whereKey($plan)->exists();
    }

    private function hasCheckoutVerification(Plan $plan): bool
    {
        $checkout = request()->session()->get('checkout_verification');

        return is_array($checkout)
            && (int) ($checkout['plan_id'] ?? 0) === (int) $plan->getKey()
            && (int) ($checkout['expires_at'] ?? 0) >= now()->timestamp;
    }

    private function checkoutOtp(): string
    {
        return config('services.moyasar.mode', 'test') === 'test'
            ? '000000'
            : (string) random_int(100000, 999999);
    }

    private function sendCheckoutOtp(string $email, string $otp): void
    {
        if (config('services.moyasar.mode', 'test') === 'test') {
            Log::info('Checkout OTP issued in test mode', ['email' => $email, 'otp' => $otp]);

            return;
        }

        Mail::raw("رمز التحقق لإكمال اشتراكك هو: {$otp}", function ($message) use ($email): void {
            $message->to($email)->subject('رمز التحقق لإكمال الاشتراك');
        });
    }

    private function showMoyasarCheckout(Bill $bill, Plan $plan)
    {
        try {
            $mode = $this->moyasarFormConfiguration->getMode();
            $publishableKey = $this->moyasarFormConfiguration->getPublishableKey();
            $amountInHalala = $this->moyasarFormConfiguration->amountInHalala((float) $bill->amount);
        } catch (PaymentInitiationException $e) {
            $this->billingService->deleteUncompletedCheckoutBill($bill);
            throw $e;
        }

        return view('site.moyasar-checkout', [
            'amountInHalala' => $amountInHalala,
            'bill' => $bill,
            'callbackUrl' => route('site.plans.checkout.callback', ['bill' => $bill->bill_number]),
            'description' => 'اشتراك في '.$plan->name,
            'mode' => $mode,
            'plan' => $plan,
            'publishableKey' => $publishableKey,
        ]);
    }

    private function findCheckoutBill(string $billNumber): ?Bill
    {
        if ($billNumber === '') {
            return null;
        }

        $bill = Bill::where('bill_number', $billNumber)->first();

        return $bill && ($bill->metadata['checkout_source'] ?? null) === 'site_checkout'
            ? $bill
            : null;
    }
}
