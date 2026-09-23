<?php

namespace App\Services\shared\Payment\Contracts;

use App\Services\shared\Payment\PaymentData;

interface PaymentGatewayInterface
{
    public function createPayment(PaymentData $data, string $callbackUrl): array;

    public function getPayment(string $paymentId): array;

    // public function refundPayment(string $paymentId, float $amount): array;
}
