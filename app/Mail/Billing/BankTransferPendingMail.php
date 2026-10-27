<?php

declare(strict_types=1);

namespace App\Mail\Billing;

use App\Mail\BaseMailable;
use App\Models\Admin\Bills\Bill;

final class BankTransferPendingMail extends BaseMailable
{
    public function __construct(
        public readonly Bill $bill,
        public readonly ?string $customerName = null
    ) {}

    public function build(): static
    {
        $name = $this->customerName 
            ?? $this->bill->tenant?->company_name 
            ?? $this->bill->metadata['billing_name'] 
            ?? $this->bill->metadata['billing_company_name'] 
            ?? 'عزيزنا العميل';

        $operationType = $this->bill->type?->label() ?? 'اشتراك';

        return $this->subject("تم استلام إيصال التحويل البنكي للفاتورة #{$this->bill->bill_number} — لوت Levan")
            ->viewWithOffice('emails.billing.bank_transfer_pending', [
                'customerName'  => $name,
                'billNumber'    => $this->bill->bill_number,
                'amount'        => $this->bill->amount,
                'operationType' => $operationType,
            ]);
    }
}
