<?php

declare(strict_types=1);

namespace App\Mail\PublicRequests;

use App\Mail\BaseMailable;

final class TrialRequestReceivedMail extends BaseMailable
{
    public function __construct(
        public readonly string $customerName,
        public readonly ?string $companyName = null
    ) {}

    public function build(): static
    {
        return $this->subject('تم استلام طلب التجربة المجانية بنجاح — لوت Levan')
            ->viewWithOffice('emails.public.trial_received', [
                'customerName' => $this->customerName,
                'companyName'  => $this->companyName,
            ]);
    }
}
