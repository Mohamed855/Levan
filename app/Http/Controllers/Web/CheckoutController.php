<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Web\InitiateCheckoutPaymentAction;
use App\Actions\Web\ProvisionPaidCheckoutAction;
use App\Actions\Web\VerifyCheckoutPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CompleteCheckoutRequest;
use App\Http\Requests\Web\StoreCheckoutRequest;
use App\Http\Requests\Web\VerifyCheckoutOtpRequest;
use App\Models\Admin\City;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Bill;
use App\Models\Admin\PaymentAttempt;
use App\Services\Web\MoyasarFormConfiguration;
use App\Services\Web\CouponQuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly InitiateCheckoutPaymentAction $initiatePayment,
        private readonly MoyasarFormConfiguration $moyasarConfiguration,
        private readonly VerifyCheckoutPaymentAction $verifyPayment,
        private readonly ProvisionPaidCheckoutAction $provisionPaidCheckout,
        private readonly CouponQuoteService $couponQuotes,
    ) {}

    public function show(Plan $plan): View
    {
        abort_unless($plan->publiclyAvailable()->whereKey($plan)->exists(), 404);

        return $this->checkoutView($plan);
    }

    public function store(StoreCheckoutRequest $request, Plan $plan): RedirectResponse
    {
        abort_unless($plan->publiclyAvailable()->whereKey($plan)->exists(), 404);
        $checkout = $request->safe()->except(['billing_password', 'billing_password_confirmation']);
        $checkout['billing_password_hash'] = Hash::make((string) $request->validated('billing_password'));
        $checkout['plan_id'] = $plan->getKey();
        $checkout['checkout_token'] = (string) Str::uuid();
        $checkout['expires_at'] = now()->addMinutes(2)->timestamp;
        $checkout['otp_hash'] = hash('sha256', $otp = $this->checkoutOtp());
        $checkout['coupon_code'] = $this->couponQuotes->quote($plan, $checkout['coupon_code'] ?? null)['coupon_code'];
        $request->session()->put($this->sessionKey($plan), $checkout);
        $this->sendCheckoutOtp((string) $checkout['billing_email'], $otp);

        return to_route('site.plans.checkout', $plan)->with('success', 'تم حفظ بيانات الفاتورة وإرسال رمز التحقق إلى بريدك الإلكتروني.');
    }

    public function edit(Request $request, Plan $plan): RedirectResponse
    {
        $checkout = $request->session()->get($this->sessionKey($plan), []);
        unset($checkout['otp_hash'], $checkout['verified_at'], $checkout['expires_at']);
        $request->session()->put($this->sessionKey($plan), $checkout);

        return to_route('site.plans.checkout', $plan)->withInput(collect($checkout)->except('billing_password_hash')->all());
    }

    public function completePayment(CompleteCheckoutRequest $request, Plan $plan): View|RedirectResponse
    {
        $checkout = $request->session()->get($this->sessionKey($plan));

        if (! $this->hasVerifiedCheckout($checkout, $plan)) {
            return to_route('site.plans.checkout', $plan)
                ->withErrors(['payment_method' => 'يجب التحقق من البريد الإلكتروني قبل بدء الدفع.']);
        }

        $paymentMethod = (string) $request->validated('payment_method');
        $quote = $this->couponQuotes->quote($plan, $checkout['coupon_code'] ?? null);
        $checkout['pricing'] = $quote;
        $moyasar = null;
        if ($paymentMethod === 'card') {
            try {
                $moyasar = [
                    'amount_in_halala' => $this->moyasarConfiguration->amountInHalala($quote['total_amount']),
                    'mode' => $this->moyasarConfiguration->mode(),
                    'publishable_key' => $this->moyasarConfiguration->publishableKey(),
                ];
            } catch (\Throwable $exception) {
                report($exception);

                return to_route('site.plans.checkout', $plan)
                    ->with('error', 'إعدادات الدفع الإلكتروني غير مكتملة. لم يتم إنشاء فاتورة أو تفعيل حساب.');
            }
        }

        try {
            $attempt = $this->initiatePayment->execute(
                $checkout,
                $plan,
                $paymentMethod,
                $request->integer('bank_id') ?: null,
                $request->file('transfer_receipt'),
            );
        } catch (\Throwable $exception) {
            report($exception);

            return to_route('site.plans.checkout', $plan)
                ->with('error', 'تعذر بدء عملية الدفع. لم يتم إنشاء حساب أو تفعيل اشتراك.');
        }

        $request->session()->forget($this->sessionKey($plan));

        if ($attempt->payment_method === 'bank_transfer') {
            return to_route('site.index')->with('success', 'تم استلام طلب التحويل البنكي، وسيتم تفعيل الحساب بعد المراجعة.');
        }

        return view('web.moyasar-checkout', [
            'amountInHalala' => $moyasar['amount_in_halala'],
            'bill' => $attempt->bill,
            'callbackUrl' => route('site.plans.checkout.callback', ['bill' => $attempt->bill->bill_number]),
            'description' => 'اشتراك في '.$plan->name,
            'mode' => $moyasar['mode'],
            'plan' => $plan,
            'publishableKey' => $moyasar['publishable_key'],
        ]);
    }

    public function verifyEmail(VerifyCheckoutOtpRequest $request, Plan $plan): RedirectResponse
    {
        $checkout = $request->session()->get($this->sessionKey($plan));

        if (! $this->hasActiveVerification($checkout, $plan)) {
            $request->session()->forget($this->sessionKey($plan));

            return to_route('site.plans.checkout', $plan)
                ->withErrors(['otp' => 'انتهت صلاحية رمز التحقق، يرجى البدء مرة أخرى.']);
        }

        if (! hash_equals((string) $checkout['otp_hash'], hash('sha256', (string) $request->validated('otp')))) {
            return back()->withErrors(['otp' => 'رمز التحقق غير صحيح أو منتهي الصلاحية.']);
        }

        $request->session()->put($this->sessionKey($plan), [...$checkout, 'verified_at' => now()->timestamp, 'expires_at' => now()->addHour()->timestamp]);

        return to_route('site.plans.checkout', $plan);
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $attempt = $this->verifyPayment->execute(
                $request->string('bill')->toString(),
                $request->string('id')->toString(),
            );
            $tenant = $this->provisionPaidCheckout->execute($attempt);

            return redirect()->to(url('/'.$tenant->getKey().'/login'))
                ->with('success', 'تم تأكيد الدفع وتفعيل الاشتراك بنجاح.');
        } catch (\Throwable $exception) {
            report($exception);

            $billNumber = $request->string('bill')->toString();
            $bill = $billNumber === '' ? null : Bill::query()->where('bill_number', $billNumber)->first();
            $retryable = $bill?->paymentAttempts()->where('provider', 'moyasar')->where('status', 'initiated')->exists() ?? false;
            if ($bill && $retryable && ($bill->metadata['checkout_source'] ?? null) === 'site_checkout') {
                return redirect()->to($this->retryUrl($bill))
                    ->with('error', 'تعذر تأكيد الدفع مؤقتًا. يمكنك إعادة محاولة الدفع دون إنشاء فاتورة جديدة.');
            }

            return to_route('site.index')->with('error', 'تعذر تأكيد الدفع أو تجهيز الحساب. لم يُنشأ حساب إضافي ويمكن إعادة معالجة العملية بأمان.');
        }
    }

    public function retry(string $billNumber): View
    {
        $bill = Bill::query()->where('bill_number', $billNumber)->firstOrFail();
        abort_unless(($bill->metadata['checkout_source'] ?? null) === 'site_checkout', 404);
        abort_if($bill->due_at !== null && $bill->due_at->isPast(), 410);
        $attempt = PaymentAttempt::query()->where('bill_id', $bill->getKey())
            ->where('provider', 'moyasar')
            ->where('status', 'initiated')
            ->latest('id')
            ->firstOrFail();
        $plan = Plan::publiclyAvailable()->findOrFail($bill->metadata['plan_id'] ?? null);

        return view('web.moyasar-checkout', [
            'amountInHalala' => $this->moyasarConfiguration->amountInHalala((float) $attempt->amount),
            'bill' => $bill,
            'callbackUrl' => route('site.plans.checkout.callback', ['bill' => $bill->bill_number]),
            'description' => 'اشتراك في '.$plan->name,
            'mode' => $this->moyasarConfiguration->mode(),
            'plan' => $plan,
            'publishableKey' => $this->moyasarConfiguration->publishableKey(),
        ]);
    }

    private function checkoutView(Plan $plan): View
    {
        $checkoutData = request()->session()->get($this->sessionKey($plan), []);
        if (is_array($checkoutData) && ! $this->hasActiveVerification($checkoutData, $plan) && ! $this->hasVerifiedCheckout($checkoutData, $plan)) {
            request()->session()->forget($this->sessionKey($plan));
            $checkoutData = [];
        }
        $finalTotal = $plan->price_after_discount;
        $taxAmount = round($finalTotal * 0.15, 2);

        return view('web.checkout', [
            'plan' => $plan,
            'priceWithoutTax' => round($finalTotal - $taxAmount, 2),
            'taxAmount' => $taxAmount,
            'banks' => collect(),
            'cities' => City::query()->orderBy('name')->get(),
            'otpStep' => $this->hasActiveVerification($checkoutData, $plan),
            'paymentStep' => $this->hasVerifiedCheckout($checkoutData, $plan),
            'checkoutData' => collect($checkoutData)->except(['otp_hash', 'verified_at', 'billing_password_hash'])->all(),
            'verification' => $checkoutData,
            'appliedCoupon' => null,
            'finalTotal' => $finalTotal,
        ]);
    }

    private function sessionKey(Plan $plan): string
    {
        return 'web.checkout.'.$plan->getKey();
    }

    private function hasActiveVerification(mixed $checkout, Plan $plan): bool
    {
        return is_array($checkout)
            && isset($checkout['otp_hash'])
            && ! isset($checkout['verified_at'])
            && (int) ($checkout['plan_id'] ?? 0) === (int) $plan->getKey()
            && (int) ($checkout['expires_at'] ?? 0) >= now()->timestamp;
    }

    private function hasVerifiedCheckout(mixed $checkout, Plan $plan): bool
    {
        return is_array($checkout)
            && isset($checkout['verified_at'])
            && (int) ($checkout['plan_id'] ?? 0) === (int) $plan->getKey()
            && (int) ($checkout['expires_at'] ?? 0) >= now()->timestamp;
    }

    private function checkoutOtp(): string
    {
        return app()->environment('testing') ? '000000' : (string) random_int(100000, 999999);
    }

    private function sendCheckoutOtp(string $email, string $otp): void
    {
        if (app()->environment('testing')) {
            Log::info('Checkout OTP issued in test mode.', ['email' => $email, 'otp' => $otp]);

            return;
        }

        Mail::raw("رمز التحقق لإكمال اشتراكك هو: {$otp}", function ($message) use ($email): void {
            $message->to($email)->subject('رمز التحقق لإكمال الاشتراك');
        });
    }

    private function retryUrl(Bill $bill): string
    {
        return URL::temporarySignedRoute(
            'site.plans.checkout.retry',
            $bill->due_at ?? now()->addHour(),
            ['billNumber' => $bill->bill_number],
        );
    }
}
