<?php

declare(strict_types=1);

namespace App\Mail\Auth;

use App\Mail\BaseMailable;

final class PasswordResetMail extends BaseMailable
{
    public function __construct(
        public readonly string $resetUrl,
        public readonly ?string $userName = null
    ) {}

    public function build(): static
    {
        return $this->subject('إعادة تعيين كلمة المرور — لوت Levan')
            ->viewWithOffice('emails.auth.password_reset', [
                'resetUrl' => $this->resetUrl,
                'userName' => $this->userName,
            ]);
    }
}
