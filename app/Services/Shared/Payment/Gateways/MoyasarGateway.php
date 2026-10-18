<?php

namespace App\Services\shared\Payment\Gateways;

use App\Services\shared\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;

class MoyasarGateway implements PaymentGatewayInterface
{
    private string $secretKey;
    private string $baseUrl;
    private int $timeout;
    private string $mode;

    public function __construct()
    {
        $this->secretKey = (string) (config('services.moyasar.secret') ?? '');
        $this->baseUrl = rtrim((string) config('services.moyasar.base_url', 'https://api.moyasar.com/v1'), '/');
        $this->timeout = (int) config('services.moyasar.timeout', 15);
        $this->mode = (string) config('services.moyasar.mode', 'test');
    }

    public function getPayment(string $paymentId): array
    {
        $response = Http::timeout($this->timeout)
            ->withOptions(['verify' => (bool) config('services.moyasar.verify_ssl', true)])
            ->withBasicAuth($this->configuredSecretKey(), '')
            ->get("{$this->baseUrl}/payments/{$paymentId}");

        return $response->json() ?? [];
    }

    /*
    |============================================================================
    |============================================================================
    |                           Private Methods
    |============================================================================
    |============================================================================
    */
    private function configuredSecretKey(): string
    {
        if (!in_array($this->mode, ['test', 'live'], true)) {
            throw new \LogicException('وضع Moyasar غير صالح. استخدم test أو live.');
        }

        $expectedPrefix = $this->mode === 'test' ? 'sk_test_' : 'sk_live_';
        if (!str_starts_with($this->secretKey, $expectedPrefix)) {
            throw new \LogicException("مفتاح Moyasar لا يطابق وضع {$this->mode}.");
        }

        return $this->secretKey;
    }

}
