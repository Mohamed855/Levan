<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Admin\Users\AdminUserStatus;
use App\Mail\Auth\OtpMail;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Settings\Settings as AdminSettings;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AdminPasswordResetOtpFeatureTest extends TestCase
{
    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.tenant' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'activitylog.enabled' => false,
            'services.moyasar.mode' => 'test',
        ]);

        DB::purge('mysql');
        DB::purge('tenant');
        Cache::forget('app_settings');
        $this->withoutVite();
        Mail::fake();

        Schema::connection('mysql')->create('admin_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->string('status')->default('active');
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::connection('mysql')->create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('image')->nullable();
            $table->string('office_name')->nullable();
            $table->timestamps();
        });

        Schema::connection('mysql')->create('tenants', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->text('data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->admin = AdminUser::create([
            'name' => 'Super Admin OTP',
            'email' => 'admin-otp@example.com',
            'phone' => '0501234567',
            'password' => Hash::make('OldAdminPass123!'),
            'status' => AdminUserStatus::ACTIVE,
            'is_super_admin' => true,
        ]);
    }

    public function test_admin_login_page_shows_forgot_password_link(): void
    {
        $this->get('/superadmin/login')
            ->assertOk()
            ->assertSee(route('admin.password.request'), false)
            ->assertSee('نسيت كلمة المرور؟');
    }

    public function test_admin_forgot_password_page_renders_email_form_and_uses_custom_admin_logo_when_available(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/admin-custom-logo.png', 'png-bytes');
        AdminSettings::create(['image' => 'branding/admin-custom-logo.png', 'office_name' => 'Lot Central']);
        Cache::forget('app_settings');

        $this->get('/superadmin/forgot-password')
            ->assertOk()
            ->assertSee('هل نسيت كلمة المرور؟')
            ->assertSee('name="email"', false)
            ->assertSee('branding/admin-custom-logo.png', false)
            ->assertDontSee('checkout-otp-timer', false);
    }

    public function test_admin_forgot_password_falls_back_to_default_logo_for_placeholder_image(): void
    {
        AdminSettings::create(['image' => 'default-office.png', 'office_name' => 'Lot Central']);
        Cache::forget('app_settings');

        $this->get('/superadmin/forgot-password')
            ->assertOk()
            ->assertSee('static/brand/logos/lot/trans-profile.png', false);
    }

    public function test_requesting_admin_otp_fails_for_unknown_or_inactive_email(): void
    {
        $this->post('/superadmin/forgot-password', ['email' => 'missing-admin@example.com'])
            ->assertSessionHasErrors('email');
        $this->assertNull(session('admin_password_reset_verification'));

        AdminUser::create([
            'name' => 'Inactive Admin',
            'email' => 'inactive-admin@example.com',
            'password' => Hash::make('OldAdminPass123!'),
            'status' => AdminUserStatus::INACTIVE,
        ]);

        $this->post('/superadmin/forgot-password', ['email' => 'inactive-admin@example.com'])
            ->assertSessionHasErrors('email');
        $this->assertNull(session('admin_password_reset_verification'));
        Mail::assertNothingSent();
    }

    public function test_requesting_admin_otp_succeeds_and_transitions_to_otp_step(): void
    {
        $this->post('/superadmin/forgot-password', ['email' => $this->admin->email])
            ->assertRedirect(route('admin.password.request'));

        $verification = session('admin_password_reset_verification');
        $this->assertNotNull($verification);
        $this->assertSame('admin', $verification['context']);
        $this->assertSame($this->admin->id, $verification['user_id']);
        $this->assertSame($this->admin->email, $verification['email']);
        $this->assertSame(hash('sha256', '000000'), $verification['otp_hash']);
        $this->assertFalse($verification['verified']);

        Mail::assertQueued(OtpMail::class, fn (OtpMail $mail): bool => $mail->hasTo($this->admin->email) && $mail->otp === '000000');

        $this->get('/superadmin/forgot-password')
            ->assertOk()
            ->assertSee('التحقق من رمز الأمان')
            ->assertSee('checkout-otp-timer', false)
            ->assertSee('btn-resend-otp', false);
    }

    public function test_verifying_invalid_admin_otp_increments_attempts_and_blocks_after_five(): void
    {
        $this->requestAdminOtp();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/superadmin/forgot-password/verify', ['otp' => '999999'])
                ->assertSessionHasErrors('otp');
            $this->assertSame($attempt, session('admin_password_reset_verification.attempts'));
        }

        $this->post('/superadmin/forgot-password/verify', ['otp' => '000000'])
            ->assertRedirect(route('admin.password.request'))
            ->assertSessionHasErrors('otp')
            ->assertSessionMissing('admin_password_reset_verification');
    }

    public function test_admin_otp_resend_respects_expiry_and_max_resends(): void
    {
        $this->requestAdminOtp();
        $this->travel(3)->minutes();

        try {
            $this->post('/superadmin/forgot-password/verify', ['otp' => '000000'])
                ->assertSessionHasErrors('otp');

            $this->postJson('/superadmin/forgot-password/resend-otp')
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('resend_count', 1)
                ->assertJsonPath('remaining_resends', 2);

            $this->postJson('/superadmin/forgot-password/resend-otp')->assertOk()->assertJsonPath('resend_count', 2);
            $this->postJson('/superadmin/forgot-password/resend-otp')->assertOk()->assertJsonPath('resend_count', 3);
            $this->postJson('/superadmin/forgot-password/resend-otp')->assertStatus(422)->assertJsonPath('success', false);
        } finally {
            $this->travelBack();
        }
    }

    public function test_editing_admin_email_clears_otp_and_restores_email_input(): void
    {
        $this->requestAdminOtp();

        $this->get('/superadmin/forgot-password/edit')
            ->assertRedirect(route('admin.password.request'));

        $this->assertNull(session('admin_password_reset_verification'));
        $this->assertSame($this->admin->email, old('email'));
    }

    public function test_verifying_valid_otp_and_resetting_password_clears_must_change_password_and_logs_into_login(): void
    {
        $this->admin->forceFill(['must_change_password' => true])->save();
        Event::fake([PasswordReset::class]);

        $this->requestAdminOtp();

        $this->post('/superadmin/forgot-password/verify', ['otp' => '000000'])
            ->assertRedirect(route('admin.password.reset'));
        $this->assertTrue(session('admin_password_reset_verification.verified'));

        $this->get('/superadmin/reset-password')
            ->assertOk()
            ->assertSee('إعادة تعيين كلمة المرور')
            ->assertSee(route('admin.password.reset.store'), false);

        $newPassword = 'NewAdminSecret123!';
        $this->post(route('admin.password.reset.store'), [
            'email' => 'tampered@example.com',
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertRedirect(route('admin.login'))
            ->assertSessionHas('status')
            ->assertSessionMissing('admin_password_reset_verification');

        $fresh = $this->admin->fresh();
        $this->assertTrue(Hash::check($newPassword, $fresh->password));
        $this->assertFalse((bool) $fresh->must_change_password);
        Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event): bool => (int) $event->user->getAuthIdentifier() === (int) $this->admin->id);
    }

    public function test_admin_reset_password_page_redirects_when_unverified_and_rejects_tenant_session_context(): void
    {
        $this->get('/superadmin/reset-password')
            ->assertRedirect(route('admin.password.request'))
            ->assertSessionHasErrors('email');

        // A tenant verification payload must never authorize an admin reset
        $this->withSession([
            'password_reset_verification' => [
                'tenant_id' => 'test',
                'user_id' => $this->admin->id,
                'email' => $this->admin->email,
                'verified' => true,
                'expires_at' => now()->addMinutes(5)->timestamp,
            ],
        ])->post(route('admin.password.reset.store'), [
            'password' => 'CrossGuardReset123!',
            'password_confirmation' => 'CrossGuardReset123!',
        ])->assertRedirect(route('admin.password.request'));

        $this->assertTrue(Hash::check('OldAdminPass123!', $this->admin->fresh()->password));
    }

    public function test_admin_form_validation_endpoint_validates_forgot_otp_and_reset_forms(): void
    {
        $this->postJson(route('admin.form-validation'), [
            'target_path' => '/superadmin/forgot-password',
            'target_method' => 'POST',
            'fields' => ['email'],
            'email' => 'not-an-email',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);

        $this->postJson(route('admin.form-validation'), [
            'target_path' => '/superadmin/forgot-password/verify',
            'target_method' => 'POST',
            'fields' => ['otp'],
            'otp' => '123',
        ])->assertUnprocessable()->assertJsonValidationErrors(['otp']);

        $this->postJson(route('admin.form-validation'), [
            'target_path' => '/superadmin/forgot-password/verify',
            'target_method' => 'POST',
            'fields' => ['otp'],
            'otp' => '123456',
        ])->assertOk()->assertJsonPath('success', true);

        $this->postJson(route('admin.form-validation'), [
            'target_path' => '/superadmin/forgot-password/reset',
            'target_method' => 'POST',
            'fields' => ['password', 'password_confirmation'],
            'password' => 'StrongAdminPass123!',
            'password_confirmation' => 'StrongAdminPass123!',
        ])->assertOk()->assertJsonPath('success', true);
    }

    private function requestAdminOtp(): void
    {
        $this->from('/superadmin/forgot-password')
            ->post('/superadmin/forgot-password', ['email' => $this->admin->email])
            ->assertRedirect(route('admin.password.request'));
    }
}
