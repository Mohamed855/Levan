<?php

namespace App\Http\Controllers\Site;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Exceptions\Payment\PaymentInitiationException;
use App\Exceptions\Payment\PaymentVerificationException;
use App\Exceptions\Subscription\SubscriptionCreationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\SubscriptionCheckoutRequest;
use App\Models\Admin\Bank\Bank;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Plans\Plan;
use App\Services\shared\Bills\BillService;
use App\Services\shared\Coupons\CouponService;
use App\Services\shared\Payment\Moyasar\MoyasarCallbackHandler;
use App\Services\shared\Payment\PaymentData;
use App\Services\shared\Payment\PaymentService;
use App\Services\shared\Subscription\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(
        private CouponService          $couponService,
        private BillService            $billingService,
        private PaymentService         $paymentService,
        private MoyasarCallbackHandler $moyasarCallbackHandler,
        private SubscriptionService    $subscriptionService
    ) {}

    public function checkout(Plan $plan)
    {
        if (!$plan->is_active) abort(404);

        $banks           = Bank::where('is_active', true)->get();
        $cities          = \App\Models\Admin\City::all();
        $taxRate         = 0.15;
        $taxAmount       = round($plan->price_after_discount * $taxRate, 2);
        $priceWithoutTax = round($plan->price_after_discount - $taxAmount, 2);

        return view('site.checkout', compact('plan', 'priceWithoutTax', 'taxAmount', 'banks', 'cities'));
    }

    //  Checkout store
    public function processCheckout(SubscriptionCheckoutRequest $request, Plan $plan)
    {
        if (!$plan->is_active) abort(404);

        try {
            $couponData = $this->couponService->processCoupon($request->input('coupon_code'), $plan->price_after_discount);

            if (isset($couponData['error'])) {
                return redirect()->back()->with('error', $couponData['error'])->withInput();
            }

            // Create pendding bill
            $pendingBill = $this->createPendingBill($request, $plan, $couponData);

            if ($request->payment_method === 'bank_transfer') {
                $this->handleBankTransfer($request, $pendingBill);
                return redirect()->route('site.index')->with('success', 'تم استلام طلبك بنجاح. سيتم تفعيل اشتراكك خلال 24 ساعة بعد التحقق من التحويل.');
            }

            // جلب الرابط
            $url = $this->handlePaymentInitiation($request, $plan, $couponData, $pendingBill);

            return redirect($url);
            //
        } catch (PaymentInitiationException $e) {
            //
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        } catch (\Throwable $e) {
            //
            report($e);
            return redirect()->back()->with('error', 'حدث خطأ غير متوقع.')->withInput();
        }
    }

    //  Callback
    public function handlePaymentCallback(Request $request)
    {
        try {
            $result  = $this->moyasarCallbackHandler->verify($request);
            $outcome = $this->subscriptionService->completeOnlineSubscription($result['bill'], $result['payment']);

            return redirect()->to(config('app.url') . '/' . $outcome['tenant_id'] . '/login');
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
                'plan_id'                   => $plan->id,
                'billing_name'              => $request->billing_name,
                'billing_email'             => $request->billing_email,
                'billing_phone'             => $request->billing_phone,
                'billing_password'          => $request->billing_password,

                'billing_company_name'      => $request->billing_company_name,
                'billing_commercial_number' => $request->billing_commercial_number,
                'billing_tax_number'        => $request->billing_tax_number,
                'billing_city_id'           => $request->billing_city_id,
                'billing_address'           => $request->billing_address,
            ]
        );
    }

    private function handleBankTransfer(Request $request, Bill $bill): void
    {
        $path = $request->file('transfer_receipt')->store('bank_transfers', 'public');

        $bill->update([
            'status'      => BillStatus::PENDING_VERIFICATION,
            'bank_id'     => $request->bank_id,
            'invoice_url' => $path,
        ]);
    }

    private function handlePaymentInitiation(Request $request, Plan $plan, array $couponData, Bill $bill)
    {
        return $this->paymentService->initiatePayment(
            PaymentData::fromRequest($request, $couponData['final_total'], 'اشتراك في ' . $plan->name),
            $bill,
            'newSubscriptionPayment'
        );
    }
}
