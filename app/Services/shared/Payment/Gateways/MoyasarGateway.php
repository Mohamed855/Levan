<?php

namespace App\Services\shared\Payment\Gateways;

use App\Services\shared\Payment\Contracts\PaymentGatewayInterface;
use App\Services\shared\Payment\PaymentData;
use Illuminate\Support\Facades\Http;

class MoyasarGateway implements PaymentGatewayInterface
{
    private string $secretKey;
    private string $baseUrl = 'https://api.moyasar.com/v1';

    public function __construct()
    {
        $this->secretKey = (string) (config('services.moyasar.secret') ?? '');
    }

    public function createPayment(PaymentData $data, string $callbackUrl): array
    {
        $amountInHalala = (int) round($data->amount * 100);

        [$month, $year] = $this->splitExpiry($data->expDate);

        if (!$month || !$year) {
            throw new \InvalidArgumentException('تاريخ انتهاء البطاقة غير صالح.');
        }

        if ($data->billNumber) {
            $callbackUrl .= '?bill=' . urlencode($data->billNumber);
        }

        $response = Http::withBasicAuth($this->secretKey, '')
            ->post("{$this->baseUrl}/payments", [
                'amount'       => $amountInHalala,
                'currency'     => 'SAR',
                'description'  => $data->description,
                'callback_url' => $callbackUrl,
                'source'       => [
                    'type'   => $data->cardType,
                    'name'   => $data->cardName,
                    'number' => $data->cardNumber,
                    'cvc'    => $data->cvc,
                    'month'  => $month,
                    'year'   => $year,
                ],
            ]);

        return $response->json();
    }

    public function getPayment(string $paymentId): array
    {
        $response = Http::withBasicAuth($this->secretKey, '')->get("{$this->baseUrl}/payments/{$paymentId}");

        return $response->json();
    }


    /*
    |============================================================================
    |============================================================================
    |                           Private Methods
    |============================================================================
    |============================================================================
    */
    private function splitExpiry(string $exp): array
    {
        if (preg_match('/^(\d{2})\/?(\d{2})$/', $exp, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }
        return [null, null];
    }
}
