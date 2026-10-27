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
            Mail::raw("رمز التحقق لتأكيد بريدك الإلكتروني هو: {$otp}", function ($message) use ($email): void {
                $message->to($email)->subject('رمز التحقق — لوت');
            });
        }

        return hash('sha256', $otp);
    }
}
