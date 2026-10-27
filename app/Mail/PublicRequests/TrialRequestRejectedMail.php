<?php

declare(strict_types=1);

namespace App\Mail\PublicRequests;

use App\Mail\BaseMailable;

final class TrialRequestRejectedMail extends BaseMailable
{
    public function __construct(
        public readonly string $customerName,
        public readonly ?string $reason = null
    ) {}

    public function build(): static
    {
        return $this->subject('تحديث بخصوص طلب التجربة المجانية — لوت Levan')
            ->viewWithOffice('emails.public.trial_rejected', [
                'customerName' => $this->customerName,
                'reason'       => $this->reason,
            ]);
    }
}
