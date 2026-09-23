<?php

namespace App\Services\shared\Payment\Moyasar;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class MoyasarClient
{
    private string $secretKey;
    private string $baseUrl = 'https://api.moyasar.com/v1';

    public function __construct()
    {
        $this->secretKey = config('services.moyasar.secret');
    }


    // الحصول على تفاصيل عملية دفع
    public function getPayment(string $paymentId): array
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')->get("{$this->baseUrl}/payments/{$paymentId}");

            return $response->json();
        } catch (\Exception $e) {


            throw $e;
        }
    }
}
