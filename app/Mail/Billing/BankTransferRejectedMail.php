<?php

declare(strict_types=1);

namespace App\Mail\Billing;

use App\Mail\BaseMailable;
use App\Models\Admin\Bills\Bill;

final class BankTransferRejectedMail extends BaseMailable
{
    public function __construct(
        public readonly Bill $bill,
        public readonly ?string $reason = null,
        public readonly ?string $uploadUrl = null
    ) {}

    public function build(): static
    {
        $tenant = $this->bill->tenant;
        $customerName = $tenant?->company_name 
            ?? $this->bill->metadata['billing_company_name'] 
            ?? $this->bill->metadata['billing_name'] 
            ?? 'عزيزنا العميل';

        $uploadUrl = $this->uploadUrl;
        if (! $uploadUrl) {
            $uploadUrl = $tenant ? route('path.bills.show', ['tenant' => $tenant->id, 'bill' => $this->bill->id], false) : config('app.url');
            if (! str_starts_with($uploadUrl, 'http')) {
                $uploadUrl = url($uploadUrl);
            }
        }

        return $this->subject("تنبيه بخصوص التحويل البنكي للفاتورة #{$this->bill->bill_number} — لوت Levan")
            ->viewWithOffice('emails.billing.bank_transfer_rejected', [
                'bill'         => $this->bill,
                'customerName' => $customerName,
                'reason'       => $this->reason ?? $this->bill->reject_reason,
                'uploadUrl'    => $uploadUrl,
            ]);
    }
}
