<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class TenantPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_is_available(): void
    {
        $this->get(route('tenant.password.request'))
            ->assertOk()
            ->assertSee('هل نسيت كلمة المرور؟');
    }

    public function test_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'tenant@example.com']);

        $response = $this->post(route('tenant.password.email'), [
            'email' => 'tenant@example.com',
        ]);

        $response->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $mail = $notification->toMail($user);

            return $mail->subject === 'إعادة تعيين كلمة المرور | لوت LOT'
                && str_contains($mail->actionUrl, route('tenant.password.reset', ['token' => $notification->token, 'email' => $user->email], false));
        });
    }

    public function test_reset_password_page_is_available_with_token(): void
    {
        $this->get(route('tenant.password.reset', ['token' => 'sample-token', 'email' => 'tenant@example.com']))
            ->assertOk()
            ->assertSee('إعادة تعيين كلمة المرور');
    }

    public function test_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'tenant@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::broker('users')->createToken($user);

        $response = $this->post(route('tenant.password.store'), [
            'token' => $token,
            'email' => 'tenant@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect(route('tenant.login'));
        $response->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }
}
