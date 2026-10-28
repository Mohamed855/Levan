<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantPasswordResetOtpFeatureTest extends TestCase
{
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::findOrFail('test');
        tenancy()->initialize($this->tenant);

        $this->user = User::firstOrCreate(
            ['email' => 'test-otp-user@example.com'],
            [
                'name' => 'OTP Test User',
                'password' => Hash::make('OldPassword123!'),
                'status' => 'active',
            ]
        );
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_forgot_password_page_renders_email_form_initially(): void
    {
        $response = $this->get('http://test.localhost/forgot-password');

        $response->assertStatus(200);
        $response->assertSee('هل نسيت كلمة المرور؟');
        $response->assertSee('name="email"', false);
        $response->assertDontSee('checkout-otp-timer', false);
    }

    public function test_requesting_otp_fails_for_unknown_email(): void
    {
        $response = $this->post('http://test.localhost/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertNull(session('password_reset_verification'));
    }

    public function test_requesting_otp_succeeds_and_transitions_to_otp_step(): void
    {
        $response = $this->post('http://test.localhost/forgot-password', [
            'email' => $this->user->email,
        ]);

        $response->assertRedirect('http://test.localhost/forgot-password');

        $verification = session('password_reset_verification');
        $this->assertNotNull($verification);
        $this->assertSame($this->user->email, $verification['email']);
        $this->assertSame(hash('sha256', '000000'), $verification['otp_hash']);
        $this->assertFalse($verification['verified']);

        // Next GET request renders OTP view
        $viewResponse = $this->get('http://test.localhost/forgot-password');
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('التحقق من رمز الأمان');
        $viewResponse->assertSee('checkout-otp-timer', false);
        $viewResponse->assertSee('btn-resend-otp', false);
        $viewResponse->assertSee($this->user->email);
    }

    public function test_verifying_invalid_otp_fails_and_increments_attempts(): void
    {
        $this->withSession([
            'password_reset_verification' => [
                'email' => $this->user->email,
                'user_id' => $this->user->id,
                'otp_hash' => hash('sha256', '000000'),
                'expires_at' => now()->addMinutes(2)->timestamp,
                'session_expires_at' => now()->addMinutes(15)->timestamp,
                'resend_count' => 0,
                'attempts' => 0,
                'verified' => false,
            ],
        ]);

        $response = $this->post('http://test.localhost/forgot-password/verify', [
            'otp' => '999999',
        ]);

        $response->assertSessionHasErrors('otp');
        $verification = session('password_reset_verification');
        $this->assertSame(1, $verification['attempts']);
        $this->assertFalse($verification['verified']);
    }

    public function test_resending_otp_updates_timer_and_respects_max_resends(): void
    {
        $this->withSession([
            'password_reset_verification' => [
                'email' => $this->user->email,
                'user_id' => $this->user->id,
                'otp_hash' => hash('sha256', '000000'),
                'expires_at' => now()->addMinutes(2)->timestamp,
                'session_expires_at' => now()->addMinutes(15)->timestamp,
                'resend_count' => 0,
                'attempts' => 0,
                'verified' => false,
            ],
        ]);

        // Resend 1
        $res1 = $this->postJson('http://test.localhost/forgot-password/resend-otp');
        $res1->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('resend_count', 1)
            ->assertJsonPath('remaining_resends', 2);

        // Resend 2
        $res2 = $this->postJson('http://test.localhost/forgot-password/resend-otp');
        $res2->assertOk()
            ->assertJsonPath('resend_count', 2);

        // Resend 3
        $res3 = $this->postJson('http://test.localhost/forgot-password/resend-otp');
        $res3->assertOk()
            ->assertJsonPath('resend_count', 3)
            ->assertJsonPath('remaining_resends', 0);

        // Resend 4 (blocked)
        $res4 = $this->postJson('http://test.localhost/forgot-password/resend-otp');
        $res4->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_editing_email_clears_otp_and_restores_email_input(): void
    {
        $this->withSession([
            'password_reset_verification' => [
                'email' => $this->user->email,
                'otp_hash' => hash('sha256', '000000'),
                'verified' => false,
            ],
        ]);

        $response = $this->get('http://test.localhost/forgot-password/edit');

        $response->assertRedirect('http://test.localhost/forgot-password');
        $this->assertNull(session('password_reset_verification'));
        $this->assertSame($this->user->email, old('email'));
    }

    public function test_verifying_valid_otp_marks_session_verified_and_redirects_to_reset_password(): void
    {
        $this->withSession([
            'password_reset_verification' => [
                'email' => $this->user->email,
                'user_id' => $this->user->id,
                'otp_hash' => hash('sha256', '000000'),
                'expires_at' => now()->addMinutes(2)->timestamp,
                'session_expires_at' => now()->addMinutes(15)->timestamp,
                'resend_count' => 0,
                'attempts' => 0,
                'verified' => false,
            ],
        ]);

        $response = $this->post('http://test.localhost/forgot-password/verify', [
            'otp' => '000000',
        ]);

        $response->assertRedirect('http://test.localhost/reset-password');
        $verification = session('password_reset_verification');
        $this->assertTrue($verification['verified']);

        // Can access reset-password page
        $resetPageResponse = $this->get('http://test.localhost/reset-password');
        $resetPageResponse->assertStatus(200);
        $resetPageResponse->assertSee('إعادة تعيين كلمة المرور');
        $resetPageResponse->assertSee('name="password"', false);
    }

    public function test_reset_password_page_redirects_if_otp_not_verified(): void
    {
        $response = $this->get('http://test.localhost/reset-password');

        $response->assertRedirect('http://test.localhost/forgot-password');
        $response->assertSessionHasErrors('email');
    }

    public function test_resetting_password_with_verified_otp_updates_password_and_redirects_to_login(): void
    {
        $newPassword = 'NewSecretPassword123!';

        $this->withSession([
            'password_reset_verification' => [
                'email' => $this->user->email,
                'user_id' => $this->user->id,
                'otp_hash' => hash('sha256', '000000'),
                'expires_at' => now()->addMinutes(15)->timestamp,
                'session_expires_at' => now()->addMinutes(15)->timestamp,
                'verified' => true,
            ],
        ]);

        $response = $this->post('http://test.localhost/reset-password', [
            'email' => $this->user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertRedirect('http://test.localhost/login');
        $response->assertSessionHas('status');

        $this->assertNull(session('password_reset_verification'));

        // Verify that the user's password was updated
        $this->assertTrue(Hash::check($newPassword, $this->user->fresh()->password));
    }
}
