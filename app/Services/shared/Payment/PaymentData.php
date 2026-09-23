<?php

namespace App\Services\shared\Payment;

use Illuminate\Http\Request;

class PaymentData
{
    public function __construct(
        public readonly float  $amount,
        public readonly string $description,

        public readonly string $cardName,
        public readonly string $cardNumber,
        public readonly string $cvc,
        public readonly string $expDate,
        public readonly string $cardType = 'creditcard',

        public readonly ?string $billNumber = null,
    ) {}

    public static function fromRequest(Request $request, float $amount, string $description): self
    {
        return new self(
            amount: $amount,
            description: $description,
            cardName: $request->card_name_display,
            cardNumber: preg_replace('/\s+/', '', (string) $request->card_number),
            cvc: $request->cvc,
            expDate: preg_replace('/\s+/', '', (string) $request->exp_date_display),
            cardType: $request->cardType ?? 'creditcard',
            billNumber: null,
        );
    }

    public function withBillNumber(string $billNumber): self
    {
        return new self(
            amount: $this->amount,
            description: $this->description,
            cardName: $this->cardName,
            cardNumber: $this->cardNumber,
            cvc: $this->cvc,
            expDate: $this->expDate,
            cardType: $this->cardType,
            billNumber: $billNumber,
        );
    }
}
