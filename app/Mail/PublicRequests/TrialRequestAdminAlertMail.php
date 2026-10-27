<?php

declare(strict_types=1);

namespace App\Mail\PublicRequests;

use App\Mail\BaseMailable;
use App\Models\Admin\PublicRequests\TrialRequest;

final class TrialRequestAdminAlertMail extends BaseMailable
{
    public function __construct(
        public readonly TrialRequest $trialRequest
    ) {}

    public function build(): static
    {
        return $this->subject("طلب تجربة مجانية جديد من: {$this->trialRequest->customer_name} — لوت Levan")
            ->viewWithOffice('emails.public.trial_admin_alert', [
                'trialRequest' => $this->trialRequest,
            ]);
    }
}
