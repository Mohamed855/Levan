<?php

declare(strict_types=1);

namespace App\Services\Shared\Auth;

use App\Services\Shared\Mail\SystemMailService;
use Illuminate\Support\Facades\Log;

class PasswordResetOtpService
{
    /**
     * Issue an OTP code for password reset, send via email, and return its SHA-256 hash.
     */
    public function issue(string $email, ?string $userName = null): string
    {
        $isTest = app()->environment(['local', 'testing']) && config('services.moyasar.mode', 'test') === 'test';
        $otp = $isTest ? '000000' : (string) random_int(100000, 999999);

        SystemMailService::sendOtp($email, $otp, 'استعادة كلمة المرور');

        if ($isTest) {
            Log::info('Password reset OTP issued in test mode', [
                'email' => $email,
                'otp' => $otp,
            ]);
        }

        return hash('sha256', $otp);
    }
}
