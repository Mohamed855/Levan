<?php

declare(strict_types=1);

namespace App\Contracts\Payments;

interface PaymentGateway
{
    /** @return array<string, mixed> */
    public function fetchPayment(string $paymentId): array;
}
