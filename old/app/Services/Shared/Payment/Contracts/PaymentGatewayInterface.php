<?php

namespace App\Services\shared\Payment\Contracts;

interface PaymentGatewayInterface
{
    public function getPayment(string $paymentId): array;
}
