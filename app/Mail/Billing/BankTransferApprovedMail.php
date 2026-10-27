<?php

declare(strict_types=1);

namespace App\Mail\Billing;

use App\Mail\BaseMailable;
use App\Models\Admin\Bills\Bill;

final class BankTransferApprovedMail extends BaseMailable
{
    public function __construct(
        public readonly Bill $bill
    ) {}

    public function build(): static
    {
        $tenant = $this->bill->tenant;
        $customerName = $tenant?->company_name 
            ?? $this->bill->metadata['billing_company_name'] 
            ?? $this->bill->metadata['billing_name'] 
            ?? 'عزيزنا العميل';

        $loginUrl = $tenant ? route('path.dashboard', ['tenant' => $tenant->id], false) : config('app.url');
        if (! str_starts_with($loginUrl, 'http')) {
            $loginUrl = url($loginUrl);
        }

        return $this->subject("تم اعتماد الحوالة البنكية وتفعيل الفاتورة #{$this->bill->bill_number} — لوت Levan")
            ->viewWithOffice('emails.billing.bank_transfer_approved', [
                'bill'         => $this->bill,
                'customerName' => $customerName,
                'loginUrl'     => $loginUrl,
            ]);
    }
}
