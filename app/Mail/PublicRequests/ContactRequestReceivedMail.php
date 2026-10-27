<?php

declare(strict_types=1);

namespace App\Mail\PublicRequests;

use App\Mail\BaseMailable;

final class ContactRequestReceivedMail extends BaseMailable
{
    public function __construct(
        public readonly string $customerName,
        public readonly ?string $subjectText = null,
        public readonly ?string $messageContent = null
    ) {}

    public function build(): static
    {
        return $this->subject('تم استلام رسالتك بنجاح — لوت Levan')
            ->viewWithOffice('emails.public.contact_received', [
                'customerName'   => $this->customerName,
                'subject'        => $this->subjectText,
                'messageContent' => $this->messageContent,
            ]);
    }
}
