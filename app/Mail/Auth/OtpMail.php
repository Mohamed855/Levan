<?php

declare(strict_types=1);

namespace App\Mail\Auth;

use App\Mail\BaseMailable;

final class OtpMail extends BaseMailable
{
    public function __construct(
        public readonly string $otp,
        public readonly string $purpose = 'التحقق من البريد الإلكتروني'
    ) {}

    public function build(): static
    {
        return $this->subject("رمز التحقق ({$this->otp}) — لوت Levan")
            ->viewWithOffice('emails.auth.otp', [
                'otp'     => $this->otp,
                'purpose' => $this->purpose,
            ]);
    }
}
