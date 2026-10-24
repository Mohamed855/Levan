<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Subscriptions;

use App\Actions\Tenant\Subscriptions\FulfillTenantCommercialPaymentAction;
use App\Actions\Web\VerifyCheckoutPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Subscriptions\InitiateCommercialPaymentRequest;
use App\Services\Tenant\Shared\CentralSubscriptionGateway;
use App\Services\Web\MoyasarFormConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

final class TenantSubscriptionController extends Controller
{
    public function __construct(
        private readonly CentralSubscriptionGateway $subscriptions,
        private readonly MoyasarFormConfiguration $moyasar,
        private readonly VerifyCheckoutPaymentAction $verifyPayment,
        private readonly FulfillTenantCommercialPaymentAction $fulfillPayment,
    ) {
        $this->middleware('can:subscriptions.view')->only('index');
        $this->middleware('can:subscriptions.renew')->only('renew');
        $this->middleware('can:subscriptions.add_licenses')->only('licenses');
    }

    public function index(): View
    {
        return view('tenant.subscriptions.index', [
            'subscription' => $this->subscriptions->current(),
            'plans' => $this->subscriptions->availablePlans(),
        ]);
    }

    public function renew(InitiateCommercialPaymentRequest $request): RedirectResponse
    {
        return $this->redirectToPayment($this->subscriptions->initiateRenewal((string) $request->validated('payment_method')));
    }

    public function licenses(InitiateCommercialPaymentRequest $request): RedirectResponse
    {
        return $this->redirectToPayment($this->subscriptions->initiateLicensePurchase(
            (int) $request->validated('license_count'),
            (string) $request->validated('payment_method'),
        ));
    }

    public function payment(string $billNumber): View|RedirectResponse
    {
        $attempt = $this->subscriptions->attemptForCurrentTenant($billNumber);
        if ($attempt->status !== 'initiated' || $attempt->expires_at?->isPast()) {
            return to_route('tenant.subscriptions.index')->with('error', 'محاولة الدفع لم تعد صالحة.');
        }

        return view('tenant.subscriptions.payment', [
            'attempt' => $attempt,
            'amountInHalala' => $this->moyasar->amountInHalala((float) $attempt->amount),
            'callbackUrl' => route('tenant.subscriptions.callback', ['billNumber' => $billNumber]),
            'publishableKey' => $this->moyasar->publishableKey(),
            'mode' => $this->moyasar->mode(),
        ]);
    }

    public function callback(Request $request, string $billNumber): RedirectResponse
    {
        $paymentId = (string) $request->query('id', '');
        try {
            $attempt = $this->subscriptions->attemptForCurrentTenant($billNumber);
            $verified = $this->verifyPayment->execute($billNumber, $paymentId);
            abort_unless($verified->is($attempt), 404);
            $this->fulfillPayment->execute($verified->bill);

            return to_route('tenant.subscriptions.index')->with('success', 'تم تأكيد الدفع وتحديث الاشتراك بنجاح.');
        } catch (RuntimeException $exception) {
            return to_route('tenant.subscriptions.index')->with('error', $exception->getMessage());
        }
    }

    private function redirectToPayment($attempt): RedirectResponse
    {
        if ($attempt->provider === 'bank_transfer') {
            return to_route('tenant.bills.bank-transfer', $attempt->bill_id)
                ->with('success', 'أُنشئت الفاتورة. ارفع إشعار التحويل لإتمام المراجعة.');
        }

        return to_route('tenant.subscriptions.payment', $attempt->bill->bill_number);
    }
}
