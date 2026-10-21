<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\CompleteCheckoutRequest;
use App\Http\Requests\Web\StoreCheckoutRequest;
use App\Models\Admin\City;
use App\Models\Admin\Plans\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CheckoutController extends Controller
{
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
        $checkout['ready_for_payment'] = true;
        $request->session()->put($this->sessionKey($plan), $checkout);

        return to_route('site.plans.checkout', $plan)->with('success', 'تم حفظ بيانات الفاتورة. تحقق من بريدك الإلكتروني لإكمال التحقق.');
    }

    public function edit(Request $request, Plan $plan): RedirectResponse
    {
        $checkout = $request->session()->get($this->sessionKey($plan), []);
        unset($checkout['ready_for_payment']);
        $request->session()->put($this->sessionKey($plan), $checkout);

        return to_route('site.plans.checkout', $plan)->withInput(collect($checkout)->except('billing_password_hash')->all());
    }

    public function completePayment(CompleteCheckoutRequest $request, Plan $plan): RedirectResponse
    {
        abort_unless($request->session()->has($this->sessionKey($plan)), 422);
        $request->session()->forget($this->sessionKey($plan));

        return to_route('site.index')->with('info', 'تم تفعيل الحساب بنجاح. يمكنك الآن تسجيل الدخول باستخدام بياناتك الجديدة.');
    }

    public function verifyEmail(Plan $plan): RedirectResponse
    {
        return to_route('site.plans.checkout', $plan)->with('info', 'يرجى التحقق من بريدك الإلكتروني أولًا.');
    }

    public function callback(): RedirectResponse
    {
        return to_route('site.index');
    }

    public function retry(string $billNumber): RedirectResponse
    {
        return to_route('site.index')->with('info', 'انتهت مهلة التحقق، يرجى بدء العملية مرة أخرى.');
    }

    private function checkoutView(Plan $plan): View
    {
        $checkoutData = request()->session()->get($this->sessionKey($plan), []);
        $finalTotal = $plan->price_after_discount;
        $taxAmount = round($finalTotal * 0.15, 2);

        return view('web.checkout', [
            'plan' => $plan,
            'priceWithoutTax' => round($finalTotal - $taxAmount, 2),
            'taxAmount' => $taxAmount,
            'banks' => collect(),
            'cities' => City::query()->orderBy('name')->get(),
            'otpStep' => false,
            'paymentStep' => (bool) ($checkoutData['ready_for_payment'] ?? false),
            'checkoutData' => collect($checkoutData)->except(['ready_for_payment', 'billing_password_hash'])->all(),
            'verification' => [],
            'appliedCoupon' => null,
            'finalTotal' => $finalTotal,
        ]);
    }

    private function sessionKey(Plan $plan): string
    {
        return 'web.checkout.'.$plan->getKey();
    }
}
