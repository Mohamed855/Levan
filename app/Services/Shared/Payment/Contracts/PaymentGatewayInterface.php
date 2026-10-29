<?php

namespace App\Services\Shared\Payment\Contracts;

interface PaymentGatewayInterface
{
    public function getPayment(string $paymentId): array;
}
