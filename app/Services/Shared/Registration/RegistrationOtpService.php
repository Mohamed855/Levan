<?php

declare(strict_types=1);

namespace App\Services\Shared\Registration;

use App\Services\Shared\Mail\SystemMailService;
use Illuminate\Support\Facades\Log;

class RegistrationOtpService
{
    public function issue(string $email): string
    {
        $test = app()->environment(['local', 'testing']) && config('services.moyasar.mode', 'test') === 'test';
        $otp = $test ? '000000' : (string) random_int(100000, 999999);

        SystemMailService::sendOtp($email, $otp, 'تأكيد البريد الإلكتروني');

        if ($test) {
            Log::info('Registration OTP issued in test mode', [
                'email' => $email,
                'otp' => $otp,
            ]);
        }

        return hash('sha256', $otp);
    }
}
