<?php

declare(strict_types=1);

namespace App\Mail\PublicRequests;

use App\Mail\BaseMailable;

final class TrialRequestApprovedMail extends BaseMailable
{
    public function __construct(
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly string $loginUrl,
        public readonly int $trialDays = 7,
        public readonly ?string $endDate = null
    ) {}

    public function build(): static
    {
        return $this->subject('تم تفعيل حسابك التجريبي المجاني — لوت Levan')
            ->viewWithOffice('emails.public.trial_approved', [
                'customerName'  => $this->customerName,
                'customerEmail' => $this->customerEmail,
                'loginUrl'      => $this->loginUrl,
                'trialDays'     => $this->trialDays,
                'endDate'       => $this->endDate,
            ]);
    }
}
