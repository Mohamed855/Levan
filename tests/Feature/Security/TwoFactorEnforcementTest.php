<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Admin\AdminUser;
use App\Models\Admin\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class TwoFactorEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_stays_guest_and_starts_an_email_challenge_when_two_factor_is_required(): void
    {
        Mail::fake();

        $admin = AdminUser::factory()->create(['password' => 'secret-password']);
        Setting::set('security', ['force_two_factor_auth' => true]);

        $response = $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('admin.two-factor.create'));
        $this->assertGuest('admin');
        $this->assertNotNull(session('two_factor_challenge.admin'));
        Mail::assertSentCount(1);
    }

    public function test_active_admin_can_start_a_normal_session_when_two_factor_is_disabled(): void
    {
        $admin = AdminUser::factory()->create(['password' => 'secret-password']);
        Setting::set('security', ['force_two_factor_auth' => false]);

        $response = $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }
}
