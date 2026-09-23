<?php

namespace App\Services\shared\Payment\Moyasar;

use App\Services\shared\Payment\Contracts\PaymentGatewayInterface;
use App\Services\shared\Payment\PaymentData;
use Illuminate\Support\Facades\Http;

class MoyasarService
{












    // دفع فاتورة
    public function paymentBill($request)
    {
        return $this->createPayment($request, tenant_route_name('bills.callback'));
    }


    // التجديد
    public function createRenewSubscriptionPayment($request)
    {
        return $this->createPayment($request, tenant_route_name('subscriptions.renew.callback'));
    }

    //  شراء تراخيص
    public function createUpgradePayment($request)
    {
        return $this->createPayment($request, tenant_route_name('subscriptions.upgrade.callback'));
    }

    // الاشتراك اول  لعميل موجود مسبقا
    public function createNewSubscriptionToTenant($request)
    {
        return $this->createPayment($request, tenant_route_name('subscribe.callback'));
    }

    public function createRenewalPayment($request)
    {
        return $this->createPayment($request, tenant_route_name('domains.moyasar.renewal.callback'));
    }

    /*
    |============================================================================
    |============================================================================
    |                          Private methods
    |============================================================================
    |============================================================================
    */
    public function createPayment($request, string $callbackUrl): array
    {
        $cleanAmount = preg_replace('/[^0-9.]/', '', (string) $request->amount);
        $amountFloat = (float) $cleanAmount;

        if ($amountFloat <= 0) {
            throw new \InvalidArgumentException('المبلغ غير صالح.');
        }

        // تحويل الريال إلى هللة (1.5 → 150)
        // (يجب أن يكون عدد صحيح)
        $amountInHalala = (int) round($amountFloat * 100);
        $exp            = preg_replace('/\s+/', '', (string) $request->exp_date_display); // --- معالجة تاريخ الانتهاء ---
        [$month, $year] = $this->splitExpiry($exp);

        if (!$month || !$year) {
            throw new \InvalidArgumentException('تاريخ انتهاء البطاقة غير صالح.');
        }

        if ($request->bill_number) {
            $callbackUrl .= '?bill=' . urlencode($request->bill_number);
        }

        $response = Http::withBasicAuth(config('services.moyasar.secret'), '')
            ->post('https://api.moyasar.com/v1/payments', [
                'amount'        => $amountInHalala,
                'currency'      => 'SAR',
                'description'   => $request->description ?? 'عملية دفع',
                'callback_url'  => $callbackUrl,

                'source' => [
                    'type'   => $request->cardType ?? 'creditcard',
                    'name'   => $request->card_name_display,
                    'number' => preg_replace('/\s+/', '', (string) $request->card_number),
                    'cvc'    => $request->cvc,
                    'month'  => $month,
                    'year'   => $year,
                ],
            ]);

        return $response->json();
    }

    private function splitExpiry(string $exp): array
    {
        if (preg_match('/^(\d{2})\/?(\d{2})$/', $exp, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }
        return [null, null];
    }

    public function renewDomain(string $domain, int $years, string $expDate)
    {
        return Http::get('https://api.sandbox.namecheap.com/xml.response', array_merge($this->auth, [
            'Command'    => 'namecheap.domains.renew',
            'DomainName' => $domain,
            'Years'      => $years,
            'ExpDate'    => $expDate,
        ]));
    }
}
