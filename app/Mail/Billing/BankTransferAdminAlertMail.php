<?php

declare(strict_types=1);

namespace App\Mail\Billing;

use App\Mail\BaseMailable;
use App\Models\Admin\Bills\Bill;

final class BankTransferAdminAlertMail extends BaseMailable
{
    public function __construct(
        public readonly Bill $bill
    ) {}

    public function build(): static
    {
        $customerName = $this->bill->tenant?->company_name 
            ?? $this->bill->metadata['billing_company_name'] 
            ?? $this->bill->metadata['billing_name'] 
            ?? 'عميل';

        return $this->subject("إشعار تحويل بنكي جديد للفاتورة #{$this->bill->bill_number} ({$customerName}) — لوت Levan")
            ->viewWithOffice('emails.billing.bank_transfer_admin_alert', [
                'bill'         => $this->bill,
                'customerName' => $customerName,
            ]);
    }
}
