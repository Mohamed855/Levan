<?php

declare(strict_types=1);

namespace App\Mail\PublicRequests;

use App\Mail\BaseMailable;

final class ContactRequestAdminAlertMail extends BaseMailable
{
    public function __construct(
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly string $messageContent,
        public readonly ?string $customerPhone = null,
        public readonly ?string $subjectText = null,
        public readonly ?string $adminViewUrl = null
    ) {}

    public function build(): static
    {
        return $this->subject("رسالة جديدة من: {$this->customerName} — تواصل معنا")
            ->viewWithOffice('emails.public.contact_admin_alert', [
                'customerName'   => $this->customerName,
                'customerEmail'  => $this->customerEmail,
                'customerPhone'  => $this->customerPhone,
                'subjectText'    => $this->subjectText,
                'messageContent' => $this->messageContent,
                'adminViewUrl'   => $this->adminViewUrl,
            ]);
    }
}
