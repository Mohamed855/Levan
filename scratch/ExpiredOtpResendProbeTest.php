<?php

declare(strict_types=1);

namespace Tests\Diagnostics;

use App\Mail\Auth\OtpMail;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\TenantPasswordResetOtpFeatureTest;

final class ExpiredOtpResendProbeTest extends TenantPasswordResetOtpFeatureTest
{
    public function test_expired_verification_session_should_not_send_an_unusable_replacement_otp(): void
    {
        $this->post('http://test.localhost/forgot-password', ['email' => User::firstOrFail()->email])
            ->assertRedirect('http://test.localhost/forgot-password');
        $this->travel(16)->minutes();

        try {
            $response = $this->postJson('http://test.localhost/forgot-password/resend-otp');
            Mail::assertQueuedCount(2);
            $this->post('http://test.localhost/forgot-password/verify', ['otp' => '000000'])
                ->assertRedirect('http://test.localhost/forgot-password')
                ->assertSessionHasErrors('otp')
                ->assertSessionMissing('password_reset_verification');
            $response->assertUnprocessable();
        } finally {
            $this->travelBack();
        }
    }
}
