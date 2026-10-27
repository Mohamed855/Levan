<?php

declare(strict_types=1);

namespace App\Mail\PublicRequests;

use App\Mail\BaseMailable;

final class ContactReplyMail extends BaseMailable
{
    public function __construct(
        public readonly string $customerName,
        public readonly string $originalMessage,
        public readonly string $replyMessage
    ) {}

    public function build(): static
    {
        return $this->subject('رد على استفسارك — لوت Levan')
            ->viewWithOffice('emails.public.contact_reply', [
                'customerName'    => $this->customerName,
                'originalMessage' => $this->originalMessage,
                'replyMessage'    => $this->replyMessage,
            ]);
    }
}
