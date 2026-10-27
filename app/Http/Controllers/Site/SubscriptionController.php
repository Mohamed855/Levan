<?php

namespace App\Http\Controllers\Site;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Exceptions\Payment\PaymentInitiationException;
use App\Exceptions\Payment\PaymentVerificationException;
use App\Exceptions\Subscription\SubscriptionCreationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\CompleteCheckoutPaymentRequest;
use App\Http\Requests\Site\SubscriptionCheckoutRequest;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

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
        if (is_array($verification)) {
            if (isset($verification['verified_at']) && (int) ($verification['expires_at'] ?? 0) < now()->timestamp) {
                request()->session()->forget(['checkout_verification', 'checkout_coupon_code']);
                $verification = null;
            } elseif (! isset($verification['verified_at']) && (int) ($verification['session_expires_at'] ?? (($verification['expires_at'] ?? 0) + 3600)) < now()->timestamp) {
                request()->session()->forget(['checkout_verification', 'checkout_coupon_code']);
                $verification = null;
            }
        }

        $isCurrentPlan = is_array($verification) && (int) ($verification['plan_id'] ?? 0) === (int) $plan->getKey();
        $otpStep = $isCurrentPlan && ! isset($verification['verified_at']) && isset($verification['otp_hash']);
        $paymentStep = $isCurrentPlan && isset($verification['verified_at']);
        $isEditing = $isCurrentPlan && (bool) ($verification['is_editing'] ?? false);

        if ($isEditing) {
            $checkoutData = collect($verification)->except(['plan_id', 'expires_at', 'otp_hash', 'verified_at', 'billing_password_hash', 'is_editing'])->all();
        } elseif ($otpStep) {
            $checkoutData = ['billing_email' => $verification['billing_email'] ?? ''];
        } else {
            $checkoutData = [];
        }
        $banks = Bank::where('is_active', true)->get();
        $cities = City::all();
        $taxRate = 0.15;
        $resendCount = (int) ($verification['resend_count'] ?? 0);
        $maxResends = 3;

        // استرجاع الكوبون المطبق من الجلسة وحساب الخصم تلقائياً
        $couponCode = $verification['coupon_code'] ?? request()->session()->get('checkout_coupon_code') ?? old('coupon_code');
        $appliedCoupon = null;
        $finalTotal = (float) $plan->price_after_discount;

        if (! empty($couponCode)) {
            $couponResult = $this->couponService->processCoupon($couponCode, (float) $plan->price_after_discount);
            if (! isset($couponResult['error']) && ! empty($couponResult['coupon_id'])) {
                $appliedCoupon = $couponResult;
                $finalTotal = (float) $couponResult['final_total'];
            } else {
                request()->session()->forget('checkout_coupon_code');
                if (is_array($verification) && isset($verification['coupon_code'])) {
                    unset($verification['coupon_code']);
                    request()->session()->put('checkout_verification', $verification);
                }
            }
        }

        $taxAmount = round($finalTotal * $taxRate, 2);
        $priceWithoutTax = round($finalTotal - $taxAmount, 2);

        return view('web.checkout', compact('plan', 'priceWithoutTax', 'taxAmount', 'banks', 'cities', 'otpStep', 'paymentStep', 'checkoutData', 'verification', 'appliedCoupon', 'finalTotal', 'resendCount', 'maxResends'));
    }

    //  Checkout store
    public function processCheckout(SubscriptionCheckoutRequest $request, Plan $plan)
    {
        if (! $this->isPublicPlan($plan)) {
            abort(404);
        }

        $checkout = $request->validated();
        $checkout['billing_password_hash'] = Hash::make($checkout['billing_password']);
        unset($checkout['billing_password'], $checkout['billing_password_confirmation'], $checkout['is_editing']);
        $checkout['plan_id'] = $plan->getKey();
        $checkout['expires_at'] = now()->addMinutes(2)->timestamp;
        $checkout['session_expires_at'] = now()->addHour()->timestamp;
        $checkout['resend_count'] = 0;
        $couponCode = $checkout['coupon_code'] ?? $request->session()->get('checkout_coupon_code');
        if (! empty($couponCode)) {
            $couponResult = $this->couponService->processCoupon($couponCode, (float) $plan->price_after_discount);
            if (! isset($couponResult['error']) && ! empty($couponResult['coupon_id'])) {
                $checkout['coupon_code'] = $couponCode;
            } else {
                $request->session()->forget('checkout_coupon_code');
                $checkout['coupon_code'] = null;
            }
        } else {
            $checkout['coupon_code'] = null;
        }
        $otp = $this->checkoutOtp();
        $checkout['otp_hash'] = hash('sha256', $otp);
        $request->session()->put('checkout_verification', $checkout);
        $this->sendCheckoutOtp($checkout['billing_email'], $otp);

        return redirect()->route('site.plans.checkout', $plan);
    }

    public function resendOtp(Request $request, Plan $plan)
    {
        abort_unless($this->isPublicPlan($plan), 404);

        $checkout = $request->session()->get('checkout_verification');
        if (! is_array($checkout) || (int) ($checkout['plan_id'] ?? 0) !== (int) $plan->getKey() || ! isset($checkout['otp_hash'])) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'لا توجد جلسة تحقق نشطة.'], 422);
            }

            return redirect()->route('site.plans.checkout', $plan)->withErrors(['otp' => 'لا توجد جلسة تحقق نشطة.']);
        }

        $resendCount = (int) ($checkout['resend_count'] ?? 0);
        if ($resendCount >= 3) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لقد استنفدت الحد الأقصى لمحاولات إعادة الإرسال (3 مرات). يرجى العودة لتعديل البيانات.',
                    'resend_count' => $resendCount,
                ], 422);
            }

            return redirect()->route('site.plans.checkout', $plan)
                ->withErrors(['otp' => 'لقد استنفدت الحد الأقصى لمحاولات إعادة الإرسال (3 مرات). يرجى العودة لتعديل البيانات.']);
        }

        $otp = $this->checkoutOtp();
        $checkout['otp_hash'] = hash('sha256', $otp);
        $checkout['expires_at'] = now()->addMinutes(2)->timestamp;
        $checkout['resend_count'] = $resendCount + 1;
        $request->session()->put('checkout_verification', $checkout);
        $this->sendCheckoutOtp($checkout['billing_email'], $otp);

        $message = 'تم إعادة إرسال رمز التحقق بنجاح إلى بريدك الإلكتروني.';
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'expires_at' => $checkout['expires_at'],
                'resend_count' => $checkout['resend_count'],
                'remaining_resends' => max(0, 3 - $checkout['resend_count']),
            ]);
        }

        return redirect()->route('site.plans.checkout', $plan)
            ->with('success', $message);
    }

    public function editCheckout(Plan $plan)
    {
        abort_unless($this->isPublicPlan($plan), 404);
        $checkout = request()->session()->get('checkout_verification', []);

        $safeInput = collect($checkout)->except(['plan_id', 'expires_at', 'otp_hash', 'verified_at', 'billing_password', 'billing_password_hash', 'billing_password_confirmation', 'resend_count', 'session_expires_at', 'is_editing'])->all();
        // إزالة تأكيد OTP حتى يعود لخطوة تعديل البيانات مع الحفاظ على البيانات في الجلسة عند عمل Reload
        unset($checkout['verified_at'], $checkout['otp_hash'], $checkout['resend_count']);
        $checkout['is_editing'] = true;
        $checkout['expires_at'] = now()->addHour()->timestamp;
        request()->session()->put('checkout_verification', $checkout);

        return redirect()->route('site.plans.checkout', $plan)->withInput($safeInput);
    }

    public function verifyOtp(VerifyCheckoutOtpRequest $request, Plan $plan)
    {
        if (! $this->hasCheckoutVerification($plan)) {
            return redirect()->route('site.plans.checkout', $plan)->withErrors(['otp' => 'انتهت صلاحية رمز التحقق. يرجى إعادة الإرسال أو تعديل البيانات.']);
        }

        $verification = $request->session()->get('checkout_verification');
        if (! hash_equals($verification['otp_hash'], hash('sha256', (string) $request->validated('otp')))) {
            return back()->withErrors(['otp' => 'رمز التحقق غير صحيح أو منتهي الصلاحية.']);
        }

        $updated = [
            ...$verification,
            'verified_at' => now()->timestamp,
            'expires_at' => now()->addHour()->timestamp,
        ];
        unset($updated['is_editing']);

        $request->session()->put('checkout_verification', $updated);

        return redirect()->route('site.plans.checkout', $plan);
    }

    public function completeCheckout(CompleteCheckoutPaymentRequest $request, Plan $plan)
    {
        abort_unless($this->isPublicPlan($plan), 404);

        $verification = $request->session()->get('checkout_verification');
        if (! $this->hasVerifiedCheckout($verification, $plan)) {
            $request->session()->forget(['checkout_verification', 'checkout_coupon_code']);

            return redirect()->route('site.plans.checkout', $plan)
                ->withErrors(['payment_method' => 'انتهت صلاحية محاولة الدفع، يرجى المحاولة مرة أخرى.']);
        }

        $validated = $request->validated();
        $couponCode = ! empty($validated['coupon_code'])
            ? $validated['coupon_code']
            : ($verification['coupon_code'] ?? $request->session()->get('checkout_coupon_code'));

        if (! empty($couponCode)) {
            $couponResult = $this->couponService->processCoupon($couponCode, (float) $plan->price_after_discount);
            if (isset($couponResult['error']) || empty($couponResult['coupon_id'])) {
                $request->session()->forget('checkout_coupon_code');
                unset($verification['coupon_code']);
                $request->session()->put('checkout_verification', $verification);
                $couponCode = null;
            }
        } else {
            $couponCode = null;
        }

        $validated['coupon_code'] = $couponCode;
        $checkout = array_merge($verification, $validated);
        $checkout['coupon_code'] = $couponCode;

        return $this->createPaymentFromVerifiedCheckout($checkout, $plan);
    }

    //  Callback
    public function handlePaymentCallback(Request $request)
    {
        try {
            $result = $this->moyasarCallbackHandler->verify($request);
            $outcome = $this->subscriptionService->completeOnlineSubscription($result['bill'], $result['payment']);
            $request->session()->forget(['checkout_verification', 'checkout_coupon_code']);

            return redirect()->to(url('/'.$outcome['tenant_id'].'/login'))
                ->with('success', 'تم تفعيل أشتراكك بنجاح يمكنك الأن تسجيل الدخول');
            //
        } catch (PaymentVerificationException $e) {
            $bill = $this->findCheckoutBill($request->string('bill')->toString());

            if ($bill?->status === BillStatus::PAID && $bill->tenant_id) {
                $request->session()->forget(['checkout_verification', 'checkout_coupon_code']);

                return redirect()->to(url('/'.$bill->tenant_id.'/login'))
                    ->with('success', 'تم تفعيل أشتراكك بنجاح يمكنك الأن تسجيل الدخول');
            }

            if ($bill) {
                return redirect()->to($this->retryCheckoutUrl($bill))
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
                $request->session()->forget(['checkout_verification', 'checkout_coupon_code']);

                return redirect()->to(url('/'.$bill->tenant_id.'/login'))
                    ->with('success', 'تم تفعيل أشتراكك بنجاح يمكنك الأن تسجيل الدخول');
            }

            if ($bill) {
                return redirect()->to($this->retryCheckoutUrl($bill))
                    ->with('error', 'تعذر التحقق من الدفع مؤقتًا، يرجى المحاولة مرة أخرى.');
            }

            return redirect()->route('site.index')->with('error', 'حدث خطأ أثناء معالجة نتيجة الدفع');
        }
    }

    public function retryCheckout(string $billNumber)
    {
        $bill = $this->findCheckoutBill($billNumber);
        abort_unless($bill && in_array($bill->status, [BillStatus::PENDING, BillStatus::UNPAID], true), 404);
        abort_if($bill->due_date && now()->isAfter($bill->due_date), 410);

        $plan = Plan::publiclyAvailable()->find($bill->metadata['plan_id'] ?? null);
        abort_unless($plan, 404);

        return $this->showMoyasarCheckout($bill, $plan);
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
                'billing_password_hash' => $request->billing_password_hash ?? Hash::make((string) $request->billing_password),

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
                request()->session()->forget(['checkout_verification', 'checkout_coupon_code']);

                return redirect()->route('site.index')->with('success', 'تم استلام طلبك بنجاح.');
            }

            // الاحتفاظ ببيانات التحقق في الجلسة حتى إذا ضغط المستخدم زر الرجوع من صفحة ميسر يعود لخطوة الدفع لا لتسجيل البيانات
            // ويتم حذف الجلسة عند اكتمال الدفع بنجاح في handlePaymentCallback
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

    private function hasVerifiedCheckout(?array $checkout, Plan $plan): bool
    {
        return is_array($checkout)
            && isset($checkout['verified_at'])
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

        \App\Services\Shared\Mail\SystemMailService::sendOtp($email, $otp, 'إكمال الاشتراك');
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

        return view('web.moyasar-checkout', [
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

    private function retryCheckoutUrl(Bill $bill): string
    {
        return URL::temporarySignedRoute(
            'site.plans.checkout.retry',
            $bill->due_date ?? now()->addHour(),
            ['billNumber' => $bill->bill_number],
        );
    }
}
