<?php

declare(strict_types=1);

namespace App\Services\Shared\Registration;

use Illuminate\Support\Facades\Mail;

class RegistrationOtpService
{
    public function issue(string $email): string
    {
        $test = app()->environment(['local', 'testing']) && config('services.moyasar.mode', 'test') === 'test';
        $otp = $test ? '000000' : (string) random_int(100000, 999999);
        if (! $test) {
            \App\Services\Shared\Mail\SystemMailService::sendOtp($email, $otp, 'تأكيد البريد الإلكتروني');
        }

        return hash('sha256', $otp);
    }
}
