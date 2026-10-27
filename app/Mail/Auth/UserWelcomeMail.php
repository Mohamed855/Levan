<?php

declare(strict_types=1);

namespace App\Mail\Auth;

use App\Mail\BaseMailable;

final class UserWelcomeMail extends BaseMailable
{
    public function __construct(
        public readonly string $userName,
        public readonly string $email,
        public readonly ?string $temporaryPassword,
        public readonly string $loginUrl,
        public readonly ?string $companyName = null,
        public readonly ?string $roleName = null
    ) {}

    public function build(): static
    {
        return $this->subject('بيانات حسابك الجديد — لوت Levan')
            ->viewWithOffice('emails.auth.user_welcome', [
                'userName'          => $this->userName,
                'email'             => $this->email,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl'          => $this->loginUrl,
                'companyName'       => $this->companyName,
                'roleName'          => $this->roleName,
            ]);
    }
}
