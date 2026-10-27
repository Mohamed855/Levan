<?php

declare(strict_types=1);

namespace App\Mail\Auth;

use App\Mail\BaseMailable;

final class VerifyEmailMail extends BaseMailable
{
    public function __construct(
        public readonly string $verifyUrl,
        public readonly ?string $userName = null
    ) {}

    public function build(): static
    {
        return $this->subject('تأكيد بريدك الإلكتروني — لوت Levan')
            ->viewWithOffice('emails.auth.verify_email', [
                'verifyUrl' => $this->verifyUrl,
                'userName'  => $this->userName,
            ]);
    }
}
